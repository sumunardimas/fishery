@extends('layouts.layout')

@section('title', 'Master Operasional Kantor')

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
                    <h4 class="card-title mb-1">Master Operasional Kantor</h4>
                    <p class="card-description mb-4">Kelola item biaya yang akan dipakai pada transaksi operasional kantor.
                    </p>

                    <form action="{{ route('operasional-kantor.master.store') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-5">
                                <label for="item">Nama Item</label>
                                <input type="text" id="item" name="item" class="form-control"
                                    value="{{ old('item') }}" required>
                            </div>
                            <div class="form-group col-md-5">
                                <label for="kategori">Kategori</label>
                                <select id="kategori" name="kategori" class="form-control" required>
                                    <option value="">Pilih kategori</option>
                                    @foreach (['Operasional', 'Gaji', 'Retribusi', 'Transportasi'] as $kategori)
                                        <option value="{{ $kategori }}" @selected(old('kategori') === $kategori)>{{ $kategori }}
                                        </option>
                                    @endforeach
                                </select>
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
                    <h5 class="card-title">Daftar Item Operasional Kantor</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item</th>
                                    <th>Kategori</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>#{{ $item->id_master_operasional_kantor }}</td>
                                        <td>{{ $item->item }}</td>
                                        <td>{{ $item->kategori }}</td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                data-toggle="modal"
                                                data-target="#editOperasionalKantor{{ $item->id_master_operasional_kantor }}">Edit</button>
                                            <form action="{{ route('operasional-kantor.master.destroy', $item) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Hapus item {{ addslashes($item->item) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade"
                                        id="editOperasionalKantor{{ $item->id_master_operasional_kantor }}" tabindex="-1"
                                        role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Master Operasional Kantor</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('operasional-kantor.master.update', $item) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Nama Item</label>
                                                            <input type="text" name="item" class="form-control"
                                                                value="{{ $item->item }}" required>
                                                        </div>
                                                        <div class="form-group mb-0">
                                                            <label>Kategori</label>
                                                            <select name="kategori" class="form-control" required>
                                                                @foreach (['Operasional', 'Gaji', 'Retribusi', 'Transportasi'] as $kategori)
                                                                    <option value="{{ $kategori }}"
                                                                        @selected($item->kategori === $kategori)>{{ $kategori }}
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
                                        <td colspan="4" class="text-center text-muted">Belum ada data master operasional
                                            kantor.</td>
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
