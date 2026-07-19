@extends('layouts.layout')

@section('title', 'Master Perbekalan')

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
                    <h4 class="card-title mb-1">Master Perbekalan</h4>
                    <p class="card-description mb-4">Kelola daftar item perbekalan yang dipakai di operasional.</p>

                    <form action="{{ route('master.perbekalan.store') }}" method="POST" class="mb-4">
                        @csrf
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-4">
                                <label for="nama_barang">Nama Barang</label>
                                <input type="text" id="nama_barang" name="nama_barang" class="form-control"
                                    value="{{ old('nama_barang') }}" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="satuan">Satuan</label>
                                <input type="text" id="satuan" name="satuan" class="form-control"
                                    value="{{ old('satuan') }}" required>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="limit_minimal">Limit Minimal</label>
                                <input type="number" id="limit_minimal" name="limit_minimal" class="form-control"
                                    step="0.01" min="0" value="{{ old('limit_minimal', 0) }}" required>
                            </div>
                            <div class="form-group col-md-3">
                                <button type="submit" class="btn btn-success w-100">Tambah Perbekalan</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Barang</th>
                                    <th>Satuan</th>
                                    <th>Stok Aktual</th>
                                    <th>Limit Minimal</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    @php
                                        $stockRowClass = '';
                                        if ((float) $item->limit_minimal > 0) {
                                            $stockRowClass = (float) $item->stok_aktual <= (float) $item->limit_minimal
                                                ? 'table-danger'
                                                : ((float) $item->stok_aktual <= (float) $item->limit_minimal * 1.2
                                                    ? 'table-warning'
                                                    : '');
                                        }
                                    @endphp
                                    <tr class="{{ $stockRowClass }}">
                                        <td>#{{ $item->id_barang }}</td>
                                        <td>{{ $item->nama_barang }}</td>
                                        <td>{{ $item->satuan }}</td>
                                        <td>{{ number_format((float) $item->stok_aktual, 2, ',', '.') }}</td>
                                        <td>{{ number_format((float) $item->limit_minimal, 2, ',', '.') }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('master.perbekalan.history', ['show_item' => $item->id_barang]) }}"
                                                class="btn btn-outline-info btn-sm">Riwayat IN/OUT</a>

                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                data-toggle="modal"
                                                data-target="#editPerbekalan{{ $item->id_barang }}">Edit</button>

                                            <form action="{{ route('master.perbekalan.destroy', $item->id_barang) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Hapus perbekalan {{ addslashes($item->nama_barang) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editPerbekalan{{ $item->id_barang }}" tabindex="-1"
                                        role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Perbekalan</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('master.perbekalan.update', $item->id_barang) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Nama Barang</label>
                                                            <input type="text" name="nama_barang" class="form-control"
                                                                value="{{ $item->nama_barang }}" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Satuan</label>
                                                            <input type="text" name="satuan" class="form-control"
                                                                value="{{ $item->satuan }}" required>
                                                        </div>
                                                        <div class="form-group mb-0">
                                                            <label>Limit Minimal</label>
                                                            <input type="number" name="limit_minimal" class="form-control"
                                                                step="0.01" min="0" value="{{ $item->limit_minimal }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light"
                                                            data-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Simpan
                                                            Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada data perbekalan.</td>
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
