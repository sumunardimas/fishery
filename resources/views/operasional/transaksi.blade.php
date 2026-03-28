@extends('layouts.layout')

@section('title', 'Input Biaya Operasional')

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
                                    <option value="{{ $sail->id_pelayaran }}" @selected((int) old('id_pelayaran') === (int) $sail->id_pelayaran)>
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
                                                <input type="date" name="tanggal[{{ $master->id_master_operasional }}]"
                                                    class="form-control"
                                                    value="{{ old('tanggal.' . $master->id_master_operasional, now()->toDateString()) }}">
                                            </td>
                                            <td style="min-width: 170px;">
                                                <input type="number" name="jumlah[{{ $master->id_master_operasional }}]"
                                                    class="form-control" step="0.01" min="0"
                                                    value="{{ old('jumlah.' . $master->id_master_operasional) }}"
                                                    placeholder="Isi jika ada biaya">
                                            </td>
                                            <td style="min-width: 260px;">
                                                <input type="text"
                                                    name="deskripsi[{{ $master->id_master_operasional }}]"
                                                    class="form-control"
                                                    value="{{ old('deskripsi.' . $master->id_master_operasional) }}"
                                                    placeholder="Catatan tambahan">
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Master operasional belum
                                                tersedia.</td>
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
        </div>
    </div>
@endsection
