<?php

namespace App\Http\Controllers;

use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KeuanganController extends Controller
{
    private const TREASURY_TRANSFER_CATEGORY = 'Setoran Kas Induk';

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
        // Fetch all completed voyages with their capture data
        $completedVoyages = DB::table('pelayaran as p')
            ->join('kapal as k', 'p.id_kapal', '=', 'k.id_kapal')
            ->leftJoin('laporan_selisih_bongkaran as lsb', 'p.id_pelayaran', '=', 'lsb.id_pelayaran')
            ->where('p.status_pelayaran', 'selesai')
            ->select([
                'p.id_pelayaran',
                'k.nama_kapal',
                'p.tanggal_berangkat',
                'p.tanggal_selesai',
                'p.tanggal_tiba',
                'lsb.id_laporan',
                'lsb.total_berat_timbangan',
                'lsb.total_berat_catatan',
                'lsb.tanggal_laporan',
            ])
            ->orderByDesc('p.tanggal_selesai')
            ->get();

        $lawuhanByVoyage = DB::table('penjualan_item_lot_allocations as pila')
            ->join('penjualan_items as pi', 'pi.id_item', '=', 'pila.id_item')
            ->join('penjualan as pj', 'pj.id_penjualan', '=', 'pi.id_penjualan')
            ->where('pj.jenis_transaksi', 'lawuhan')
            ->whereNotNull('pila.id_pelayaran')
            ->selectRaw('pila.id_pelayaran, SUM(pila.berat_alokasi) as total_lawuhan')
            ->groupBy('pila.id_pelayaran')
            ->pluck('total_lawuhan', 'id_pelayaran');

        $salesWeightByVoyage = DB::table('penjualan_item_lot_allocations as pila')
            ->join('penjualan_items as pi', 'pi.id_item', '=', 'pila.id_item')
            ->join('penjualan as pj', 'pj.id_penjualan', '=', 'pi.id_penjualan')
            ->where('pj.jenis_transaksi', 'penjualan')
            ->whereNotNull('pila.id_pelayaran')
            ->selectRaw('pila.id_pelayaran, SUM(pila.berat_alokasi) as total_berat_penjualan')
            ->groupBy('pila.id_pelayaran')
            ->pluck('total_berat_penjualan', 'id_pelayaran');

        // Calculate total captured weight for each voyage
        $rows = collect();
        foreach ($completedVoyages as $voyage) {
            $idPelayaran = (int) $voyage->id_pelayaran;

            // Get total captured weight from ikan_hasil_pelayaran
            $capturedWeight = (float) DB::table('ikan_hasil_pelayaran')
                ->where('id_pelayaran', $idPelayaran)
                ->selectRaw('SUM(berat_hasil) as total_berat')
                ->value('total_berat') ?? 0;

            $recordedWeight = (float) ($voyage->total_berat_catatan ?? 0);
            $difference = $capturedWeight - $recordedWeight;

            $rows->push((object) [
                'id_pelayaran' => $idPelayaran,
                'id_laporan' => $voyage->id_laporan,
                'nama_kapal' => $voyage->nama_kapal,
                'tanggal_berangkat' => $voyage->tanggal_berangkat,
                'tanggal_selesai' => $voyage->tanggal_selesai,
                'tanggal_tiba' => $voyage->tanggal_tiba,
                'berat_timbangan' => $capturedWeight,
                'berat_catatan' => $recordedWeight,
                'total_berat_penjualan' => (float) ($salesWeightByVoyage[$idPelayaran] ?? 0),
                'total_lawuhan' => (float) ($lawuhanByVoyage[$idPelayaran] ?? 0),
                'selisih' => $difference,
            ]);
        }

        $totalLawuhan = (float) $rows->sum('total_lawuhan');

        $today = Carbon::today();

        // Prepare chart data for last 30 voyages with selisih
        $chartRows = $rows->take(30);
        $chartLabels = $chartRows->map(function ($row) {
            return \Carbon\Carbon::parse($row->tanggal_selesai)->format('d M');
        })->reverse()->values();

        $chartSelisih = $chartRows->map(function ($row) {
            return (float) $row->selisih;
        })->reverse()->values();

        $chartBeratTimbangan = $chartRows->map(function ($row) {
            return (float) $row->berat_timbangan;
        })->reverse()->values();

        $chartBeratCatatan = $chartRows->map(function ($row) {
            return (float) $row->berat_catatan;
        })->reverse()->values();

        return view('keuangan.selisih_bongkar.index', compact(
            'rows',
            'today',
            'chartLabels',
            'chartSelisih',
            'chartBeratTimbangan',
            'chartBeratCatatan',
            'totalLawuhan'
        ));
    }

    public function storeBeratLelang(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_pelayaran' => ['required', 'integer', 'exists:pelayaran,id_pelayaran'],
            'berat_catatan' => ['required', 'numeric', 'min:0'],
        ]);

        $idPelayaran = (int) $validated['id_pelayaran'];
        $beratCatatan = (float) $validated['berat_catatan'];

        // Get total captured weight
        $capturedWeight = (float) DB::table('ikan_hasil_pelayaran')
            ->where('id_pelayaran', $idPelayaran)
            ->selectRaw('SUM(berat_hasil) as total_berat')
            ->value('total_berat') ?? 0;

        $difference = $capturedWeight - $beratCatatan;

        // Insert or update laporan_selisih_bongkaran
        $existing = DB::table('laporan_selisih_bongkaran')
            ->where('id_pelayaran', $idPelayaran)
            ->first();

        if ($existing) {
            DB::table('laporan_selisih_bongkaran')
                ->where('id_laporan', $existing->id_laporan)
                ->update([
                    'total_berat_timbangan' => $capturedWeight,
                    'total_berat_catatan' => $beratCatatan,
                    'total_selisih' => $difference,
                    'tanggal_laporan' => now()->toDateString(),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('laporan_selisih_bongkaran')->insert([
                'id_pelayaran' => $idPelayaran,
                'total_berat_timbangan' => $capturedWeight,
                'total_berat_catatan' => $beratCatatan,
                'total_selisih' => $difference,
                'tanggal_laporan' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()
            ->route('keuangan.lap-selisih-bongkaran.index')
            ->with('success', 'Berat dari kantor pengolahan ikan berhasil disimpan.');
    }

    public function bayarPiutang(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_penjualan' => ['required', 'integer', 'exists:penjualan,id_penjualan'],
            'bayar_tunai' => ['nullable', 'numeric', 'min:0'],
            'bayar_transfer' => ['nullable', 'numeric', 'min:0'],
        ]);

        $idPenjualan = (int) $validated['id_penjualan'];
        $newKas = (float) ($validated['bayar_tunai'] ?? 0);
        $newTransfer = (float) ($validated['bayar_transfer'] ?? 0);
        $newPayment = $newKas + $newTransfer;

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
                'message' => 'Pembayaran melebihi sisa piutang (Rp '.number_format($currentPiutang, 2, ',', '.').').',
            ], 422);
        }

        $newPiutang = round(max(0, $currentPiutang - $newPayment), 2);
        $newBayarTunai = (float) $penjualan->bayar_tunai + $newKas;
        $newBayarTransfer = (float) $penjualan->bayar_transfer + $newTransfer;
        $newDiterima = $newBayarTunai + $newBayarTransfer;
        $statusPembayaran = $newPiutang <= 0 ? 'lunas' : 'piutang';
        $invNo = 'INV-'.str_pad($idPenjualan, 5, '0', STR_PAD_LEFT);

        DB::transaction(function () use (
            $idPenjualan, $penjualan, $invNo,
            $newBayarTunai, $newBayarTransfer, $newPiutang, $statusPembayaran,
            $newKas, $newTransfer
        ) {
            DB::table('penjualan')->where('id_penjualan', $idPenjualan)->update([
                'bayar_tunai' => $newBayarTunai,
                'bayar_transfer' => $newBayarTransfer,
                'piutang' => $newPiutang,
                'status_pembayaran' => $statusPembayaran,
                'updated_at' => now(),
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
            'status_pembayaran' => $statusPembayaran,
            'new_piutang' => $newPiutang,
            'new_piutang_formatted' => number_format($newPiutang, 2, ',', '.'),
            'new_diterima' => $newDiterima,
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

    public function kasInduk(Request $request): View
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

        $rows = DB::table('kas_induk_transfers')
            ->whereBetween('tanggal_setor', [$startDate, $endDate])
            ->orderByDesc('tanggal_setor')
            ->orderByDesc('id_kas_induk_transfer')
            ->get();

        $summary = [
            'total_setoran' => (float) $rows->sum('nominal'),
            'jumlah_transaksi' => $rows->count(),
            'total_dari_kas' => (float) $rows->where('akun_sumber', 'kas')->sum('nominal'),
            'total_dari_bank' => (float) $rows->where('akun_sumber', 'bank')->sum('nominal'),
        ];

        return view('keuangan.kas_induk.index', compact(
            'rows',
            'summary',
            'startDate',
            'endDate'
        ));
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
            if ($debit <= 0 || $kredit > 0) {
                return back()
                    ->withInput()
                    ->withErrors(['kategori' => 'Pinjam Modal Bu Uum dan Pinjam Modal Jons Group harus diinput sebagai debit pada kas/bank. Kredit hutangnya akan tercatat otomatis di Hutang Modal.']);
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
                    nominal: $debit
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
            $message .= ' Debit kas/bank dan kredit Hutang Modal sudah tercatat.';
        }

        if ($validated['kategori'] === self::EMPLOYEE_CASH_ADVANCE_CATEGORY) {
            $message .= ' Piutang Kas Bon Pegawai ikut tercatat.';
        }

        return back()->with('success', $message);
    }

    public function storeKasIndukTransfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'akun_sumber' => ['required', 'in:kas,bank'],
            'tanggal' => ['required', 'date'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'nominal' => ['required', 'numeric', 'gt:0'],
        ]);

        $nominal = round((float) $validated['nominal'], 2);
        $saldoTersedia = $this->getLastSaldoByAkun($validated['akun_sumber']);

        if ($nominal > $saldoTersedia + 0.01) {
            return back()
                ->withInput()
                ->withErrors([
                    'nominal' => 'Nominal setoran melebihi saldo '.strtoupper($validated['akun_sumber']).' yang tersedia.',
                ]);
        }

        DB::transaction(function () use ($validated, $nominal) {
            $deskripsi = trim((string) ($validated['deskripsi'] ?? ''));

            if ($deskripsi === '') {
                $deskripsi = 'Setoran dari '.strtoupper($validated['akun_sumber']).' ke kas induk.';
            }

            $arusKasId = $this->postArusKas(
                akun: $validated['akun_sumber'],
                tanggal: $validated['tanggal'],
                kategori: self::TREASURY_TRANSFER_CATEGORY,
                deskripsi: $deskripsi,
                debit: 0,
                kredit: $nominal
            );

            $this->recordKasIndukTransfer(
                arusKasId: $arusKasId,
                tanggal: $validated['tanggal'],
                akunSumber: $validated['akun_sumber'],
                deskripsi: $deskripsi,
                nominal: $nominal
            );
        });

        return back()->with('success', 'Setoran Kas Induk berhasil disimpan dan saldo akun sumber sudah dikurangi.');
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
            'end_date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:semua,piutang'],
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
        $status = $validated['status'] ?? 'piutang';

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
            'total_piutang' => (float) $rows->sum('piutang'),
            'jumlah_transaksi' => $rows->count(),
            'total_tagihan' => (float) $rows->sum('total_harga'),
            'total_diterima' => (float) $rows->sum('total_diterima'),
        ];

        $byCustomer = $rows
            ->groupBy('nama_customer_display')
            ->map(fn ($items) => (object) [
                'nama' => $items->first()->nama_customer_display,
                'jumlah' => $items->count(),
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

    private function recordKasIndukTransfer(int $arusKasId, string $tanggal, string $akunSumber, string $deskripsi, float $nominal): void
    {
        DB::table('kas_induk_transfers')->insert([
            'id_kas_sumber' => $arusKasId,
            'tanggal_setor' => $tanggal,
            'akun_sumber' => $akunSumber,
            'deskripsi' => $deskripsi,
            'nominal' => $nominal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function postArusKas(string $akun, string $tanggal, string $kategori, string $deskripsi, float $debit, float $kredit): int
    {
        $lastSaldo = $this->getLastSaldoByAkun($akun);
        $saldoBaru = $lastSaldo + $debit - $kredit;

        if ($saldoBaru < -0.009) {
            throw ValidationException::withMessages([
                'nominal' => 'Saldo '.strtoupper($akun).' tidak mencukupi. Saldo tersedia Rp '.number_format($lastSaldo, 2, ',', '.').', sehingga transaksi ini tidak boleh membuat saldo minus.',
            ]);
        }

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
