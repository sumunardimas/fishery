@extends('layouts.layout')

@section('title', 'Laporan Trip Pelayaran')

@section('content')
    @php
        $tripCostTotal = (float) ($reportSummary['trip_cost_total'] ?? 0);
        $tripCatchTotal = (float) ($reportSummary['trip_catch_total'] ?? 0);
        $salesTotal = (float) ($reportSummary['sales_total'] ?? 0);
        $salesNet = (float) ($reportSummary['sales_net'] ?? 0);
        $storageTotalWeight = (float) ($reportSummary['current_storage_total_weight'] ?? 0);
        $nextTrip = $salesWindow['next_pelayaran'] ?? null;
        $isExactAttribution = ($salesAttributionMode ?? 'estimate') === 'exact';
        $salesLabel = $isExactAttribution ? 'Realisasi Penjualan (Exact)' : 'Estimasi Penjualan';
        $salesNetLabel = $isExactAttribution ? 'Realisasi Penjualan - Biaya' : 'Estimasi Penjualan - Biaya';
    @endphp

    <div class="row">
        <div class="col-12">
            @if ($errors->has('message'))
                <x-alert type="danger" :message="$errors->first('message') ?? null" />
            @elseif (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            <div class="card mb-4">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="card-title mb-1">Laporan Trip #{{ $pelayaran->id_pelayaran }}</h4>
                        <p class="card-description mb-0">
                            Kapal {{ $pelayaran->kapal->nama_kapal ?? '-' }}. Ringkasan tangkapan, biaya trip, dan
                            realisasi penjualan dibanding biaya trip.
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('pelayaran.sisa.history.show', ['pelayaran' => $pelayaran->id_pelayaran, 'tab' => 'rekap']) }}"
                            class="btn btn-outline-primary mr-2">Kembali ke Rekap</a>
                        <a href="{{ route('pelayaran.sisa.history') }}" class="btn btn-primary">Riwayat Pelayaran</a>
                    </div>
                </div>
            </div>

            @if ($isExactAttribution)
                <div class="alert alert-success mb-4" role="alert">
                    <strong>Atribusi exact aktif:</strong> realisasi penjualan dihitung dari alokasi lot ke trip ini
                    pada saat transaksi penjualan. Trip baru yang berangkat lagi tidak memutus atribusi trip lama;
                    ikan akan tetap terhitung ke trip asal lot sampai lot tersebut habis terjual.
                </div>
            @else
                <div class="alert alert-warning mb-4" role="alert">
                    <strong>Catatan estimasi (historis):</strong> trip ini belum memiliki data lot sehingga penjualan exact
                    belum bisa dihitung.
                    Bagian penjualan dihitung sebagai estimasi dari penjualan komoditas yang sama sejak trip ini ditutup
                    sampai sebelum trip berikutnya pada kapal yang sama.
                </div>
            @endif

            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <small class="text-muted d-block">Nilai Tangkapan Trip</small>
                            <h4 class="mb-0">Rp {{ number_format($tripCatchTotal, 2, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <small class="text-muted d-block">{{ $salesLabel }}</small>
                            <h4 class="mb-0">Rp {{ number_format($salesTotal, 2, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <small class="text-muted d-block">Total Biaya Trip</small>
                            <h4 class="mb-0">Rp {{ number_format($tripCostTotal, 2, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card h-100 border {{ $salesNet >= 0 ? 'border-success' : 'border-danger' }}">
                        <div class="card-body">
                            <small class="text-muted d-block">{{ $salesNetLabel }}</small>
                            <h4 class="mb-0 {{ $salesNet >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($salesNet, 2, ',', '.') }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Periode Referensi Penjualan</h5>
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 40%;">Mulai</th>
                                        <td>{{ \Carbon\Carbon::parse($salesWindow['start_date'])->translatedFormat('d F Y') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Sampai</th>
                                        <td>{{ \Carbon\Carbon::parse($salesWindow['end_date'])->translatedFormat('d F Y') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Trip Berikutnya</th>
                                        <td>
                                            @if ($nextTrip)
                                                #{{ $nextTrip->id_pelayaran }}
                                            @else
                                                Belum ada trip berikutnya
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Stok Kapal Saat Ini</th>
                                        <td>{{ number_format($storageTotalWeight, 2, ',', '.') }} kg</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Biaya Trip</h5>
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 50%;">Perbekalan Terpakai</th>
                                        <td class="text-right">Rp
                                            {{ number_format((float) ($rekapGrandTotals['total_perbekalan_terpakai'] ?? 0), 2, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Operasional Trip</th>
                                        <td class="text-right">Rp
                                            {{ number_format((float) ($rekapGrandTotals['total_operasional'] ?? 0), 2, ',', '.') }}
                                        </td>
                                    </tr>
                                    <tr class="font-weight-bold table-light">
                                        <th>Total Biaya</th>
                                        <td class="text-right">Rp {{ number_format($tripCostTotal, 2, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Perbandingan Tangkapan vs {{ $salesLabel }}</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Komoditas</th>
                                    <th class="text-right">Tangkapan (kg)</th>
                                    <th class="text-right">Nilai Tangkapan</th>
                                    <th class="text-right">Penjualan (kg)</th>
                                    <th class="text-right">Nilai Penjualan</th>
                                    <th class="text-right">Selisih Berat</th>
                                    <th class="text-right">Stok Kapal Saat Ini</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reportRows as $row)
                                    <tr>
                                        <td>{{ $row->commodity_name }}</td>
                                        <td class="text-right">{{ number_format((float) $row->catch_weight, 2, ',', '.') }}
                                        </td>
                                        <td class="text-right">Rp
                                            {{ number_format((float) $row->catch_value, 2, ',', '.') }}</td>
                                        <td class="text-right">{{ number_format((float) $row->sales_weight, 2, ',', '.') }}
                                        </td>
                                        <td class="text-right">Rp
                                            {{ number_format((float) $row->sales_value, 2, ',', '.') }}</td>
                                        <td
                                            class="text-right {{ (float) $row->weight_gap >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format((float) $row->weight_gap, 2, ',', '.') }}
                                        </td>
                                        <td class="text-right">
                                            {{ number_format((float) $row->current_storage_weight, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada data tangkapan untuk
                                            trip ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($reportRows->isNotEmpty())
                                <tfoot>
                                    <tr class="font-weight-bold table-light">
                                        <td>Total</td>
                                        <td class="text-right">
                                            {{ number_format((float) $reportRows->sum('catch_weight'), 2, ',', '.') }}</td>
                                        <td class="text-right">Rp {{ number_format($tripCatchTotal, 2, ',', '.') }}</td>
                                        <td class="text-right">
                                            {{ number_format((float) $reportRows->sum('sales_weight'), 2, ',', '.') }}</td>
                                        <td class="text-right">Rp {{ number_format($salesTotal, 2, ',', '.') }}</td>
                                        <td class="text-right">
                                            {{ number_format((float) $reportRows->sum('weight_gap'), 2, ',', '.') }}</td>
                                        <td class="text-right">{{ number_format($storageTotalWeight, 2, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Pemakaian Perbekalan</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Barang</th>
                                            <th class="text-right">Terpakai</th>
                                            <th class="text-right">Biaya</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($rekapPerbekalan as $row)
                                            <tr>
                                                <td>{{ $row->nama_barang }}</td>
                                                <td class="text-right">
                                                    {{ number_format((float) $row->jumlah_terpakai, 2, ',', '.') }}
                                                    {{ $row->satuan }}</td>
                                                <td class="text-right">Rp
                                                    {{ number_format((float) $row->total_biaya, 2, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Belum ada pemakaian
                                                    perbekalan.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Operasional Trip</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Biaya</th>
                                            <th class="text-right">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($rekapOperasional['detail'] as $detail)
                                            <tr>
                                                <td>{{ $detail->tanggal ? \Carbon\Carbon::parse($detail->tanggal)->format('d-m-Y') : '-' }}
                                                </td>
                                                <td>{{ $detail->nama_operasional ?? ($detail->deskripsi ?? '-') }}</td>
                                                <td class="text-right">Rp
                                                    {{ number_format((float) $detail->jumlah, 2, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Belum ada biaya
                                                    operasional.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
