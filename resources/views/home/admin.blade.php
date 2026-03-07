@extends('layouts.layout')

@section('title', 'Beranda')

@section('content')
    @php
        $currency = static fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
        $decimal = static fn($value) => number_format((float) $value, 2, ',', '.');
    @endphp

    <div class="row">
        <div class="col-md-12 grid-margin">
            <div class="row">
                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                    <h3 class="font-weight-bold">Selamat datang <span
                            class="text-info">{{ ucwords($user->getRoleNames()->first() ?? '-') }}</span> di <br> Fisherya</h3>
                    <h6 class="font-weight-normal mb-0">Ringkasan operasional dan keuangan perusahaan per {{ $overview['today_label'] }}.</h6>
                </div>
                <div class="col-12 col-xl-4">
                    <div class="card card-light-danger mb-0">
                        <div class="card-body py-3">
                            <p class="mb-1">Status Kas Hari Ini</p>
                            @if ($overview['kas_harian_today']['exists'])
                                <h5 class="mb-0 {{ $overview['kas_harian_today']['is_open'] ? 'text-success' : 'text-danger' }}">
                                    {{ $overview['kas_harian_today']['is_open'] ? 'Kas Masih Buka' : 'Kas Sudah Ditutup' }}
                                </h5>
                            @else
                                <h5 class="mb-0 text-warning">Kas Belum Dibuka</h5>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-1">Penjualan Hari Ini</p>
                    <p class="text-muted mb-2">{{ $overview['today_label'] }}</p>
                    <h3 class="mb-3">{{ $currency($overview['sales_today']['revenue']) }}</h3>
                    <div class="d-flex justify-content-between">
                        <p class="mb-0">Transaksi: <strong>{{ $overview['sales_today']['transactions'] }}</strong></p>
                        <p class="mb-0">Berat: <strong>{{ $decimal($overview['sales_today']['weight']) }} kg</strong></p>
                    </div>
                    <a href="{{ route('penjualan.report') }}" class="btn btn-sm btn-outline-info mt-3">Lihat Detail Penjualan</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-1">Penjualan Bulan Ini</p>
                    <p class="text-muted mb-2">{{ $overview['month_label'] }}</p>
                    <h3 class="mb-3">{{ $currency($overview['sales_month']['revenue']) }}</h3>
                    <div class="d-flex justify-content-between">
                        <p class="mb-0">Transaksi: <strong>{{ $overview['sales_month']['transactions'] }}</strong></p>
                        <p class="mb-0">Berat: <strong>{{ $decimal($overview['sales_month']['weight']) }} kg</strong></p>
                    </div>
                    <a href="{{ route('keuangan.lap-penjualan.index') }}" class="btn btn-sm btn-outline-info mt-3">Lihat Ringkasan Keuangan</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card card-tale">
                <div class="card-body">
                    <p class="mb-1">Arus Kas Masuk Hari Ini</p>
                    <h4 class="mb-0">{{ $currency($overview['cash_today']['in']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card card-dark-blue">
                <div class="card-body">
                    <p class="mb-1">Arus Kas Keluar Hari Ini</p>
                    <h4 class="mb-0">{{ $currency($overview['cash_today']['out']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card card-light-blue">
                <div class="card-body">
                    <p class="mb-1">Saldo Terakhir</p>
                    <h4 class="mb-0">{{ $currency($overview['current_balance']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="card-title mb-0">Master Data Snapshot</h4>
                        <small class="text-muted">Update {{ $overview['updated_at'] }}</small>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-lg-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <p class="mb-1 text-muted">Data Ikan</p>
                                <h5 class="mb-0">{{ number_format($overview['master_data']['ikan']) }}</h5>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <p class="mb-1 text-muted">Data Customer</p>
                                <h5 class="mb-0">{{ number_format($overview['master_data']['customer']) }}</h5>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <p class="mb-1 text-muted">Item Pembelian</p>
                                <h5 class="mb-0">{{ number_format($overview['master_data']['item_pembelian']) }}</h5>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <p class="mb-1 text-muted">Master Operasional</p>
                                <h5 class="mb-0">{{ number_format($overview['master_data']['operasional']) }}</h5>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <p class="mb-1 text-muted">Pengguna Sistem</p>
                                <h5 class="mb-0">{{ number_format($overview['master_data']['users']) }}</h5>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4 mb-3">
                            <div class="border rounded p-3 h-100">
                                <p class="mb-1 text-muted">Net Cashflow Hari Ini</p>
                                <h5 class="mb-0 {{ $overview['cash_today']['net'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $currency($overview['cash_today']['net']) }}
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Aksi Cepat Admin</h4>
                    <div class="d-grid gap-2">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-primary btn-block mb-2">Kelola Pengguna</a>
                        <a href="{{ route('master.ikan.index') }}" class="btn btn-outline-primary btn-block mb-2">Kelola Master Ikan</a>
                        <a href="{{ route('master.customer.index') }}" class="btn btn-outline-primary btn-block mb-2">Kelola Customer</a>
                        <a href="{{ route('keuangan.arus-kas.index') }}" class="btn btn-outline-primary btn-block mb-2">Cek Arus Kas</a>
                        <a href="{{ route('keuangan.laba.index') }}" class="btn btn-outline-primary btn-block">Cek Laba Rugi</a>
                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection

@push('scripts')

@endpush
