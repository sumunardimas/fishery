<?php

namespace App\Http\Controllers;

use App\Services\InventoryFifoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BarangKeluarController extends Controller
{
    public function __construct(private readonly InventoryFifoService $fifoService) {}

    public function index(): View
    {
        $itemPembelian = DB::table('master_item_pembelian as item')
            ->leftJoin('item_pembelian_stock as stock', 'stock.id_item_pembelian', '=', 'item.id_item_pembelian')
            ->select('item.id_item_pembelian as id', 'item.nama_item as nama', 'item.satuan', DB::raw('COALESCE(stock.stok_aktual, 0) as stok'))
            ->orderBy('item.nama_item')
            ->get();

        $perbekalan = DB::table('master_perbekalan as item')
            ->leftJoin('perbekalan_stock as stock', 'stock.id_barang', '=', 'item.id_barang')
            ->select('item.id_barang as id', 'item.nama_barang as nama', 'item.satuan', DB::raw('COALESCE(stock.stok_aktual, 0) as stok'))
            ->orderBy('item.nama_barang')
            ->get();

        return view('barang-keluar.index', compact('itemPembelian', 'perbekalan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'sumber_tujuan' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item' => ['required', 'string', 'regex:/^(pembelian|perbekalan):[1-9][0-9]*$/'],
            'items.*.jumlah' => ['required', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($request, $data) {
            foreach ($data['items'] as $row) {
                [$itemType, $id] = explode(':', $row['item'], 2);
                $quantity = (float) $row['jumlah'];
                $valuation = $this->fifoService->valueOutgoing(
                    $itemType,
                    (int) $id,
                    $quantity,
                    $data['tanggal_transaksi']
                );

                $itemRequest = clone $request;
                $itemRequest->replace([
                    'tanggal_transaksi' => $data['tanggal_transaksi'],
                    'jenis_transaksi' => 'out',
                    'mode_transaksi' => 'normal',
                    'akun_pembayaran' => null,
                    'jumlah' => $quantity,
                    'harga_satuan' => $valuation['average_price'],
                    'sumber_tujuan' => $data['sumber_tujuan'] ?? null,
                    'keterangan' => $this->fifoNote($data['keterangan'] ?? null, $valuation['layers']),
                ]);

                if ($itemType === 'pembelian') {
                    $itemRequest->merge(['id_item_pembelian' => (int) $id]);
                    (new PembelianController)->storeTransaction($itemRequest);
                } else {
                    $itemRequest->merge(['id_barang' => (int) $id]);
                    (new MasterPerbekalanController)->storeTransaction($itemRequest);
                }
            }
        });

        return redirect()->route('barang-keluar.index')
            ->with('success', count($data['items']).' item barang keluar berhasil dicatat dengan harga FIFO.');
    }

    /** @param array<int, array<string, float|int|string>> $layers */
    private function fifoNote(?string $note, array $layers): string
    {
        $references = collect($layers)
            ->map(fn (array $layer) => '#'.$layer['reference_id'].' ('.number_format((float) $layer['quantity'], 2, ',', '.').' × Rp '.number_format((float) $layer['harga_satuan'], 2, ',', '.').')')
            ->implode(', ');

        return '[FIFO: '.$references.']'.(trim((string) $note) !== '' ? ' '.trim((string) $note) : '');
    }
}
