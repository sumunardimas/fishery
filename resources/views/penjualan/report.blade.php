@extends('layouts.layout')

@section('title', 'Laporan Penjualan')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">Laporan Penjualan</h4>
                        <p class="card-description mb-0">Default menampilkan ringkasan hari ini, bisa filter rentang tanggal.</p>
                    </div>
                    <a href="{{ route('penjualan.index') }}" class="btn btn-light">Kembali ke POS</a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('penjualan.report') }}" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="start_date">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date">Tanggal Selesai</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card"><div class="card-body"><small class="text-muted">Total Transaksi</small><h4>{{ $summary['total_transaksi'] }}</h4></div></div>
                </div>
                <div class="col-md-4">
                    <div class="card"><div class="card-body"><small class="text-muted">Total Berat</small><h4>{{ number_format($summary['total_berat'], 2) }} kg</h4></div></div>
                </div>
                <div class="col-md-4">
                    <div class="card"><div class="card-body"><small class="text-muted">Total Pendapatan</small><h4>Rp {{ number_format($summary['total_pendapatan'], 2, ',', '.') }}</h4></div></div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Ringkasan per Jenis Ikan</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Jenis Ikan</th>
                                    <th>Jumlah Transaksi</th>
                                    <th>Total Berat</th>
                                    <th>Total Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($groupByIkan as $row)
                                    <tr>
                                        <td>{{ $row['nama_ikan'] ?? '-' }}</td>
                                        <td>{{ $row['jumlah_transaksi'] }}</td>
                                        <td>{{ number_format($row['total_berat'], 2) }} kg</td>
                                        <td>Rp {{ number_format($row['total_pendapatan'], 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Detail Transaksi</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Ikan</th>
                                    <th>Customer</th>
                                    <th>Berat</th>
                                    <th>Harga/kg</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $trx)
                                    <tr>
                                        <td>{{ $trx->tanggal_penjualan?->format('d-m-Y') }}</td>
                                        <td>{{ $trx->nama_ikan }}</td>
                                        <td>{{ $trx->nama_customer_display }}</td>
                                        <td>{{ number_format($trx->berat, 2) }} kg</td>
                                        <td>Rp {{ number_format($trx->harga_per_kg, 2, ',', '.') }}</td>
                                        <td>Rp {{ number_format($trx->total_harga, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">Tidak ada transaksi pada periode ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
