<?php

namespace App\Http\Controllers;

use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class KeuanganController extends Controller
{
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

        $currentBalance = (float) (DB::table('arus_kas')->orderByDesc('id_kas')->value('saldo') ?? 0);

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

        $dailyFromDb = DB::table('arus_kas')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->selectRaw('DATE(tanggal) as tanggal, SUM(uang_masuk) as total_revenue, SUM(uang_keluar) as total_expenditure')
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->keyBy('tanggal');

        $rows = collect();
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $key = $date->toDateString();
            $rows->push((object) [
                'tanggal' => $key,
                'total_revenue' => (float) ($dailyFromDb[$key]->total_revenue ?? 0),
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
            ->whereBetween('tanggal_penjualan', [$startDate, $endDate])
            ->selectRaw('DATE(tanggal_penjualan) as tanggal, SUM(berat) as berat_penjualan')
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
}
