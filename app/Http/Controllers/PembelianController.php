<?php

namespace App\Http\Controllers;

use App\Models\ItemPembelianStock;
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
    public function index(): RedirectResponse
    {
        return redirect()->route('master.item-pembelian.index');
    }

    public function transaksi(): View
    {
        $items = DB::table('master_item_pembelian as mip')
            ->leftJoin('item_pembelian_stock as s', 's.id_item_pembelian', '=', 'mip.id_item_pembelian')
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

        return view('pembelian.transaksi', compact('items'));
    }

    public function riwayat(Request $request): View
    {
        $selectedItemId = $request->integer('show_item');
        $defaultStart = now()->subDays(29)->toDateString();
        $defaultEnd = now()->toDateString();

        $startDate = $request->input('start_date', $defaultStart);
        $endDate = $request->input('end_date', $defaultEnd);

        $items = DB::table('master_item_pembelian as mip')
            ->leftJoin('item_pembelian_stock as s', 's.id_item_pembelian', '=', 'mip.id_item_pembelian')
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

        $selectedItem = null;

        $transactionsQuery = DB::table('pembelian_transaction as t')
            ->join('master_item_pembelian as mip', 'mip.id_item_pembelian', '=', 't.id_item_pembelian')
            ->whereDate('t.tanggal_transaksi', '>=', $startDate)
            ->whereDate('t.tanggal_transaksi', '<=', $endDate)
            ->select('t.*', 'mip.nama_item', 'mip.satuan')
            ->orderByDesc('t.tanggal_transaksi')
            ->orderByDesc('t.id_transaction');

        if ($selectedItemId > 0) {
            $selectedItem = MasterItemPembelian::query()->find($selectedItemId);
            if ($selectedItem) {
                $transactionsQuery->where('t.id_item_pembelian', (int) $selectedItem->id_item_pembelian);
            } else {
                $selectedItemId = 0;
            }
        }

        $transactions = $transactionsQuery->get();

        return view('pembelian.riwayat', compact(
            'items',
            'selectedItem',
            'transactions',
            'selectedItemId',
            'startDate',
            'endDate'
        ));
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

        return redirect()->route('master.item-pembelian.index')->with('success', 'Master item pembelian berhasil ditambahkan.');
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

        return redirect()->route('master.item-pembelian.index')->with('success', 'Master item pembelian berhasil diperbarui.');
    }

    public function destroyItem(MasterItemPembelian $item): RedirectResponse
    {
        $used = DB::table('pembelian_transaction')
            ->where('id_item_pembelian', $item->id_item_pembelian)
            ->exists();

        if ($used) {
            return redirect()->route('master.item-pembelian.index')->withErrors([
                'message' => 'Item tidak bisa dihapus karena sudah memiliki riwayat transaksi.',
            ]);
        }

        DB::table('item_pembelian_stock')
            ->where('id_item_pembelian', $item->id_item_pembelian)
            ->delete();

        $item->delete();

        return redirect()->route('master.item-pembelian.index')->with('success', 'Master item pembelian berhasil dihapus.');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'id_item_pembelian' => ['required', 'integer', 'exists:master_item_pembelian,id_item_pembelian'],
            'jenis_transaksi' => ['required', 'in:in,out'],
            'akun_pembayaran' => ['nullable', 'in:kas,bank', 'required_if:jenis_transaksi,in'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'sumber_tujuan' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        if (($data['jenis_transaksi'] ?? null) === 'in' && (float) ($data['harga_satuan'] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'harga_satuan' => 'Harga satuan wajib diisi dan harus lebih dari 0 untuk transaksi IN.',
            ]);
        }

        DB::transaction(function () use ($data) {
            $jumlah = (float) $data['jumlah'];

            $stock = ItemPembelianStock::query()
                ->where('id_item_pembelian', (int) $data['id_item_pembelian'])
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = ItemPembelianStock::create([
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
                'jenis_transaksi' => $data['jenis_transaksi'],
                'akun_pembayaran' => $data['akun_pembayaran'] ?? null,
                'jumlah' => $jumlah,
                'harga_satuan' => $hargaSatuan,
                'total_harga' => $totalHarga,
                'sumber_tujuan' => $data['sumber_tujuan'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            // Purchase IN with price will reduce selected account balance.
            if ($data['jenis_transaksi'] === 'in' && $totalHarga > 0 && !empty($data['akun_pembayaran'])) {
                $itemName = (string) DB::table('master_item_pembelian')
                    ->where('id_item_pembelian', (int) $data['id_item_pembelian'])
                    ->value('nama_item');

                $this->postArusKas(
                    akun: $data['akun_pembayaran'],
                    tanggal: $data['tanggal_transaksi'],
                    kategori: 'Pembelian Barang',
                    deskripsi: 'Pembelian '.$itemName.' ('.number_format($jumlah, 2, ',', '.').' '.$this->getItemUnit((int) $data['id_item_pembelian']).')'.($data['sumber_tujuan'] ? ' dari '.$data['sumber_tujuan'] : ''),
                    debit: 0,
                    kredit: (float) $totalHarga
                );
            }
        });

        return redirect()->route('pembelian.riwayat', ['show_item' => (int) $data['id_item_pembelian']])
            ->with('success', 'Transaksi pembelian berhasil disimpan.');
    }

    public function destroyTransaction(Request $request, PembelianTransaction $transaction): RedirectResponse
    {
        $selectedItemId = $request->integer('show_item');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        DB::transaction(function () use ($transaction) {
            $stock = ItemPembelianStock::query()
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

            if (
                $transaction->jenis_transaksi === 'in'
                && (float) $transaction->total_harga > 0
                && in_array((string) $transaction->akun_pembayaran, ['kas', 'bank'], true)
            ) {
                $this->postArusKas(
                    akun: (string) $transaction->akun_pembayaran,
                    tanggal: now()->toDateString(),
                    kategori: 'Pembatalan Pembelian Barang',
                    deskripsi: 'Pembatalan transaksi pembelian #'.$transaction->id_transaction,
                    debit: (float) $transaction->total_harga,
                    kredit: 0
                );
            }

            $transaction->delete();
        });

        $redirectParams = [];
        if ($selectedItemId > 0) {
            $redirectParams['show_item'] = $selectedItemId;
        }
        if (!empty($startDate)) {
            $redirectParams['start_date'] = $startDate;
        }
        if (!empty($endDate)) {
            $redirectParams['end_date'] = $endDate;
        }

        return redirect()->route('pembelian.riwayat', $redirectParams)
            ->with('success', 'Transaksi berhasil dihapus dan stok telah disesuaikan.');
    }

    private function getItemUnit(int $itemId): string
    {
        return (string) DB::table('master_item_pembelian')
            ->where('id_item_pembelian', $itemId)
            ->value('satuan');
    }

    private function getLastSaldoByAkun(string $akun): float
    {
        return (float) (DB::table('arus_kas')
            ->where('akun', $akun)
            ->orderByDesc('id_kas')
            ->value('saldo') ?? 0);
    }

    private function postArusKas(string $akun, string $tanggal, string $kategori, string $deskripsi, float $debit, float $kredit): void
    {
        $lastSaldo = $this->getLastSaldoByAkun($akun);
        $saldoBaru = $lastSaldo + $debit - $kredit;

        DB::table('arus_kas')->insert([
            'akun' => $akun,
            'tanggal' => $tanggal,
            'jenis_transaksi' => $debit > 0 ? 'Masuk' : 'Keluar',
            'kategori' => $kategori,
            'deskripsi' => $deskripsi,
            'uang_masuk' => $debit,
            'uang_keluar' => $kredit,
            'saldo' => $saldoBaru,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
