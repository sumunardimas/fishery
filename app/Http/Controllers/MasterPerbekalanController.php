<?php

namespace App\Http\Controllers;

use App\Models\MasterPerbekalan;
use App\Models\PerbekalanStock;
use App\Models\PerbekalanTransaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MasterPerbekalanController extends Controller
{
    public function index(Request $request): View
    {
        $selectedItemId = $request->integer('show_item');

        $items = $this->getPerbekalanItems();

        return view('master.perbekalan.index', compact(
            'items',
            'selectedItemId'
        ));
    }

    public function transaksi(Request $request): View
    {
        $selectedItemId = $request->integer('show_item');

        $items = $this->getPerbekalanItems();

        return view('master.perbekalan.transaksi', compact(
            'items',
            'selectedItemId'
        ));
    }

    public function history(Request $request): View
    {
        $selectedItemId = $request->integer('show_item');

        $defaultStart = Carbon::today()->subDays(29)->toDateString();
        $defaultEnd   = Carbon::today()->toDateString();

        $startDate = $request->input('start_date', $defaultStart);
        $endDate   = $request->input('end_date', $defaultEnd);

        $items = $this->getPerbekalanItems();

        $selectedItem = null;

        $transactionsQuery = DB::table('perbekalan_transaction as pt')
            ->join('master_perbekalan as mp', 'mp.id_barang', '=', 'pt.id_barang')
            ->whereDate('pt.tanggal_transaksi', '>=', $startDate)
            ->whereDate('pt.tanggal_transaksi', '<=', $endDate)
            ->select('pt.*', 'mp.nama_barang', 'mp.satuan')
            ->orderByDesc('pt.tanggal_transaksi')
            ->orderByDesc('pt.id_transaction');

        if ($selectedItemId > 0) {
            $selectedItem = MasterPerbekalan::query()->find($selectedItemId);

            if ($selectedItem) {
                $transactionsQuery->where('pt.id_barang', (int) $selectedItem->id_barang);
            } else {
                $selectedItemId = 0;
            }
        }

        $transactions = $transactionsQuery->get();

        return view('master.perbekalan.history', compact(
            'items',
            'selectedItem',
            'transactions',
            'selectedItemId',
            'startDate',
            'endDate'
        ));
    }

    private function getPerbekalanItems()
    {
        return DB::table('master_perbekalan as mp')
            ->leftJoin('perbekalan_stock as ps', 'ps.id_barang', '=', 'mp.id_barang')
            ->select(
                'mp.id_barang',
                'mp.nama_barang',
                'mp.satuan',
                DB::raw('COALESCE(ps.stok_aktual, 0) as stok_aktual')
            )
            ->orderBy('mp.nama_barang')
            ->get();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255', 'unique:master_perbekalan,nama_barang'],
            'satuan' => ['required', 'string', 'max:100'],
        ]);

        MasterPerbekalan::create($data);

        return redirect()->route('master.perbekalan.index')->with('success', 'Master perbekalan berhasil ditambahkan.');
    }

    public function update(Request $request, MasterPerbekalan $perbekalan): RedirectResponse
    {
        $data = $request->validate([
            'nama_barang' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_perbekalan', 'nama_barang')->ignore($perbekalan->id_barang, 'id_barang'),
            ],
            'satuan' => ['required', 'string', 'max:100'],
        ]);

        $perbekalan->update($data);

        return redirect()->route('master.perbekalan.index')->with('success', 'Master perbekalan berhasil diperbarui.');
    }

    public function destroy(MasterPerbekalan $perbekalan): RedirectResponse
    {
        $idBarang = (int) $perbekalan->id_barang;

        $usageMap = [
            'perbekalan' => 'perbekalan',
            'perbekalan_transaction' => 'transaksi perbekalan',
            'pembelian_barang' => 'pembelian barang',
            'pemakaian_barang_kantor' => 'pemakaian barang kantor',
            'sisa_trip' => 'sisa trip',
            'perbekalan_pelayaran' => 'perbekalan pelayaran',
        ];

        $usedIn = [];

        foreach ($usageMap as $table => $label) {
            if (DB::table($table)->where('id_barang', $idBarang)->exists()) {
                $usedIn[] = $label;
            }
        }

        if ($usedIn !== []) {
            return redirect()->route('master.perbekalan.index')->withErrors([
                'message' => 'Data tidak bisa dihapus karena sudah digunakan pada: '.implode(', ', $usedIn).'.',
            ]);
        }

        DB::table('perbekalan_stock')->where('id_barang', $idBarang)->delete();

        $perbekalan->delete();

        return redirect()->route('master.perbekalan.index')->with('success', 'Master perbekalan berhasil dihapus.');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal_transaksi' => ['required', 'date'],
            'id_barang' => ['required', 'integer', 'exists:master_perbekalan,id_barang'],
            'jenis_transaksi' => ['required', 'in:in,out'],
            'akun_pembayaran' => ['nullable', 'in:kas,bank', 'required_if:jenis_transaksi,in'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'harga_satuan' => ['nullable', 'numeric', 'min:0', 'required_if:jenis_transaksi,in'],
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
            $idBarang = (int) $data['id_barang'];

            $stock = PerbekalanStock::query()
                ->where('id_barang', $idBarang)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = PerbekalanStock::create([
                    'id_barang' => $idBarang,
                    'stok_aktual' => 0,
                ]);
            }

            $stokSaatIni = (float) $stock->stok_aktual;

            if ($data['jenis_transaksi'] === 'out' && $stokSaatIni < $jumlah) {
                throw ValidationException::withMessages([
                    'jumlah' => 'Stok tidak mencukupi. Stok tersedia: '
                        .number_format($stokSaatIni, 2, ',', '.')
                        .' '
                        .$this->getPerbekalanUnit($idBarang)
                        .'.',
                ]);
            }

            $stock->stok_aktual = $data['jenis_transaksi'] === 'in'
                ? $stokSaatIni + $jumlah
                : $stokSaatIni - $jumlah;
            $stock->save();

            $hargaSatuan = $data['harga_satuan'] ?? null;
            $totalHarga = $hargaSatuan !== null ? $jumlah * (float) $hargaSatuan : 0;

            PerbekalanTransaction::create([
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'id_barang' => $idBarang,
                'jenis_transaksi' => $data['jenis_transaksi'],
                'akun_pembayaran' => $data['akun_pembayaran'] ?? null,
                'jumlah' => $jumlah,
                'harga_satuan' => $hargaSatuan,
                'total_harga' => $totalHarga,
                'sumber_tujuan' => $data['sumber_tujuan'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            // Purchase IN with price will reduce selected account balance.
            if ($data['jenis_transaksi'] === 'in' && $totalHarga > 0 && ! empty($data['akun_pembayaran'])) {
                $namaBarang = $this->getPerbekalanName($idBarang);
                $unit = $this->getPerbekalanUnit($idBarang);

                $this->postArusKas(
                    akun: $data['akun_pembayaran'],
                    tanggal: $data['tanggal_transaksi'],
                    kategori: 'Pembelian Perbekalan',
                    deskripsi: 'Pembelian perbekalan '.$namaBarang.' ('.number_format($jumlah, 2, ',', '.').' '.$unit.')'.(!empty($data['sumber_tujuan']) ? ' dari '.$data['sumber_tujuan'] : ''),
                    debit: 0,
                    kredit: (float) $totalHarga
                );
            }
        });

        return redirect()->route('master.perbekalan.transaksi', ['show_item' => (int) $data['id_barang']])
            ->with('success', 'Transaksi perbekalan berhasil disimpan.');
    }

    public function destroyTransaction(Request $request, PerbekalanTransaction $transaction): RedirectResponse
    {
        $selectedItemId = $request->integer('show_item');

        DB::transaction(function () use ($transaction) {
            $stock = PerbekalanStock::query()
                ->where('id_barang', (int) $transaction->id_barang)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw ValidationException::withMessages([
                    'message' => 'Stok perbekalan tidak ditemukan. Tidak bisa membatalkan transaksi.',
                ]);
            }

            $stokSaatIni = (float) $stock->stok_aktual;
            $jumlah = (float) $transaction->jumlah;

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
                    kategori: 'Pembatalan Pembelian Perbekalan',
                    deskripsi: 'Pembatalan transaksi perbekalan #'.$transaction->id_transaction,
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

        return redirect()->route('master.perbekalan.history', $redirectParams)
            ->with('success', 'Transaksi berhasil dihapus dan stok telah disesuaikan.');
    }

    private function getPerbekalanUnit(int $idBarang): string
    {
        return (string) DB::table('master_perbekalan')
            ->where('id_barang', $idBarang)
            ->value('satuan');
    }

    private function getPerbekalanName(int $idBarang): string
    {
        return (string) DB::table('master_perbekalan')
            ->where('id_barang', $idBarang)
            ->value('nama_barang');
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

        if ($saldoBaru < -0.009) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'nominal' => 'Saldo '.strtoupper($akun).' tidak mencukupi. Saldo tersedia Rp '.number_format($lastSaldo, 2, ',', '.').', sehingga transaksi ini tidak boleh membuat saldo minus.',
            ]);
        }

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
