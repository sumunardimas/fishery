@extends('layouts.layout')

@section('title', 'Pembelian Barang Kantor')

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
                    <h4 class="card-title mb-1">Input Transaksi Pembelian/Pemakaian</h4>
                    <p class="card-description mb-4">Catat transaksi masuk (IN) atau keluar (OUT) barang kantor.</p>

                    <form action="{{ route('pembelian.transactions.store') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label for="tanggal_transaksi">Tanggal</label>
                                <input type="date" id="tanggal_transaksi" name="tanggal_transaksi" class="form-control"
                                    value="{{ old('tanggal_transaksi', now()->toDateString()) }}" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="id_item_pembelian">Item</label>
                                <select id="id_item_pembelian" name="id_item_pembelian" class="form-control" required>
                                    <option value="">Pilih item</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id_item_pembelian }}" @selected((int) old('id_item_pembelian') === (int) $item->id_item_pembelian)>
                                            {{ $item->nama_item }} ({{ $item->satuan }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="id_gudang">Gudang</label>
                                <select id="id_gudang" name="id_gudang" class="form-control" required>
                                    <option value="">Pilih gudang</option>
                                    @foreach ($gudangs as $gudang)
                                        <option value="{{ $gudang->id_gudang }}" @selected((int) old('id_gudang') === (int) $gudang->id_gudang)>
                                            {{ $gudang->nama_gudang }}
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
                                    class="form-control" value="{{ old('harga_satuan') }}" placeholder="Opsional">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label for="sumber_tujuan">Supplier / Tujuan</label>
                                <input type="text" id="sumber_tujuan" name="sumber_tujuan" class="form-control"
                                    value="{{ old('sumber_tujuan') }}"
                                    placeholder="Contoh: Toko ATK / Dipakai divisi admin">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="keterangan">Keterangan</label>
                                <input type="text" id="keterangan" name="keterangan" class="form-control"
                                    value="{{ old('keterangan') }}">
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Simpan Transaksi</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
