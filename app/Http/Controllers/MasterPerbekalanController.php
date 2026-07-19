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
        $defaultEnd = Carbon::today()->toDateString();

        $startDate = $request->input('start_date', $defaultStart);
        $endDate = $request->input('end_date', $defaultEnd);

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
                'mp.limit_minimal',
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
            'limit_minimal' => ['nullable', 'numeric', 'min:0'],
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
            'limit_minimal' => ['nullable', 'numeric', 'min:0'],
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
            'mode_transaksi' => ['nullable', 'in:normal,import_awal'],
            'akun_pembayaran' => ['nullable', 'in:kas,bank,hutang'],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'harga_satuan' => ['nullable', 'numeric', 'min:0'],
            'sumber_tujuan' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $isIn = ($data['jenis_transaksi'] ?? null) === 'in';
        $isImportAwal = ($data['mode_transaksi'] ?? 'normal') === 'import_awal';

        if ($isIn && ! $isImportAwal && empty($data['akun_pembayaran'])) {
            throw ValidationException::withMessages([
                'akun_pembayaran' => 'Akun pembayaran wajib dipilih untuk transaksi IN pembelian.',
            ]);
        }

        if ($isIn && ! $isImportAwal && (float) ($data['harga_satuan'] ?? 0) <= 0) {
            throw ValidationException::withMessages([
                'harga_satuan' => 'Harga satuan wajib diisi dan harus lebih dari 0 untuk transaksi IN.',
            ]);
        }

        if (! $isIn) {
            $data['mode_transaksi'] = 'normal';
        }

        DB::transaction(function () use ($data) {
            $jumlah = (float) $data['jumlah'];
            $idBarang = (int) $data['id_barang'];
            $isImportAwal = ($data['mode_transaksi'] ?? 'normal') === 'import_awal';

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
            if ($isImportAwal && (float) ($hargaSatuan ?? 0) <= 0) {
                $hargaSatuan = null;
            }

            $totalHarga = $hargaSatuan !== null ? $jumlah * (float) $hargaSatuan : 0;
            $akunPembayaran = $isImportAwal ? null : ($data['akun_pembayaran'] ?? null);

            $transaction = PerbekalanTransaction::create([
                'tanggal_transaksi' => $data['tanggal_transaksi'],
                'id_barang' => $idBarang,
                'id_pelayaran' => null,
                'jenis_transaksi' => $data['jenis_transaksi'],
                'akun_pembayaran' => $akunPembayaran,
                'jumlah' => $jumlah,
                'harga_satuan' => $hargaSatuan,
                'total_harga' => $totalHarga,
                'nominal_terbayar_hutang' => 0,
                'sumber_tujuan' => $data['sumber_tujuan'] ?? null,
                'keterangan' => $this->buildPerbekalanKeterangan(
                    $data['keterangan'] ?? null,
                    $isImportAwal
                ),
            ]);

            // Purchase IN with price will reduce selected account balance.
            if ($data['jenis_transaksi'] === 'in' && ! $isImportAwal && $totalHarga > 0 && ! empty($akunPembayaran)) {
                $namaBarang = $this->getPerbekalanName($idBarang);
                $unit = $this->getPerbekalanUnit($idBarang);

                if ($akunPembayaran === 'hutang') {
                    $this->postArusKas(
                        akun: 'hutang',
                        tanggal: $data['tanggal_transaksi'],
                        kategori: 'Hutang Pembelian Perbekalan',
                        deskripsi: 'Hutang pembelian perbekalan #'.$transaction->id_transaction.' '.$namaBarang.' sebesar Rp '.number_format((float) $totalHarga, 2, ',', '.').'.',
                        debit: (float) $totalHarga,
                        kredit: 0
                    );
                } else {
                    $this->postArusKas(
                        akun: $akunPembayaran,
                        tanggal: $data['tanggal_transaksi'],
                        kategori: 'Pembelian Perbekalan',
                        deskripsi: 'Pembelian perbekalan '.$namaBarang.' ('.number_format($jumlah, 2, ',', '.').' '.$unit.')'.(! empty($data['sumber_tujuan']) ? ' dari '.$data['sumber_tujuan'] : ''),
                        debit: 0,
                        kredit: (float) $totalHarga
                    );
                }
            }
        });

        return redirect()->route('master.perbekalan.transaksi', ['show_item' => (int) $data['id_barang']])
            ->with('success', 'Transaksi perbekalan berhasil disimpan.');
    }

    public function destroyTransaction(Request $request, PerbekalanTransaction $transaction): RedirectResponse
    {
        $selectedItemId = $request->integer('show_item');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($transaction->jenis_transaksi === 'out' && (int) ($transaction->id_pelayaran ?? 0) > 0) {
            return redirect()->route('master.perbekalan.history', array_filter([
                'show_item' => $selectedItemId > 0 ? $selectedItemId : null,
            ]))->withErrors([
                'message' => 'Transaksi stok keluar dari penutupan pelayaran tidak dapat dihapus dari menu ini. Buka data pelayaran terkait untuk audit riwayatnya.',
            ]);
        }

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
                && in_array((string) $transaction->akun_pembayaran, ['kas', 'bank', 'hutang'], true)
            ) {
                if ((string) $transaction->akun_pembayaran === 'hutang') {
                    $nominalTerbayarHutang = round((float) ($transaction->nominal_terbayar_hutang ?? 0), 2);
                    if ($nominalTerbayarHutang > 0.009) {
                        throw ValidationException::withMessages([
                            'message' => 'Transaksi hutang yang sudah dibayar tidak bisa dihapus. Batalkan pembayaran hutang terlebih dahulu lewat koreksi keuangan.',
                        ]);
                    }

                    $this->postArusKas(
                        akun: 'hutang',
                        tanggal: now()->toDateString(),
                        kategori: 'Pembatalan Hutang Pembelian Perbekalan',
                        deskripsi: 'Pembatalan transaksi hutang perbekalan #'.$transaction->id_transaction,
                        debit: 0,
                        kredit: (float) $transaction->total_harga
                    );
                } else {
                    $this->postArusKas(
                        akun: (string) $transaction->akun_pembayaran,
                        tanggal: now()->toDateString(),
                        kategori: 'Pembatalan Pembelian Perbekalan',
                        deskripsi: 'Pembatalan transaksi perbekalan #'.$transaction->id_transaction,
                        debit: (float) $transaction->total_harga,
                        kredit: 0
                    );
                }
            }

            $transaction->delete();
        });

        $redirectParams = [];
        if ($selectedItemId > 0) {
            $redirectParams['show_item'] = $selectedItemId;
        }
        if (! empty($startDate)) {
            $redirectParams['start_date'] = $startDate;
        }
        if (! empty($endDate)) {
            $redirectParams['end_date'] = $endDate;
        }

        return redirect()->route('master.perbekalan.history', $redirectParams)
            ->with('success', 'Transaksi berhasil dihapus dan stok telah disesuaikan.');
    }

    public function payDebt(Request $request, PerbekalanTransaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'akun_pembayaran' => ['required', 'in:kas,bank'],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'show_item' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        DB::transaction(function () use ($transaction, $data) {
            /** @var PerbekalanTransaction|null $locked */
            $locked = PerbekalanTransaction::query()
                ->whereKey($transaction->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw ValidationException::withMessages([
                    'message' => 'Data transaksi tidak ditemukan.',
                ]);
            }

            $isDebtTransaction = (string) $locked->jenis_transaksi === 'in'
                && (string) $locked->akun_pembayaran === 'hutang'
                && (float) $locked->total_harga > 0;

            if (! $isDebtTransaction) {
                throw ValidationException::withMessages([
                    'message' => 'Transaksi ini bukan transaksi hutang yang bisa dibayar.',
                ]);
            }

            $alreadyPaid = round((float) ($locked->nominal_terbayar_hutang ?? 0), 2);
            $remainingDebt = round(max(0, (float) $locked->total_harga - $alreadyPaid), 2);
            $paymentAmount = round((float) $data['nominal'], 2);

            if ($paymentAmount > $remainingDebt + 0.01) {
                throw ValidationException::withMessages([
                    'nominal' => 'Nominal melebihi sisa hutang transaksi ini.',
                ]);
            }

            $newPaid = round($alreadyPaid + $paymentAmount, 2);
            $newRemainingDebt = round(max(0, (float) $locked->total_harga - $newPaid), 2);

            $this->postArusKas(
                akun: (string) $data['akun_pembayaran'],
                tanggal: now()->toDateString(),
                kategori: 'Pelunasan Hutang Pembelian Perbekalan',
                deskripsi: 'Bayar hutang perbekalan #'.$locked->id_transaction.' sebesar Rp '.number_format($paymentAmount, 2, ',', '.').'. Sisa hutang Rp '.number_format($newRemainingDebt, 2, ',', '.').'.',
                debit: 0,
                kredit: $paymentAmount
            );

            $this->postArusKas(
                akun: 'hutang',
                tanggal: now()->toDateString(),
                kategori: 'Pelunasan Hutang Pembelian Perbekalan',
                deskripsi: 'Pengurangan hutang perbekalan #'.$locked->id_transaction.' sebesar Rp '.number_format($paymentAmount, 2, ',', '.').'.',
                debit: 0,
                kredit: $paymentAmount
            );

            $locked->nominal_terbayar_hutang = $newPaid;
            $locked->save();
        });

        $redirectParams = array_filter([
            'show_item' => ! empty($data['show_item']) ? (int) $data['show_item'] : null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        return redirect()->route('master.perbekalan.history', $redirectParams)
            ->with('success', 'Pembayaran hutang perbekalan berhasil disimpan.');
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

    private function buildPerbekalanKeterangan(?string $keterangan, bool $isImportAwal): ?string
    {
        $keterangan = trim((string) ($keterangan ?? ''));

        if (! $isImportAwal) {
            return $keterangan !== '' ? $keterangan : null;
        }

        $tag = '[IMPORT STOK AWAL TANPA KAS]';

        if ($keterangan === '') {
            return $tag;
        }

        if (str_starts_with($keterangan, $tag)) {
            return $keterangan;
        }

        return $tag.' '.$keterangan;
    }
}
