@extends('layouts.layout')

@section('title', 'Panduan Aplikasi')

@section('content')
    @php
        $menuItems = config('menu.items', []);
        $user = auth()->user();

        $showItem = function (array $item) use ($user): bool {
            if (!$user) {
                return false;
            }

            if ($user->hasRole('staff')) {
                $roles = (array) ($item['roles'] ?? []);
                if (!in_array('staff', $roles, true)) {
                    return false;
                }
            }

            if (isset($item['permission']) && !$user->can($item['permission'])) {
                return false;
            }

            if (isset($item['roles'])) {
                $roles = (array) $item['roles'];
                if (!$user->hasAnyRole($roles)) {
                    return false;
                }
            }

            return true;
        };

        $itemUrl = function (array $item): string {
            if (!isset($item['route'])) {
                return '#';
            }

            if (($item['type'] ?? 'url') === 'route') {
                return route($item['route']);
            }

            return url($item['route']);
        };

        $visibleMenuItems = collect($menuItems)
            ->filter(fn(array $item) => $showItem($item))
            ->map(function (array $item) use ($showItem, $itemUrl) {
                $children = collect((array) ($item['children'] ?? []))
                    ->filter(fn(array $child) => $showItem($child))
                    ->map(
                        fn(array $child) => [
                            'title' => $child['title'] ?? 'Menu',
                            'icon' => $child['icon'] ?? 'ti-angle-right',
                            'url' => $itemUrl($child),
                            'type' => $child['type'] ?? 'url',
                        ],
                    )
                    ->values();

                return [
                    'title' => $item['title'] ?? 'Menu',
                    'icon' => $item['icon'] ?? 'ti-layout-grid2',
                    'url' => $itemUrl($item),
                    'type' => $item['type'] ?? 'url',
                    'children' => $children,
                ];
            })
            ->values();
    @endphp

    <div class="row">
        <div class="col-12 stretch-card grid-margin">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title mb-2">Panduan Menu Aplikasi</h3>
                    <p class="card-description mb-0">
                        Halaman ini mengikuti urutan menu pada sidebar agar alur penggunaan aplikasi lebih mudah diikuti.
                        Klik tautan menu di bawah untuk membuka halaman terkait.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($visibleMenuItems as $index => $item)
            <div class="col-lg-6 grid-margin stretch-card">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div>
                                <h4 class="card-title mb-1">{{ $index + 1 }}. {{ $item['title'] }}</h4>
                                <p class="text-muted mb-0">
                                    @if ($item['children']->isNotEmpty())
                                        Bagian ini memiliki {{ $item['children']->count() }} submenu.
                                    @else
                                        Menu ini membuka satu halaman utama.
                                    @endif
                                </p>
                            </div>
                            <i class="{{ $item['icon'] }}" style="font-size: 1.5rem;"></i>
                        </div>

                        @if ($item['children']->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">No</th>
                                            <th>Submenu</th>
                                            <th class="text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($item['children'] as $childIndex => $child)
                                            <tr>
                                                <td>{{ $childIndex + 1 }}</td>
                                                <td>
                                                    <div class="font-weight-medium">{{ $child['title'] }}</div>
                                                    <small class="text-muted">Buka submenu
                                                        {{ strtolower($child['title']) }}.</small>
                                                </td>
                                                <td class="text-right">
                                                    <a href="{{ $child['url'] }}" class="btn btn-outline-primary btn-sm">
                                                        Buka
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mb-3 text-muted">
                                Gunakan menu ini untuk membuka halaman {{ strtolower($item['title']) }}.
                            </p>

                            @if ($item['url'] !== '#')
                                <a href="{{ $item['url'] }}" class="btn btn-primary btn-sm">
                                    Buka {{ $item['title'] }}
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
