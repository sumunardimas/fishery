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
                    <h4 class="card-title mb-1">Transaksi Perbekalan (IN/OUT)</h4>
                    <p class="card-description mb-4">Gunakan transaksi IN untuk pembelian/stok masuk dan OUT untuk pemakaian.
                        Harga per transaksi disimpan agar item yang sama bisa punya harga berbeda.</p>

                    <form action="{{ route('master.perbekalan.transactions.store') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="tanggal_transaksi">Tanggal</label>
                                <input type="date" id="tanggal_transaksi" name="tanggal_transaksi" class="form-control"
                                    value="{{ old('tanggal_transaksi', now()->toDateString()) }}" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="id_barang">Perbekalan</label>
                                <select id="id_barang" name="id_barang" class="form-control" required>
                                    <option value="">Pilih perbekalan</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id_barang }}" @selected((int) old('id_barang', $selectedItemId) === (int) $item->id_barang)>
                                            {{ $item->nama_barang }} ({{ $item->satuan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-1">
                                <label for="jenis_transaksi">Jenis</label>
                                <select id="jenis_transaksi" name="jenis_transaksi" class="form-control" required>
                                    <option value="in" @selected(old('jenis_transaksi', 'in') === 'in')>IN</option>
                                    <option value="out" @selected(old('jenis_transaksi') === 'out')>OUT</option>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="jumlah">Jumlah</label>
                                <input type="number" step="0.01" min="0.01" id="jumlah" name="jumlah"
                                    class="form-control" value="{{ old('jumlah') }}" required>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="harga_satuan">Harga Satuan</label>
                                <input type="number" step="0.01" min="0" id="harga_satuan" name="harga_satuan"
                                    class="form-control" value="{{ old('harga_satuan') }}" placeholder="Wajib untuk IN">
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Simpan Transaksi</button>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="sumber_tujuan">Supplier / Tujuan</label>
                                <input type="text" id="sumber_tujuan" name="sumber_tujuan" class="form-control"
                                    value="{{ old('sumber_tujuan') }}" placeholder="Contoh: Supplier A / Dipakai Trip 12">
                            </div>
                            <div class="form-group col-md-8">
                                <label for="keterangan">Keterangan</label>
                                <input type="text" id="keterangan" name="keterangan" class="form-control"
                                    value="{{ old('keterangan') }}" placeholder="Opsional">
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-1">Master Perbekalan</h4>
                    <p class="card-description mb-4">Kelola daftar item perbekalan yang dipakai di operasional.</p>

                    <form action="{{ route('master.perbekalan.store') }}" method="POST" class="mb-4">
                        @csrf
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-5">
                                <label for="nama_barang">Nama Barang</label>
                                <input type="text" id="nama_barang" name="nama_barang" class="form-control"
                                    value="{{ old('nama_barang') }}" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="satuan">Satuan</label>
                                <input type="text" id="satuan" name="satuan" class="form-control"
                                    value="{{ old('satuan') }}" required>
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
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>#{{ $item->id_barang }}</td>
                                        <td>{{ $item->nama_barang }}</td>
                                        <td>{{ $item->satuan }}</td>
                                        <td>{{ number_format((float) $item->stok_aktual, 2, ',', '.') }}</td>
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
                                                <button type="submit"
                                                    class="btn btn-outline-danger btn-sm">Hapus</button>
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
                                                        <div class="form-group mb-0">
                                                            <label>Satuan</label>
                                                            <input type="text" name="satuan" class="form-control"
                                                                value="{{ $item->satuan }}" required>
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
                                        <td colspan="5" class="text-center text-muted">Belum ada data perbekalan.</td>
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
