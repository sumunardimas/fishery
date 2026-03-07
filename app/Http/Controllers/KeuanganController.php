<?php

namespace App\Http\Controllers;

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
}
