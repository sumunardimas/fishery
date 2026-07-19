@extends('layouts.layout')

@section('title', 'Barang Masuk')

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
                    <h4 class="card-title mb-1">Goods Purchase and Receive</h4>
                    <p class="card-description mb-4">
                        Catat pembelian atau penerimaan stok dari item Pembelian Barang dan Perbekalan.
                    </p>

                    <form action="{{ route('barang-masuk.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="jenis_transaksi" value="in">

                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label class="required-asterisk" for="tanggal_transaksi">Tanggal</label>
                                <input type="date" id="tanggal_transaksi" name="tanggal_transaksi" class="form-control"
                                    value="{{ old('tanggal_transaksi', now()->toDateString()) }}" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="required-asterisk" for="item">Item</label>
                                <select id="item" name="item" class="form-control" required>
                                    <option value="">Pilih item</option>
                                    <optgroup label="Pembelian Barang">
                                        @foreach ($itemPembelian as $item)
                                            <option value="pembelian:{{ $item->id }}" @selected(old('item') === 'pembelian:'.$item->id)>
                                                {{ $item->nama }} ({{ $item->satuan }}) - Barang
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Perbekalan">
                                        @foreach ($perbekalan as $item)
                                            <option value="perbekalan:{{ $item->id }}" @selected(old('item') === 'perbekalan:'.$item->id)>
                                                {{ $item->nama }} ({{ $item->satuan }}) - Perbekalan
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                <small class="form-text text-muted">Kelompok item ditampilkan agar item bernama sama tetap mudah dibedakan.</small>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="required-asterisk" for="jumlah">Jumlah</label>
                                <input type="number" step="0.01" min="0.01" id="jumlah" name="jumlah"
                                    class="form-control" value="{{ old('jumlah') }}" required>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="required-asterisk" for="harga_satuan">Harga Satuan</label>
                                <input type="text" id="harga_satuan" name="harga_satuan" class="form-control"
                                    data-rupiah-input value="{{ old('harga_satuan') }}" placeholder="0,00">
                            </div>
                            <div class="form-group col-md-2">
                                <label class="required-asterisk" for="mode_transaksi">Mode</label>
                                <select id="mode_transaksi" name="mode_transaksi" class="form-control">
                                    <option value="normal" @selected(old('mode_transaksi', 'normal') === 'normal')>Pembelian</option>
                                    <option value="import_awal" @selected(old('mode_transaksi') === 'import_awal')>Import Stok Awal</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label class="required-asterisk" for="akun_pembayaran">Bayar Dari</label>
                                <select id="akun_pembayaran" name="akun_pembayaran" class="form-control">
                                    <option value="">-</option>
                                    <option value="kas" @selected(old('akun_pembayaran', 'kas') === 'kas')>Kas</option>
                                    <option value="bank" @selected(old('akun_pembayaran') === 'bank')>Bank</option>
                                    <option value="hutang" @selected(old('akun_pembayaran') === 'hutang')>Hutang</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label for="sumber_tujuan">Supplier</label>
                                <input type="text" id="sumber_tujuan" name="sumber_tujuan" class="form-control"
                                    value="{{ old('sumber_tujuan') }}" placeholder="Nama supplier">
                            </div>
                            <div class="form-group col-md-5">
                                <label for="keterangan">Keterangan</label>
                                <input type="text" id="keterangan" name="keterangan" class="form-control"
                                    value="{{ old('keterangan') }}" placeholder="Opsional">
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Simpan Barang Masuk</button>
                            </div>
                        </div>

                        <small class="text-muted d-block mt-2">
                            Mode <strong>Pembelian</strong> menambah stok dan mencatat pembayaran dari Kas, Bank, atau
                            Hutang. Mode <strong>Import Stok Awal</strong> hanya menambah stok tanpa mencatat pembayaran.
                        </small>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const akun = document.getElementById('akun_pembayaran');
            const mode = document.getElementById('mode_transaksi');

            if (!akun || !mode) return;

            const syncAkunState = () => {
                const isImportAwal = mode.value === 'import_awal';
                akun.disabled = isImportAwal;

                if (isImportAwal) {
                    akun.value = '';
                } else if (!akun.value) {
                    akun.value = 'kas';
                }
            };

            mode.addEventListener('change', syncAkunState);
            syncAkunState();
        })();
    </script>
@endpush
