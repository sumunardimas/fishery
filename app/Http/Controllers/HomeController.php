<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    // Home views
    public function index(): View
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $overview = $this->getAdminOverview();

            return view('home.admin', compact('user', 'overview'));
        }
        if ($user->hasRole('kasir')) {
            return view('home.kasir', compact('user'));
        }
        if ($user->hasRole('staff')) {
            return view('home.staff', compact('user'));
        }

        // Default fallback if no matching role
        abort(403, 'Unauthorized access');
    }

    public function adminHome(): View
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole('admin')) {
            abort(403, 'Unauthorized access');
        }

        $overview = $this->getAdminOverview();

        return view('home.admin', compact('user', 'overview'));
    }

    private function getAdminOverview(): array
    {
        $today = Carbon::today();
        $todayDate = $today->toDateString();
        $monthStart = $today->copy()->startOfMonth()->toDateString();
        $monthEnd = $today->copy()->endOfMonth()->toDateString();

        $salesToday = [
            'transactions' => (int) DB::table('penjualan')->whereDate('tanggal_penjualan', $todayDate)->count(),
            'revenue' => (float) (DB::table('penjualan')->whereDate('tanggal_penjualan', $todayDate)->sum('total_harga') ?? 0),
            'weight' => (float) (DB::table('penjualan')->whereDate('tanggal_penjualan', $todayDate)->sum('berat') ?? 0),
        ];

        $salesMonth = [
            'transactions' => (int) DB::table('penjualan')->whereBetween('tanggal_penjualan', [$monthStart, $monthEnd])->count(),
            'revenue' => (float) (DB::table('penjualan')->whereBetween('tanggal_penjualan', [$monthStart, $monthEnd])->sum('total_harga') ?? 0),
            'weight' => (float) (DB::table('penjualan')->whereBetween('tanggal_penjualan', [$monthStart, $monthEnd])->sum('berat') ?? 0),
        ];

        $cashToday = [
            'in' => (float) (DB::table('arus_kas')->whereDate('tanggal', $todayDate)->sum('uang_masuk') ?? 0),
            'out' => (float) (DB::table('arus_kas')->whereDate('tanggal', $todayDate)->sum('uang_keluar') ?? 0),
        ];
        $cashToday['net'] = $cashToday['in'] - $cashToday['out'];

        $kasHarianToday = DB::table('kas_harian')->whereDate('tanggal', $todayDate)->first();

        $masterData = [
            'ikan' => (int) DB::table('master_ikan')->count(),
            'customer' => (int) DB::table('master_customer')->count(),
            'item_pembelian' => (int) DB::table('master_item_pembelian')->count(),
            'operasional' => (int) DB::table('master_operasional')->count(),
            'users' => (int) DB::table('users')->count(),
        ];

        return [
            'today_label' => $today->translatedFormat('d M Y'),
            'month_label' => Carbon::parse($monthStart)->translatedFormat('d M').' - '.Carbon::parse($monthEnd)->translatedFormat('d M Y'),
            'sales_today' => $salesToday,
            'sales_month' => $salesMonth,
            'cash_today' => $cashToday,
            'current_balance' => (float) (DB::table('arus_kas')->orderByDesc('id_kas')->value('saldo') ?? 0),
            'kas_harian_today' => [
                'is_open' => $kasHarianToday ? ! (bool) $kasHarianToday->status_tutup : false,
                'exists' => (bool) $kasHarianToday,
            ],
            'master_data' => $masterData,
            'updated_at' => now()->format('H:i'),
        ];
    }
}
