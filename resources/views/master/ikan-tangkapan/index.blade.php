@extends('layouts.layout')

@section('title', 'Master Ikan Tangkapan')

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
                    <h4 class="card-title mb-1">Master Ikan Tangkapan</h4>
                    <p class="card-description mb-4">Kelola kelompok ikan tangkapan untuk kebutuhan klasifikasi data.</p>

                    <form action="{{ route('master.ikan-tangkapan.store') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-10">
                                <label for="nama_ikan_tangkapan">Nama Ikan Tangkapan</label>
                                <input type="text" id="nama_ikan_tangkapan" name="nama_ikan_tangkapan"
                                    class="form-control" value="{{ old('nama_ikan_tangkapan') }}" required>
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100">Tambah</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Daftar Ikan Tangkapan</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Ikan Tangkapan</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>#{{ $item->id_ikan_tangkapan }}</td>
                                        <td>{{ $item->nama_ikan_tangkapan }}</td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                data-toggle="modal"
                                                data-target="#editIkanTangkapan{{ $item->id_ikan_tangkapan }}">Edit</button>
                                            <form action="{{ route('master.ikan-tangkapan.destroy', $item) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Hapus data ikan tangkapan {{ addslashes($item->nama_ikan_tangkapan) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editIkanTangkapan{{ $item->id_ikan_tangkapan }}"
                                        tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Master Ikan Tangkapan</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('master.ikan-tangkapan.update', $item) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="form-group mb-0">
                                                            <label>Nama Ikan Tangkapan</label>
                                                            <input type="text" name="nama_ikan_tangkapan"
                                                                class="form-control"
                                                                value="{{ $item->nama_ikan_tangkapan }}" required>
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
                                        <td colspan="3" class="text-center text-muted">Belum ada data ikan tangkapan.
                                        </td>
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
