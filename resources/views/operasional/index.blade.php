@extends('layouts.layout')

@section('title', 'Operasional Kantor per Pelayaran')

@section('content')
    <div class="row">
        <div class="col-12">
            @if ($errors->has('message'))
                <x-alert type="danger" :message="$errors->first('message') ?? null" />
            @elseif (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            @if ($errors->any() && !$errors->has('message'))
                <x-alert type="danger" :message="$errors->first()" />
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-1">Input Biaya Operasional</h4>
                    <p class="card-description mb-4">
                        Pilih data sail/pelayaran, lalu isi biaya operasional berdasarkan master operasional.
                    </p>

                    <form action="{{ route('operasional.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="id_pelayaran">Pilih Sail / Pelayaran</label>
                            <select id="id_pelayaran" name="id_pelayaran" class="form-control" required>
                                <option value="">Pilih sail</option>
                                @foreach ($pelayaran as $sail)
                                    <option value="{{ $sail->id_pelayaran }}"
                                        @selected((int) old('id_pelayaran') === (int) $sail->id_pelayaran)>
                                        #{{ $sail->id_pelayaran }} - {{ $sail->nama_kapal }}
                                        ({{ \Carbon\Carbon::parse($sail->tanggal_berangkat)->format('d-m-Y') }}
                                        s/d {{ \Carbon\Carbon::parse($sail->tanggal_tiba)->format('d-m-Y') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="table-responsive mt-4">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Jenis Operasional</th>
                                        <th>Deskripsi Master</th>
                                        <th>Tanggal Biaya</th>
                                        <th>Jumlah</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($masterOperasional as $master)
                                        <tr>
                                            <td>{{ $master->nama_operasional }}</td>
                                            <td>{{ $master->deskripsi ?: '-' }}</td>
                                            <td style="min-width: 170px;">
                                                <input type="date"
                                                    name="tanggal[{{ $master->id_master_operasional }}]"
                                                    class="form-control"
                                                    value="{{ old('tanggal.'.$master->id_master_operasional, now()->toDateString()) }}">
                                            </td>
                                            <td style="min-width: 170px;">
                                                <input type="number"
                                                    name="jumlah[{{ $master->id_master_operasional }}]"
                                                    class="form-control"
                                                    step="0.01"
                                                    min="0"
                                                    value="{{ old('jumlah.'.$master->id_master_operasional) }}"
                                                    placeholder="Isi jika ada biaya">
                                            </td>
                                            <td style="min-width: 260px;">
                                                <input type="text"
                                                    name="deskripsi[{{ $master->id_master_operasional }}]"
                                                    class="form-control"
                                                    value="{{ old('deskripsi.'.$master->id_master_operasional) }}"
                                                    placeholder="Catatan tambahan">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Master operasional belum tersedia.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary">Simpan Biaya Operasional</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Rekap Biaya Operasional per Pelayaran</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Sail</th>
                                    <th>Kapal</th>
                                    <th>Rute</th>
                                    <th>Periode</th>
                                    <th>Total Item</th>
                                    <th>Total Biaya</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rekapSail as $row)
                                    @php
                                        $collapseId = 'detail-operasional-'.$row->id_pelayaran;
                                        $detailRows = $detailBiaya[$row->id_pelayaran] ?? collect();
                                    @endphp
                                    <tr>
                                        <td>#{{ $row->id_pelayaran }}</td>
                                        <td>{{ $row->nama_kapal }}</td>
                                        <td>{{ $row->pelabuhan_asal }} -> {{ $row->pelabuhan_tujuan }}</td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($row->tanggal_berangkat)->format('d-m-Y') }}
                                            s/d
                                            {{ \Carbon\Carbon::parse($row->tanggal_tiba)->format('d-m-Y') }}
                                        </td>
                                        <td>{{ $row->total_item_biaya }}</td>
                                        <td>Rp {{ number_format((float) $row->total_biaya, 2, ',', '.') }}</td>
                                        <td class="text-right">
                                            <button class="btn btn-outline-info btn-sm" type="button" data-toggle="collapse"
                                                data-target="#{{ $collapseId }}" aria-expanded="false"
                                                aria-controls="{{ $collapseId }}">
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
                                                                <td>{{ \Carbon\Carbon::parse($detail->tanggal)->format('d-m-Y') }}</td>
                                                                <td>{{ $detail->nama_operasional ?: $detail->jenis_biaya }}</td>
                                                                <td>Rp {{ number_format((float) $detail->jumlah, 2, ',', '.') }}</td>
                                                                <td>{{ $detail->deskripsi ?: '-' }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4" class="text-center text-muted">Tidak ada detail biaya.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada data operasional tercatat.</td>
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
