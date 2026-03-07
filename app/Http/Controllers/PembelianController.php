<?php

namespace App\Http\Controllers;

use App\Models\GudangItemPembelianStock;
use App\Models\MasterItemPembelian;
use App\Models\PembelianTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PembelianController extends Controller
{
    public function index(Request $request): View
    {
        $selectedItemId = $request->integer('show_item');

        $items = DB::table('master_item_pembelian as mip')
            ->leftJoin('gudang_item_pembelian_stock as s', 's.id_item_pembelian', '=', 'mip.id_item_pembelian')
            ->select(
                'mip.id_item_pembelian',
                'mip.nama_item',
                'mip.kategori',
                'mip.satuan',
                'mip.keterangan',
                DB::raw('COALESCE(SUM(s.stok_aktual), 0) as total_stok')
            )
            ->groupBy('mip.id_item_pembelian', 'mip.nama_item', 'mip.kategori', 'mip.satuan', 'mip.keterangan')
            ->orderBy('mip.nama_item')
            ->get();

        $gudangs = DB::table('master_gudang')->orderBy('nama_gudang')->get();

        $selectedItem = null;
        $transactions = collect();

        if ($selectedItemId > 0) {
            $selectedItem = MasterItemPembelian::query()->find($selectedItemId);
            if ($selectedItem) {
                $transactions = DB::table('pembelian_transaction as t')
                    ->join('master_gudang as g', 'g.id_gudang', '=', 't.id_gudang')
                    ->where('t.id_item_pembelian', $selectedItem->id_item_pembelian)
                    ->orderByDesc('t.tanggal_transaksi')
                    ->orderByDesc('t.id_transaction')
                    ->select('t.*', 'g.nama_gudang')
                    ->get();
            }
        }

        return view('pembelian.index', compact('items', 'gudangs', 'selectedItem', 'transactions', 'selectedItemId'));
    }

    public function storeItem(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_item' => ['required', 'string', 'max:255', 'unique:master_item_pembelian,nama_item'],
            'kategori' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
        ]);

        MasterItemPembelian::create($data);

        return redirect()->route('pembelian.index')->with('success', 'Master item pembelian berhasil ditambahkan.');
    }

    public function updateItem(Request $request, MasterItemPembelian $item): RedirectResponse
    {
        $data = $request->validate([
            'nama_item' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_item_pembelian', 'nama_item')->ignore($item->id_item_pembelian, 'id_item_pembelian'),
            ],
            'kategori' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $item->update($data);

        return redirect()->route('pembelian.index')->with('success', 'Master item pembelian berhasil diperbarui.');
    }

    public function destroyItem(MasterItemPembelian $item): RedirectResponse
    {
        $used = DB::table('pembelian_transaction')
            ->where('id_item_pembelian', $item->id_item_pembelian)
            ->exists();

        if ($used) {
            return redirect()->route('pembelian.index')->withErrors([
                'message' => 'Item tidak bisa dihapus karena sudah memiliki riwayat transaksi.',
            ]);
        }

        DB::table('gudang_item_pembelian_stock')
            ->where('id_item_pembelian', $item->id_item_pembelian)
            ->delete();

        $item->delete();

        return redirect()->route('pembelian.index')->with('success', 'Master item pembelian berhasil dihapus.');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'id_item_pembelian' => ['required', 'integer', 'exists:master_item_pembelian,id_item_pembelian'],
            'id_gudang' => ['required', 'integer', 'exists:master_gudang,id_gudang'],
            'jenis_transaksi' => ['required', 'in:in,out'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'sumber_tujuan' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data) {
            $jumlah = (float) $data['jumlah'];

            $stock = GudangItemPembelianStock::query()
                ->where('id_gudang', (int) $data['id_gudang'])
                ->where('id_item_pembelian', (int) $data['id_item_pembelian'])
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = GudangItemPembelianStock::create([
                    'id_gudang' => (int) $data['id_gudang'],
                    'id_item_pembelian' => (int) $data['id_item_pembelian'],
                    'stok_aktual' => 0,
                ]);
            }

            $stokSaatIni = (float) $stock->stok_aktual;
            if ($data['jenis_transaksi'] === 'out' && $stokSaatIni < $jumlah) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Stok tidak mencukupi. Stok tersedia: '.number_format($stokSaatIni, 2, ',', '.').' '.$this->getItemUnit((int) $data['id_item_pembelian']).'.',
                ]);
            }

            $stock->stok_aktual = $data['jenis_transaksi'] === 'in'
                ? $stokSaatIni + $jumlah
                : $stokSaatIni - $jumlah;
            $stock->save();

            $hargaSatuan = $data['harga_satuan'] ?? null;
            $totalHarga = $hargaSatuan !== null ? $jumlah * (float) $hargaSatuan : 0;

            PembelianTransaction::create([
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'id_item_pembelian' => (int) $data['id_item_pembelian'],
                'id_gudang' => (int) $data['id_gudang'],
                'jenis_transaksi' => $data['jenis_transaksi'],
                'jumlah' => $jumlah,
                'harga_satuan' => $hargaSatuan,
                'total_harga' => $totalHarga,
                'sumber_tujuan' => $data['sumber_tujuan'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);
        });

        return redirect()->route('pembelian.index', ['show_item' => (int) $data['id_item_pembelian']])
            ->with('success', 'Transaksi pembelian berhasil disimpan.');
    }

    public function destroyTransaction(PembelianTransaction $transaction): RedirectResponse
    {
        DB::transaction(function () use ($transaction) {
            $stock = GudangItemPembelianStock::query()
                ->where('id_gudang', (int) $transaction->id_gudang)
                ->where('id_item_pembelian', (int) $transaction->id_item_pembelian)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw ValidationException::withMessages([
                    'message' => 'Stok tidak ditemukan. Tidak bisa membatalkan transaksi.',
                ]);
            }

            $stokSaatIni = (float) $stock->stok_aktual;
            $jumlah = (float) $transaction->jumlah;

            // Reverse effect of the transaction to keep stock integrity.
            if ($transaction->jenis_transaksi === 'in') {
                if ($stokSaatIni < $jumlah) {
                    throw ValidationException::withMessages([
                        'message' => 'Transaksi tidak bisa dihapus karena akan membuat stok negatif.',
                    ]);
                }

                $stock->stok_aktual = $stokSaatIni - $jumlah;
            } else {
                $stock->stok_aktual = $stokSaatIni + $jumlah;
            }

            $stock->save();
            $transaction->delete();
        });

        return redirect()->route('pembelian.index', ['show_item' => (int) $transaction->id_item_pembelian])
            ->with('success', 'Transaksi berhasil dihapus dan stok telah disesuaikan.');
    }

    private function getItemUnit(int $itemId): string
    {
        return (string) DB::table('master_item_pembelian')
            ->where('id_item_pembelian', $itemId)
            ->value('satuan');
    }
}
