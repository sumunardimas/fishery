<?php

namespace App\Http\Controllers;

use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KeuanganController extends Controller
{
    private const MODAL_BORROW_CATEGORIES = [
        'Pinjam Modal Bu Uum',
        'Pinjam Modal Jons Group',
    ];

    private const JONS_GROUP_PAYMENT_CATEGORY = 'Pembayaran Hutang Modal';

    private const EMPLOYEE_CASH_ADVANCE_CATEGORY = 'Kas Bon Pegawai';

    private const EMPLOYEE_CASH_ADVANCE_PAYMENT_CATEGORY = 'Pelunasan Kas Bon Pegawai';

    public function arusKas(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $today = Carbon::today();

        $start = !empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : $today->copy()->subDays(29);

        $end = !empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : $today->copy();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $rows = DB::table('arus_kas')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderByDesc('tanggal')
            ->orderByDesc('id_kas')
            ->get();

        $summary = [
            'total_masuk' => (float) $rows->sum('uang_masuk'),
            'total_keluar' => (float) $rows->sum('uang_keluar'),
            'net' => (float) $rows->sum('uang_masuk') - (float) $rows->sum('uang_keluar'),
        ];

        $currentBalance = $this->getLastSaldoByAkun('kas') + $this->getLastSaldoByAkun('bank');

        return view('keuangan.arus_kas.index', compact(
            'rows',
            'startDate',
            'endDate',
            'currentBalance',
            'summary'
        ));
    }

    public function labaRugi(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $today = Carbon::today();

        $start = ! empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : $today->copy()->subDays(29);

        $end = ! empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : $today->copy();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $dailyRevenue = DB::table('penjualan')
            ->whereBetween('tanggal_penjualan', [$startDate, $endDate])
            ->selectRaw('DATE(tanggal_penjualan) as tanggal, SUM(total_harga) as total_revenue')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $dailyFromDb = DB::table('arus_kas')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('DATE(tanggal) as tanggal, SUM(uang_keluar) as total_expenditure')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $rows = collect();
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->toDateString();
            $rows->push((object) [
                'tanggal' => $key,
                'total_revenue' => (float) ($dailyRevenue[$key]->total_revenue ?? 0),
                'total_expenditure' => (float) ($dailyFromDb[$key]->total_expenditure ?? 0),
            ]);
        }

        $totalRevenue = (float) $rows->sum('total_revenue');
        $totalExpenditure = (float) $rows->sum('total_expenditure');
        $net = $totalRevenue - $totalExpenditure;

        if ($net > 0) {
            $statusLabel = 'Profit';
        } elseif ($net < 0) {
            $statusLabel = 'Loss';
        } else {
            $statusLabel = 'Break Even';
        }

        $profitLoss = [
            'total_revenue' => $totalRevenue,
            'total_expenditure' => $totalExpenditure,
            'net' => $net,
            'status' => $statusLabel,
        ];

        return view('keuangan.laba_rugi.index', compact(
            'rows',
            'startDate',
            'endDate',
            'profitLoss'
        ));
    }

    public function selisihBongkar(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $today = Carbon::today();

        $start = ! empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : $today->copy()->subDays(29);

        $end = ! empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : $today->copy();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $salesByDate = DB::table('penjualan')
            ->join('penjualan_items', 'penjualan.id_penjualan', '=', 'penjualan_items.id_penjualan')
            ->whereBetween('penjualan.tanggal_penjualan', [$startDate, $endDate])
            ->selectRaw('DATE(penjualan.tanggal_penjualan) as tanggal, SUM(penjualan_items.berat) as berat_penjualan')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $auctionByDate = DB::table('lelang_ikan_harian')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('DATE(tanggal) as tanggal, SUM(berat_lelang) as berat_lelang')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $rows = collect();
        $chartLabels = [];
        $chartSales = [];
        $chartAuction = [];

        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->toDateString();

            $salesWeight = (float) ($salesByDate[$key]->berat_penjualan ?? 0);
            $auctionWeight = (float) ($auctionByDate[$key]->berat_lelang ?? 0);
            $selisih = $salesWeight - $auctionWeight;

            $rows->push((object) [
                'tanggal' => $key,
                'berat_penjualan' => $salesWeight,
                'berat_lelang' => $auctionWeight,
                'selisih' => $selisih,
            ]);

            $chartLabels[] = $date->format('d M');
            $chartSales[] = $salesWeight;
            $chartAuction[] = $auctionWeight;
        }

        $summary = [
            'total_penjualan' => (float) $rows->sum('berat_penjualan'),
            'total_lelang' => (float) $rows->sum('berat_lelang'),
            'total_selisih' => (float) $rows->sum('selisih'),
        ];

        return view('keuangan.selisih_bongkar.index', compact(
            'rows',
            'startDate',
            'endDate',
            'summary',
            'chartLabels',
            'chartSales',
            'chartAuction',
            'today'
        ));
    }

    public function storeBeratLelang(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'berat_lelang' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = DB::table('lelang_ikan_harian')
            ->whereDate('tanggal', $validated['tanggal'])
            ->first();

        if ($existing) {
            DB::table('lelang_ikan_harian')
                ->where('id_lelang_harian', $existing->id_lelang_harian)
                ->update([
                    'berat_lelang' => (float) $validated['berat_lelang'],
                    'keterangan' => $validated['keterangan'] ?? null,
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('lelang_ikan_harian')->insert([
                'tanggal' => $validated['tanggal'],
                'berat_lelang' => (float) $validated['berat_lelang'],
                'keterangan' => $validated['keterangan'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()
            ->route('keuangan.lap-selisih-bongkaran.index', [
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ])
            ->with('success', 'Berat lelang ikan berhasil disimpan.');
    }

    public function bayarPiutang(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_penjualan'   => ['required', 'integer', 'exists:penjualan,id_penjualan'],
            'bayar_tunai'    => ['nullable', 'numeric', 'min:0'],
            'bayar_transfer' => ['nullable', 'numeric', 'min:0'],
        ]);

        $idPenjualan = (int) $validated['id_penjualan'];
        $newKas      = (float) ($validated['bayar_tunai'] ?? 0);
        $newTransfer = (float) ($validated['bayar_transfer'] ?? 0);
        $newPayment  = $newKas + $newTransfer;

        if ($newPayment <= 0) {
            return response()->json(['message' => 'Masukkan jumlah pembayaran.'], 422);
        }

        $penjualan = DB::table('penjualan as p')
            ->leftJoin('master_customer as mc', 'p.id_customer', '=', 'mc.id_customer')
            ->where('p.id_penjualan', $idPenjualan)
            ->select([
                'p.id_penjualan', 'p.bayar_tunai', 'p.bayar_transfer',
                'p.piutang', 'p.total_harga',
                DB::raw('COALESCE(mc.nama_customer, p.pembeli) as nama_customer_display'),
            ])
            ->first();

        if (! $penjualan) {
            return response()->json(['message' => 'Transaksi tidak ditemukan.'], 404);
        }

        $currentPiutang = (float) $penjualan->piutang;

        if ($currentPiutang <= 0) {
            return response()->json(['message' => 'Transaksi ini sudah lunas.'], 422);
        }

        if ($newPayment > $currentPiutang + 0.01) {
            return response()->json([
                'message' => 'Pembayaran melebihi sisa piutang (Rp ' . number_format($currentPiutang, 2, ',', '.') . ').',
            ], 422);
        }

        $newPiutang       = round(max(0, $currentPiutang - $newPayment), 2);
        $newBayarTunai    = (float) $penjualan->bayar_tunai + $newKas;
        $newBayarTransfer = (float) $penjualan->bayar_transfer + $newTransfer;
        $newDiterima      = $newBayarTunai + $newBayarTransfer;
        $statusPembayaran = $newPiutang <= 0 ? 'lunas' : 'piutang';
        $invNo            = 'INV-' . str_pad($idPenjualan, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use (
            $idPenjualan, $penjualan, $invNo,
            $newBayarTunai, $newBayarTransfer, $newPayment, $newPiutang, $statusPembayaran,
            $newKas, $newTransfer
        ) {
            DB::table('penjualan')->where('id_penjualan', $idPenjualan)->update([
                'bayar_tunai'       => $newBayarTunai,
                'bayar_transfer'    => $newBayarTransfer,
                'piutang'           => $newPiutang,
                'status_pembayaran' => $statusPembayaran,
                'updated_at'        => now(),
            ]);

            if ($newKas > 0) {
                $this->postArusKas(
                    akun: 'kas',
                    tanggal: Carbon::today()->toDateString(),
                    kategori: 'Pelunasan Piutang',
                    deskripsi: 'Pelunasan piutang '.$penjualan->nama_customer_display.' ('.$invNo.'). Diterima kas Rp '.number_format($newKas, 2, ',', '.').'; Sisa Piutang Rp '.number_format($newPiutang, 2, ',', '.'),
                    debit: $newKas,
                    kredit: 0
                );
            }

            if ($newTransfer > 0) {
                $this->postArusKas(
                    akun: 'bank',
                    tanggal: Carbon::today()->toDateString(),
                    kategori: 'Pelunasan Piutang',
                    deskripsi: 'Pelunasan piutang '.$penjualan->nama_customer_display.' ('.$invNo.'). Diterima transfer Rp '.number_format($newTransfer, 2, ',', '.').'; Sisa Piutang Rp '.number_format($newPiutang, 2, ',', '.'),
                    debit: $newTransfer,
                    kredit: 0
                );
            }
        });

        return response()->json([
            'status_pembayaran'      => $statusPembayaran,
            'new_piutang'            => $newPiutang,
            'new_piutang_formatted'  => number_format($newPiutang, 2, ',', '.'),
            'new_diterima'           => $newDiterima,
            'new_diterima_formatted' => number_format($newDiterima, 2, ',', '.'),
        ]);
    }

    public function kas(Request $request): View
    {
        return $this->renderCashLedger($request, 'kas', 'cash.kas', 'Kas');
    }

    public function bank(Request $request): View
    {
        return $this->renderCashLedger($request, 'bank', 'cash.bank', 'Bank');
    }

    public function jonsGroupDebt(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $today = Carbon::today();

        $start = ! empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : $today->copy()->subDays(89);

        $end = ! empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : $today->copy();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $rows = DB::table('jons_group_debts')
            ->whereBetween('tanggal_pinjam', [$startDate, $endDate])
            ->orderByDesc('tanggal_pinjam')
            ->orderByDesc('id_jons_group_debt')
            ->get();

        $summary = [
            'total_hutang' => (float) $rows->sum('sisa_hutang'),
            'jumlah_transaksi' => $rows->count(),
            'total_pinjaman' => (float) $rows->sum('nominal_awal'),
            'total_terbayar' => (float) $rows->sum(fn ($row) => (float) $row->nominal_awal - (float) $row->sisa_hutang),
        ];

        return view('keuangan.jons_group_debts.index', compact(
            'rows',
            'summary',
            'startDate',
            'endDate'
        ));
    }

    public function kasBonPegawai(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $today = Carbon::today();

        $start = ! empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : $today->copy()->subDays(89);

        $end = ! empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : $today->copy();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $rows = DB::table('kas_bon_pegawai')
            ->whereBetween('tanggal_pinjam', [$startDate, $endDate])
            ->orderByDesc('tanggal_pinjam')
            ->orderByDesc('id_kas_bon_pegawai')
            ->get();

        $summary = [
            'total_piutang' => (float) $rows->sum('sisa_piutang'),
            'jumlah_transaksi' => $rows->count(),
            'total_pinjaman' => (float) $rows->sum('nominal_awal'),
            'total_dibayar' => (float) $rows->sum(fn ($row) => (float) $row->nominal_awal - (float) $row->sisa_piutang),
        ];

        $byPegawai = $rows
            ->groupBy('nama_pegawai')
            ->map(fn ($items) => (object) [
                'nama' => $items->first()->nama_pegawai,
                'jumlah' => $items->count(),
                'total_piutang' => (float) $items->sum('sisa_piutang'),
            ])
            ->sortByDesc('total_piutang')
            ->values();

        return view('keuangan.kas_bon_pegawai.index', compact(
            'rows',
            'summary',
            'byPegawai',
            'startDate',
            'endDate'
        ));
    }

    public function storeCashTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'akun' => ['required', 'in:kas,bank'],
            'tanggal' => ['required', 'date'],
            'kategori' => ['required', 'string', 'max:120'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'debit' => ['nullable', 'numeric', 'min:0'],
            'kredit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $debit = (float) ($validated['debit'] ?? 0);
        $kredit = (float) ($validated['kredit'] ?? 0);

        if (($debit <= 0 && $kredit <= 0) || ($debit > 0 && $kredit > 0)) {
            return back()
                ->withInput()
                ->withErrors(['nominal' => 'Isi salah satu: Debit atau Kredit (tidak boleh keduanya).']);
        }

        if (in_array($validated['kategori'], self::MODAL_BORROW_CATEGORIES, true)) {
            if ($kredit <= 0 || $debit > 0) {
                return back()
                    ->withInput()
                    ->withErrors(['kategori' => 'Pinjam Modal Bu Uum dan Pinjam Modal Jons Group harus dicatat sebagai kredit.']);
            }
        }

        if ($validated['kategori'] === self::EMPLOYEE_CASH_ADVANCE_CATEGORY) {
            if ($kredit <= 0 || $debit > 0) {
                return back()
                    ->withInput()
                    ->withErrors(['kategori' => 'Kas Bon Pegawai harus dicatat sebagai kredit.']);
            }

            if (blank($validated['deskripsi'] ?? null)) {
                return back()
                    ->withInput()
                    ->withErrors(['deskripsi' => 'Nama pegawai wajib diisi untuk Kas Bon Pegawai.']);
            }
        }

        DB::transaction(function () use ($validated, $debit, $kredit) {
            $arusKasId = $this->postArusKas(
                akun: $validated['akun'],
                tanggal: $validated['tanggal'],
                kategori: $validated['kategori'],
                deskripsi: $validated['deskripsi'] ?? '-',
                debit: $debit,
                kredit: $kredit
            );

            if (in_array($validated['kategori'], self::MODAL_BORROW_CATEGORIES, true)) {
                $this->recordJonsGroupDebt(
                    arusKasId: $arusKasId,
                    tanggal: $validated['tanggal'],
                    akun: $validated['akun'],
                    deskripsi: $validated['deskripsi'] ?? $validated['kategori'],
                    nominal: $kredit
                );
            }

            if ($validated['kategori'] === self::EMPLOYEE_CASH_ADVANCE_CATEGORY) {
                $this->recordKasBonPegawai(
                    arusKasId: $arusKasId,
                    tanggal: $validated['tanggal'],
                    akun: $validated['akun'],
                    namaPegawai: trim((string) $validated['deskripsi']),
                    nominal: $kredit
                );
            }
        });

        $message = 'Transaksi '.strtoupper($validated['akun']).' berhasil disimpan.';

        if (in_array($validated['kategori'], self::MODAL_BORROW_CATEGORIES, true)) {
            $message .= ' Hutang Modal ikut tercatat.';
        }

        if ($validated['kategori'] === self::EMPLOYEE_CASH_ADVANCE_CATEGORY) {
            $message .= ' Piutang Kas Bon Pegawai ikut tercatat.';
        }

        return back()->with('success', $message);
    }

    public function bayarJonsGroupDebt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_jons_group_debt' => ['required', 'integer', 'exists:jons_group_debts,id_jons_group_debt'],
            'akun_pembayaran' => ['required', 'in:kas,bank'],
            'nominal' => ['required', 'numeric', 'gt:0'],
        ]);

        $nominalBayar = round((float) $validated['nominal'], 2);
        $response = [];

        DB::transaction(function () use ($validated, $nominalBayar, &$response) {
            $debt = DB::table('jons_group_debts')
                ->where('id_jons_group_debt', $validated['id_jons_group_debt'])
                ->lockForUpdate()
                ->first();

            if (! $debt) {
                throw ValidationException::withMessages([
                    'id_jons_group_debt' => 'Data hutang tidak ditemukan.',
                ]);
            }

            $remainingDebt = round((float) $debt->sisa_hutang, 2);

            if ($nominalBayar > $remainingDebt + 0.01) {
                throw ValidationException::withMessages([
                    'nominal' => 'Pembayaran melebihi sisa hutang.',
                ]);
            }

            $newRemainingDebt = max(0, round($remainingDebt - $nominalBayar, 2));
            $kodeHutang = 'HJG-'.str_pad((string) $debt->id_jons_group_debt, 5, '0', STR_PAD_LEFT);

            $this->postArusKas(
                akun: $validated['akun_pembayaran'],
                tanggal: Carbon::today()->toDateString(),
                kategori: self::JONS_GROUP_PAYMENT_CATEGORY,
                deskripsi: 'Pembayaran hutang modal '.$kodeHutang.' sebesar Rp '.number_format($nominalBayar, 2, ',', '.').'. Sisa hutang Rp '.number_format($newRemainingDebt, 2, ',', '.').'.',
                debit: 0,
                kredit: $nominalBayar
            );

            if ($newRemainingDebt < 0.01) {
                DB::table('jons_group_debts')
                    ->where('id_jons_group_debt', $debt->id_jons_group_debt)
                    ->delete();
            } else {
                DB::table('jons_group_debts')
                    ->where('id_jons_group_debt', $debt->id_jons_group_debt)
                    ->update([
                        'sisa_hutang' => $newRemainingDebt,
                        'updated_at' => now(),
                    ]);
            }

            $response = [
                'status' => $newRemainingDebt < 0.01 ? 'lunas' : 'parsial',
                'new_sisa_hutang' => $newRemainingDebt,
                'new_sisa_hutang_formatted' => number_format($newRemainingDebt, 2, ',', '.'),
            ];
        });

        return response()->json($response);
    }

    public function bayarKasBonPegawai(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_kas_bon_pegawai' => ['required', 'integer', 'exists:kas_bon_pegawai,id_kas_bon_pegawai'],
            'akun_pembayaran' => ['required', 'in:kas,bank'],
            'nominal' => ['required', 'numeric', 'gt:0'],
        ]);

        $nominalBayar = round((float) $validated['nominal'], 2);
        $response = [];

        DB::transaction(function () use ($validated, $nominalBayar, &$response) {
            $advance = DB::table('kas_bon_pegawai')
                ->where('id_kas_bon_pegawai', $validated['id_kas_bon_pegawai'])
                ->lockForUpdate()
                ->first();

            if (! $advance) {
                throw ValidationException::withMessages([
                    'id_kas_bon_pegawai' => 'Data kas bon pegawai tidak ditemukan.',
                ]);
            }

            $remainingReceivable = round((float) $advance->sisa_piutang, 2);

            if ($nominalBayar > $remainingReceivable + 0.01) {
                throw ValidationException::withMessages([
                    'nominal' => 'Pembayaran melebihi sisa kas bon pegawai.',
                ]);
            }

            $newRemainingReceivable = max(0, round($remainingReceivable - $nominalBayar, 2));
            $kodeKasBon = 'KBP-'.str_pad((string) $advance->id_kas_bon_pegawai, 5, '0', STR_PAD_LEFT);

            $this->postArusKas(
                akun: $validated['akun_pembayaran'],
                tanggal: Carbon::today()->toDateString(),
                kategori: self::EMPLOYEE_CASH_ADVANCE_PAYMENT_CATEGORY,
                deskripsi: 'Pelunasan kas bon pegawai '.$advance->nama_pegawai.' ('.$kodeKasBon.') sebesar Rp '.number_format($nominalBayar, 2, ',', '.').'. Sisa piutang Rp '.number_format($newRemainingReceivable, 2, ',', '.').'.',
                debit: $nominalBayar,
                kredit: 0
            );

            if ($newRemainingReceivable < 0.01) {
                DB::table('kas_bon_pegawai')
                    ->where('id_kas_bon_pegawai', $advance->id_kas_bon_pegawai)
                    ->delete();
            } else {
                DB::table('kas_bon_pegawai')
                    ->where('id_kas_bon_pegawai', $advance->id_kas_bon_pegawai)
                    ->update([
                        'sisa_piutang' => $newRemainingReceivable,
                        'updated_at' => now(),
                    ]);
            }

            $response = [
                'status' => $newRemainingReceivable < 0.01 ? 'lunas' : 'parsial',
                'new_sisa_piutang' => $newRemainingReceivable,
                'new_sisa_piutang_formatted' => number_format($newRemainingReceivable, 2, ',', '.'),
            ];
        });

        return response()->json($response);
    }

    public function piutang(Request $request): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
            'status'     => ['nullable', 'in:semua,piutang'],
        ]);

        $today = Carbon::today();

        $start = ! empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : $today->copy()->subDays(89);

        $end = ! empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : $today->copy();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $startDate = $start->toDateString();
        $endDate   = $end->toDateString();
        $status    = $validated['status'] ?? 'piutang';

        $query = DB::table('penjualan as p')
            ->leftJoin('master_customer as mc', 'p.id_customer', '=', 'mc.id_customer')
            ->whereBetween('p.tanggal_penjualan', [$startDate, $endDate])
            ->select([
                'p.id_penjualan',
                'p.tanggal_penjualan',
                'p.total_harga',
                DB::raw('COALESCE(p.bayar_tunai, 0) + COALESCE(p.bayar_transfer, 0) as total_diterima'),
                'p.piutang',
                'p.status_pembayaran',
                DB::raw('COALESCE(mc.nama_customer, p.pembeli) as nama_customer_display'),
            ])
            ->orderByDesc('p.tanggal_penjualan')
            ->orderByDesc('p.id_penjualan');

        if ($status === 'piutang') {
            $query->where('p.piutang', '>', 0);
        }

        $rows = $query->get();

        $summary = [
            'total_piutang'    => (float) $rows->sum('piutang'),
            'jumlah_transaksi' => $rows->count(),
            'total_tagihan'    => (float) $rows->sum('total_harga'),
            'total_diterima'   => (float) $rows->sum('total_diterima'),
        ];

        $byCustomer = $rows
            ->groupBy('nama_customer_display')
            ->map(fn ($items) => (object) [
                'nama'          => $items->first()->nama_customer_display,
                'jumlah'        => $items->count(),
                'total_piutang' => (float) $items->sum('piutang'),
            ])
            ->sortByDesc('total_piutang')
            ->values();

        return view('keuangan.piutang.index', compact(
            'rows',
            'startDate',
            'endDate',
            'status',
            'summary',
            'byCustomer',
        ));
    }

    private function renderCashLedger(Request $request, string $akun, string $view, string $title): View
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $today = Carbon::today();

        $start = ! empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : $today->copy()->subDays(29);

        $end = ! empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : $today->copy();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $rows = DB::table('arus_kas')
            ->where('akun', $akun)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderByDesc('tanggal')
            ->orderByDesc('id_kas')
            ->get();

        $summary = [
            'total_debit' => (float) $rows->sum('uang_masuk'),
            'total_kredit' => (float) $rows->sum('uang_keluar'),
            'net' => (float) $rows->sum('uang_masuk') - (float) $rows->sum('uang_keluar'),
            'saldo_terkini' => $this->getLastSaldoByAkun($akun),
        ];

        return view($view, compact('rows', 'startDate', 'endDate', 'summary', 'akun', 'title'));
    }

    private function getLastSaldoByAkun(string $akun): float
    {
        return (float) (DB::table('arus_kas')
            ->where('akun', $akun)
            ->orderByDesc('id_kas')
            ->value('saldo') ?? 0);
    }

    private function recordJonsGroupDebt(int $arusKasId, string $tanggal, string $akun, string $deskripsi, float $nominal): void
    {
        DB::table('jons_group_debts')->insert([
            'id_kas_sumber' => $arusKasId,
            'tanggal_pinjam' => $tanggal,
            'akun_penerimaan' => $akun,
            'deskripsi' => $deskripsi,
            'nominal_awal' => $nominal,
            'sisa_hutang' => $nominal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function recordKasBonPegawai(int $arusKasId, string $tanggal, string $akun, string $namaPegawai, float $nominal): void
    {
        DB::table('kas_bon_pegawai')->insert([
            'id_kas_sumber' => $arusKasId,
            'tanggal_pinjam' => $tanggal,
            'akun_pengeluaran' => $akun,
            'nama_pegawai' => $namaPegawai,
            'nominal_awal' => $nominal,
            'sisa_piutang' => $nominal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function postArusKas(string $akun, string $tanggal, string $kategori, string $deskripsi, float $debit, float $kredit): int
    {
        $lastSaldo = $this->getLastSaldoByAkun($akun);
        $saldoBaru = $lastSaldo + $debit - $kredit;

        return (int) DB::table('arus_kas')->insertGetId([
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
