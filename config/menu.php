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
            'roles' => ['admin', 'staff'],
        ],
        [
            'title' => 'Pelayaran Mulai',
            'icon' => 'ti-direction',
            'route' => '/pelayaran',
            'type' => 'url',
            'roles' => ['admin', 'staff'],
        ],
        [
            'title' => 'Pelayaran Selesai',
            'icon' => 'ti-calendar',
            'route' => '#',
            'roles' => ['admin', 'staff'],
            'children' => [
                [
                    'title' => 'Laporkan',
                    'icon' => 'ti-write',
                    'route' => 'pelayaran.sisa.index',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Riwayat',
                    'icon' => 'ti-exchange-vertical',
                    'route' => 'pelayaran.sisa.history',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
            ],
        ],
        [
            'title' => 'Penjualan Ikan',
            'icon' => 'ti-bag',
            'route' => '#',
            'children' => [
                [
                    'title' => 'Transaksi',
                    'icon' => 'ti-write',
                    'route' => 'penjualan.index',
                    'type' => 'route',
                ],
                [
                    'title' => 'Riwayat Transaksi',
                    'icon' => 'ti-exchange-vertical',
                    'route' => 'penjualan.riwayat',
                    'type' => 'route',
                ],
            ],
        ],
        [
            'title' => 'Pembelian Barang',
            'icon' => 'ti-package',
            'route' => '#',
            'roles' => ['admin', 'staff'],
            'children' => [

                [
                    'title' => 'Transaksi',
                    'icon' => 'ti-write',
                    'route' => 'pembelian.transaksi',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Riwayat',
                    'icon' => 'ti-exchange-vertical',
                    'route' => 'pembelian.riwayat',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Master',
                    'icon' => 'ti-layout-list-thumb',
                    'route' => 'master.item-pembelian.index',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
            ],
        ],
        [
            'title' => 'Perbekalan',
            'icon' => 'ti-package',
            'route' => '#',
            'roles' => ['admin', 'staff'],
            'children' => [
                [
                    'title' => 'Transaksi',
                    'icon' => 'ti-write',
                    'route' => 'master.perbekalan.transaksi',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Riwayat In Out',
                    'icon' => 'ti-exchange-vertical',
                    'route' => 'master.perbekalan.history',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Master',
                    'icon' => 'ti-layout-list-thumb',
                    'route' => 'master.perbekalan.index',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
            ],
        ],
        [
            'title' => 'Operasional Kantor',
            'icon' => 'ti-clipboard',
            'route' => '#',
            'roles' => ['admin', 'staff'],
            'children' => [
                [
                    'title' => 'Transaksi',
                    'icon' => 'ti-write',
                    'route' => 'operasional-kantor.transaksi',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Riwayat In Out',
                    'icon' => 'ti-exchange-vertical',
                    'route' => 'operasional-kantor.history',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Master',
                    'icon' => 'ti-layout-list-thumb',
                    'route' => 'operasional-kantor.index',
                    'type' => 'route',
                    'roles' => ['admin', 'staff'],
                ],
            ],
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
            ],
        ],
        [
            'title' => 'Keuangan',
            'icon' => 'ti-money',
            'route' => '#',
            'roles' => ['admin', 'staff'],
            'children' => [
                [
                    'title' => 'Kas',
                    'icon' => 'ti-wallet',
                    'route' => '/keuangan/kas',
                    'type' => 'url',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Bank',
                    'icon' => 'ti-credit-card',
                    'route' => '/keuangan/bank',
                    'type' => 'url',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Setoran Kas Induk',
                    'icon' => 'ti-export',
                    'route' => '/keuangan/kas-induk',
                    'type' => 'url',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Piutang',
                    'icon' => 'ti-alert',
                    'route' => '/keuangan/piutang',
                    'type' => 'url',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Kas Bon Pegawai',
                    'icon' => 'ti-user',
                    'route' => '/keuangan/kas-bon-pegawai',
                    'type' => 'url',
                    'roles' => ['admin', 'staff'],
                ],
                [
                    'title' => 'Hutang Modal',
                    'icon' => 'ti-receipt',
                    'route' => '/keuangan/hutang-modal',
                    'type' => 'url',
                    'roles' => ['admin', 'staff'],
                ],
            ],
        ],
        [
            'title' => 'Laporan',
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
                    'title' => 'Lap Arus Kas',
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
                    'title' => 'Master Ikan',
                    'icon' => 'ti-layout-list-thumb',
                    'route' => 'master.ikan.index',
                    'type' => 'route',
                ],
                [
                    'title' => 'Master Ikan Tangkapan',
                    'icon' => 'ti-layout-grid2-thumb',
                    'route' => 'master.ikan-tangkapan.index',
                    'type' => 'route',
                ],
                [
                    'title' => 'Master Customer',
                    'icon' => 'ti-id-badge',
                    'route' => 'master.customer.index',
                    'type' => 'route',
                ],
                [
                    'title' => 'Master Operasional Trip',
                    'icon' => 'ti-layout-list-thumb',
                    'route' => 'operasional.master',
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
