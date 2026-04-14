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
            $overview = $this->getStaffOverview();

            return view('home.staff', compact('user', 'overview'));
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

        $saldoKas = (float) (DB::table('arus_kas')->where('akun', 'kas')->orderByDesc('id_kas')->value('saldo') ?? 0);
        $saldoBank = (float) (DB::table('arus_kas')->where('akun', 'bank')->orderByDesc('id_kas')->value('saldo') ?? 0);

        return [
            'today_label' => $today->translatedFormat('d M Y'),
            'month_label' => Carbon::parse($monthStart)->translatedFormat('d M').' - '.Carbon::parse($monthEnd)->translatedFormat('d M Y'),
            'sales_today' => $salesToday,
            'sales_month' => $salesMonth,
            'cash_today' => $cashToday,
            'current_balance' => $saldoKas + $saldoBank,
            'kas_harian_today' => [
                'is_open' => $kasHarianToday ? ! (bool) $kasHarianToday->status_tutup : false,
                'exists' => (bool) $kasHarianToday,
            ],
            'master_data' => $masterData,
            'updated_at' => now()->format('H:i'),
        ];
    }

    private function getStaffOverview(): array
    {
        $menuItems = config('menu.items', []);
        $sections = [];

        $kasBalance = (float) (DB::table('arus_kas')->where('akun', 'kas')->orderByDesc('id_kas')->value('saldo') ?? 0);
        $bankBalance = (float) (DB::table('arus_kas')->where('akun', 'bank')->orderByDesc('id_kas')->value('saldo') ?? 0);
        $piutangOutstanding = (float) (DB::table('penjualan')->where('piutang', '>', 0)->sum('piutang') ?? 0);

        foreach ($menuItems as $item) {
            if (! $this->staffCanSeeMenuItem($item)) {
                continue;
            }

            $children = [];
            foreach ((array) ($item['children'] ?? []) as $child) {
                if (! $this->staffCanSeeMenuItem($child)) {
                    continue;
                }

                $url = $this->resolveMenuItemUrl($child);
                if (! $url) {
                    continue;
                }

                $children[] = [
                    'title' => $child['title'] ?? 'Menu',
                    'icon' => $child['icon'] ?? 'ti-angle-right',
                    'url' => $url,
                ];
            }

            if (count($children) > 0) {
                $sections[] = [
                    'title' => $item['title'] ?? 'Menu',
                    'icon' => $item['icon'] ?? 'ti-layout-grid2',
                    'links' => $children,
                ];

                continue;
            }

            $url = $this->resolveMenuItemUrl($item);
            if ($url) {
                $sections[] = [
                    'title' => $item['title'] ?? 'Menu',
                    'icon' => $item['icon'] ?? 'ti-layout-grid2',
                    'links' => [[
                        'title' => $item['title'] ?? 'Menu',
                        'icon' => $item['icon'] ?? 'ti-angle-right',
                        'url' => $url,
                    ]],
                ];
            }
        }

        return [
            'sections' => $sections,
            'total_sections' => count($sections),
            'total_links' => collect($sections)->sum(fn ($section) => count($section['links'] ?? [])),
            'summary_cards' => [
                [
                    'title' => 'Kas',
                    'icon' => 'ti-wallet',
                    'value' => $kasBalance,
                    'url' => route('keuangan.kas.index'),
                    'button' => 'Buka Kas',
                ],
                [
                    'title' => 'Bank',
                    'icon' => 'ti-credit-card',
                    'value' => $bankBalance,
                    'url' => route('keuangan.bank.index'),
                    'button' => 'Buka Bank',
                ],
                [
                    'title' => 'Piutang',
                    'icon' => 'ti-alert',
                    'value' => $piutangOutstanding,
                    'url' => route('keuangan.piutang.index'),
                    'button' => 'Buka Piutang',
                ],
            ],
            'updated_at' => now()->format('H:i'),
        ];
    }

    private function staffCanSeeMenuItem(array $item): bool
    {
        $roles = (array) ($item['roles'] ?? []);

        return in_array('staff', $roles, true);
    }

    private function resolveMenuItemUrl(array $item): ?string
    {
        if (! isset($item['route'])) {
            return null;
        }

        if (($item['route'] ?? '#') === '#') {
            return null;
        }

        if (($item['type'] ?? 'url') === 'route') {
            try {
                return route($item['route']);
            } catch (\Throwable) {
                return null;
            }
        }

        return url($item['route']);
    }
}
