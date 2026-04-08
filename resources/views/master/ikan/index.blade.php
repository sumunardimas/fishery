@extends('layouts.layout')

@section('title', 'Master Ikan')

@section('content')
    <div class="row">
        <div class="col-12">
            @if ($errors->has('message'))
                <x-alert type="danger" :message="$errors->first('message') ?? null" />
            @elseif (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-1">Master Ikan</h4>
                    <p class="card-description mb-4">Kelola jenis ikan yang digunakan dalam proses bongkaran dan penjualan.
                    </p>

                    <form action="{{ route('master.ikan.store') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-7">
                                <label for="nama_ikan">Nama Ikan</label>
                                <input type="text" id="nama_ikan" name="nama_ikan" class="form-control"
                                    value="{{ old('nama_ikan') }}" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="id_ikan_tangkapan">Ikan Tangkapan</label>
                                <select id="id_ikan_tangkapan" name="id_ikan_tangkapan" class="form-control">
                                    <option value="">- Pilih Ikan Tangkapan -</option>
                                    @foreach ($ikanTangkapanOptions as $option)
                                        <option value="{{ $option->id_ikan_tangkapan }}" @selected((string) old('id_ikan_tangkapan') === (string) $option->id_ikan_tangkapan)>
                                            {{ $option->nama_ikan_tangkapan }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-1 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100">Tambah</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Daftar Ikan</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Ikan</th>
                                    <th>Ikan Tangkapan</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>#{{ $item->id_ikan }}</td>
                                        <td>{{ $item->nama_ikan }}</td>
                                        <td>{{ $item->ikanTangkapan?->nama_ikan_tangkapan ?: '-' }}</td>

                                        <td class="text-right">
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                data-toggle="modal"
                                                data-target="#editIkan{{ $item->id_ikan }}">Edit</button>
                                            <form action="{{ route('master.ikan.destroy', $item) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Hapus data ikan {{ addslashes($item->nama_ikan) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editIkan{{ $item->id_ikan }}" tabindex="-1" role="dialog"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Master Ikan</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('master.ikan.update', $item) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Nama Ikan</label>
                                                            <input type="text" name="nama_ikan" class="form-control"
                                                                value="{{ $item->nama_ikan }}" required>
                                                        </div>
                                                        <div class="form-group mb-0">
                                                            <label>Ikan Tangkapan</label>
                                                            <select name="id_ikan_tangkapan" class="form-control">
                                                                <option value="">- Pilih Ikan Tangkapan -</option>
                                                                @foreach ($ikanTangkapanOptions as $option)
                                                                    <option value="{{ $option->id_ikan_tangkapan }}"
                                                                        @selected((string) $item->id_ikan_tangkapan === (string) $option->id_ikan_tangkapan)>
                                                                        {{ $option->nama_ikan_tangkapan }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
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
                                        <td colspan="4" class="text-center text-muted">Belum ada data ikan.</td>
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
