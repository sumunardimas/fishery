@extends('layouts.layout')

@section('title', 'Beranda')

@section('content')
    @php
        $overview = $overview ?? ['sections' => [], 'total_sections' => 0, 'total_links' => 0, 'summary_cards' => [], 'updated_at' => now()->format('H:i')];
        $currency = static fn($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    @endphp

    <div class="row">
        <div class="col-md-12 grid-margin">
            <div class="row">
                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                    <h3 class="font-weight-bold">Selamat datang <span
                            class="text-info">{{ ucwords($user->getRoleNames()->first() ?? '-') }}</span> di <br> Perikanan</h3>
                    <h6 class="font-weight-normal mb-0">Dashboard ini menampilkan halaman yang bisa Anda akses.</h6>
                </div>
                <div class="col-12 col-xl-4 mt-3 mt-xl-0">
                    <div class="d-flex justify-content-xl-end text-muted">
                        <small>Update {{ $overview['updated_at'] }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($overview['summary_cards'] as $card)
            <div class="col-sm-6 col-lg-4 grid-margin stretch-card">
                <div class="card card-light-blue">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <p class="mb-0">{{ $card['title'] }}</p>
                            <i class="{{ $card['icon'] }}"></i>
                        </div>
                        <h4 class="mb-3">{{ $currency($card['value']) }}</h4>
                        <a href="{{ $card['url'] }}" class="btn btn-sm btn-outline-light">{{ $card['button'] }}</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card card-tale">
                <div class="card-body">
                    <p class="mb-1">Modul Akses</p>
                    <h4 class="mb-0">{{ number_format($overview['total_sections']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card card-dark-blue">
                <div class="card-body">
                    <p class="mb-1">Total Halaman</p>
                    <h4 class="mb-0">{{ number_format($overview['total_links']) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse ($overview['sections'] as $section)
            <div class="col-sm-6 col-xl-4 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-2">
                            <i class="{{ $section['icon'] ?? 'ti-layout-grid2' }} mr-2"></i>{{ $section['title'] }}
                        </h4>
                        <ul class="list-group list-group-flush">
                            @foreach ($section['links'] as $link)
                                <li class="list-group-item px-0">
                                    <a href="{{ $link['url'] }}" class="d-flex align-items-center text-decoration-none">
                                        <i class="{{ $link['icon'] ?? 'ti-angle-right' }} mr-2"></i>
                                        <span>{{ $link['title'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Belum ada halaman yang tersedia untuk peran ini.</div>
            </div>
        @endforelse
    </div>
@endsection

@push('scripts')

@endpush
