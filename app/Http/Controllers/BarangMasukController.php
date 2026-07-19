<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BarangMasukController extends Controller
{
    public function index(): View
    {
        $itemPembelian = DB::table('master_item_pembelian')
            ->select('id_item_pembelian as id', 'nama_item as nama', 'satuan')
            ->orderBy('nama_item')
            ->get();

        $perbekalan = DB::table('master_perbekalan')
            ->select('id_barang as id', 'nama_barang as nama', 'satuan')
            ->orderBy('nama_barang')
            ->get();

        return view('barang-masuk.index', compact('itemPembelian', 'perbekalan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'mode_transaksi' => ['required', 'in:normal,import_awal'],
            'akun_pembayaran' => ['nullable', 'in:kas,bank,hutang'],
            'sumber_tujuan' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item' => ['required', 'string', 'regex:/^(pembelian|perbekalan):[1-9][0-9]*$/'],
            'items.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'items.*.harga_satuan' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $data) {
            foreach ($data['items'] as $row) {
                [$jenisItem, $id] = explode(':', $row['item'], 2);

                $itemRequest = clone $request;
                $itemRequest->replace([
                    'tanggal_transaksi' => $data['tanggal_transaksi'],
                    'jenis_transaksi' => 'in',
                    'mode_transaksi' => $data['mode_transaksi'],
                    'akun_pembayaran' => $data['akun_pembayaran'] ?? null,
                    'jumlah' => $row['jumlah'],
                    'harga_satuan' => $row['harga_satuan'] ?? null,
                    'sumber_tujuan' => $data['sumber_tujuan'] ?? null,
                    'keterangan' => $data['keterangan'] ?? null,
                ]);

                if ($jenisItem === 'pembelian') {
                    $itemRequest->merge(['id_item_pembelian' => (int) $id]);
                    (new PembelianController)->storeTransaction($itemRequest);
                } else {
                    $itemRequest->merge(['id_barang' => (int) $id]);
                    (new MasterPerbekalanController)->storeTransaction($itemRequest);
                }
            }
        });

        return redirect()->route('barang-masuk.index')
            ->with('success', count($data['items']).' item barang masuk berhasil dicatat.');
    }
}
