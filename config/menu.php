<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Menu
    |--------------------------------------------------------------------------
    |
    | Each entry represents a sidebar item.  Supported keys:
    |   title      (string)         – text shown next to the icon
    |   icon       (string)         – CSS classes for the `<i>` element
    |   route      (string)         – route name or url path
    |   type       ('route'|'url')  – how to interpret `route` (defaults to url)
    |   permission (string)         – permission name (optional)
    |   roles      (string|array)   – role(s) required (optional)
    |   children   (array)          – nested submenu items (same format)
    |
    */

    'items' => [
        [
            'title' => 'Beranda',
            'icon' => 'ti-home',
            'route' => '/',          // a plain URL in this case
            'type' => 'url',
        ],
        [
            'title' => 'Daftar Kapal',
            'icon' => 'ti-anchor',
            'route' => '/kapal',
            'type' => 'url',
        ],
        [
            'title' => 'Pelayaran',
            'icon' => 'ti-direction',
            'route' => '/pelayaran',
            'type' => 'url',
        ],
        [
            'title' => 'Sisa Trip',
            'icon' => 'ti-calendar',
            'route' => '/pelayaran/sisa',
            'type' => 'url',
        ],
        [
            'title' => 'Operasional Trip',
            'icon' => 'ti-agenda',
            'route' => '/operasional',
            'type' => 'url',
        ],
        [
            'title' => 'Penjualan',
            'icon' => 'ti-bag',
            'route' => '/penjualan',
            'type' => 'url',
        ],
        [
            'title' => 'Pembelian Barang',
            'icon' => 'ti-package',
            'route' => 'pembelian.index',
            'type' => 'route',
        ],
        [
            'title' => 'Operasional Kantor',
            'icon' => 'ti-clipboard',
            'route' => '/operasional-kantor',
            'type' => 'url',
        ],
        [
            'title' => 'Stok',
            'icon' => 'ti-layers',
            'route' => '#',
            'children' => [
                [
                    'title' => 'Stok Ikan',
                    'icon' => 'ti-fish',
                    'route' => 'stok.ikan.index',
                    'type' => 'route',
                ],
                [
                    'title' => 'Stok Barang',
                    'icon' => 'ti-tag',
                    'route' => 'stok.barang.index',
                    'type' => 'route',
                ],
            ],
        ],
        [
            'title' => 'Keuangan',
            'icon' => 'ti-briefcase',
            'route' => '#',
            'children' => [
                [
                    'title' => 'Lap Penjualan',
                    'icon' => 'ti-report-money',
                    'route' => '/keuangan/lap-penjualan',
                    'type' => 'url',
                ],
                [
                    'title' => 'Arus Kas',
                    'icon' => 'ti-money',
                    'route' => '/keuangan/arus-kas',
                    'type' => 'url',
                ],
                [
                    'title' => 'Laba Rugi',
                    'icon' => 'ti-cash',
                    'route' => '/keuangan/laba',
                    'type' => 'url',
                ],
                [
                    'title' => 'Lap Selisih Bongkaran',
                    'icon' => 'ti-arrows-diff',
                    'route' => '/keuangan/lap-selisih-bongkaran',
                    'type' => 'url',
                ],
            ],
        ],
        [
            'title' => 'Pengaturan',
            'icon' => 'ti-settings',
            'route' => '#',
            'children' => [
                [
                    'title' => 'Master Perbekalan',
                    'icon' => 'ti-package',
                    'route' => 'master.perbekalan.index',
                    'type' => 'route',
                ],
                [
                    'title' => 'Master Ikan',
                    'icon' => 'ti-layout-list-thumb',
                    'route' => 'master.ikan.index',
                    'type' => 'route',
                ],
                [
                    'title' => 'Master Customer',
                    'icon' => 'ti-id-badge',
                    'route' => 'master.customer.index',
                    'type' => 'route',
                ],
                [
                    'title' => 'Master Operasional',
                    'icon' => 'ti-agenda',
                    'route' => 'master.operasional.index',
                    'type' => 'route',
                ],
                [
                    'title' => 'Master Item Pembelian',
                    'icon' => 'ti-layout-list-thumb',
                    'route' => 'master.item-pembelian.index',
                    'type' => 'route',
                ],
                [
                    'title' => 'Pengguna',
                    'icon' => 'ti-user',
                    'route' => 'users.index',   // will call route()
                    'type' => 'route',
                    'permission' => 'read user',
                ],
                [
                    'title' => 'Pengaturan',
                    'icon' => 'ti-adjustments',
                    'route' => '/pengaturan',
                    'type' => 'url',
                ],
            ],
        ],
        [
            'title' => 'Panduan',
            'icon' => 'icon-paper',
            'route' => '/panduan',
            'type' => 'url',
        ],
    ],

];
