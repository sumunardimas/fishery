@extends('layouts.layout')

@section('title', 'Rekap Biaya Operasional per Pelayaran')

@section('content')
    <div class="row">
        <div class="col-12">
            @if ($errors->has('message'))
                <x-alert type="danger" :message="$errors->first('message') ?? null" />
            @elseif (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Rekap Biaya Operasional per Pelayaran</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Sail</th>
                                    <th>Kapal</th>
                                    <th>Keterangan</th>
                                    <th>Periode</th>
                                    <th>Total Item</th>
                                    <th>Total Biaya</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rekapSail as $row)
                                    @php
                                        $collapseId = 'detail-operasional-' . $row->id_pelayaran;
                                        $detailRows = $detailBiaya[$row->id_pelayaran] ?? collect();
                                    @endphp
                                    <tr>
                                        <td>#{{ $row->id_pelayaran }}</td>
                                        <td>{{ $row->nama_kapal }}</td>
                                        <td>{{ $row->keterangan ?: '-' }}</td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($row->tanggal_berangkat)->format('d-m-Y') }}
                                            s/d
                                            {{ \Carbon\Carbon::parse($row->tanggal_tiba)->format('d-m-Y') }}
                                        </td>
                                        <td>{{ $row->total_item_biaya }}</td>
                                        <td>Rp {{ number_format((float) $row->total_biaya, 2, ',', '.') }}</td>
                                        <td class="text-right">
                                            <button class="btn btn-outline-info btn-sm" type="button"
                                                data-toggle="collapse" data-target="#{{ $collapseId }}"
                                                aria-expanded="false" aria-controls="{{ $collapseId }}">
                                                Lihat Detail
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="{{ $collapseId }}">
                                        <td colspan="7" class="bg-light">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Tanggal</th>
                                                            <th>Jenis Operasional</th>
                                                            <th>Jumlah</th>
                                                            <th>Deskripsi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($detailRows as $detail)
                                                            <tr>
                                                                <td>{{ \Carbon\Carbon::parse($detail->tanggal)->format('d-m-Y') }}
                                                                </td>
                                                                <td>{{ $detail->nama_operasional ?: $detail->jenis_biaya }}
                                                                </td>
                                                                <td>Rp
                                                                    {{ number_format((float) $detail->jumlah, 2, ',', '.') }}
                                                                </td>
                                                                <td>{{ $detail->deskripsi ?: '-' }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4" class="text-center text-muted">Tidak ada
                                                                    detail biaya.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada data operasional
                                            tercatat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
