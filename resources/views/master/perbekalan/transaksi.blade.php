@extends('layouts.layout')

@section('title', 'Transaksi Perbekalan')

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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Transaksi Perbekalan (IN/OUT)</h4>
                            <p class="card-description mb-0">Gunakan transaksi IN untuk pembelian/stok masuk dan OUT untuk
                                pemakaian. Harga per transaksi disimpan agar item yang sama bisa punya harga berbeda.</p>
                        </div>
                        <a href="{{ route('master.perbekalan.history') }}" class="btn btn-outline-primary">Buka Riwayat</a>
                    </div>

                    <form action="{{ route('master.perbekalan.transactions.store') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label class="required-asterisk" for="tanggal_transaksi">Tanggal</label>
                                <input type="date" id="tanggal_transaksi" name="tanggal_transaksi" class="form-control"
                                    value="{{ old('tanggal_transaksi', now()->toDateString()) }}" required>
                            </div>
                            <div class="form-group col-md-3">
                                <label class="required-asterisk" for="id_barang">Perbekalan</label>
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
                                <label class="required-asterisk" for="jenis_transaksi">Jenis</label>
                                <select id="jenis_transaksi" name="jenis_transaksi" class="form-control" required>
                                    <option value="in" @selected(old('jenis_transaksi', 'in') === 'in')>IN</option>
                                    <option value="out" @selected(old('jenis_transaksi') === 'out')>OUT</option>
                                </select>
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
                                <label class="required-asterisk" for="mode_transaksi">Mode IN</label>
                                <select id="mode_transaksi" name="mode_transaksi" class="form-control">
                                    <option value="normal" @selected(old('mode_transaksi', 'normal') === 'normal')>Pembelian</option>
                                    <option value="import_awal" @selected(old('mode_transaksi') === 'import_awal')>Import Stok Awal</option>
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="required-asterisk" for="akun_pembayaran">Bayar Dari</label>
                                <select id="akun_pembayaran" name="akun_pembayaran" class="form-control">
                                    <option value="">-</option>
                                    <option value="kas" @selected(old('akun_pembayaran', 'kas') === 'kas')>Kas</option>
                                    <option value="bank" @selected(old('akun_pembayaran') === 'bank')>Bank</option>
                                    <option value="hutang" @selected(old('akun_pembayaran') === 'hutang')>Hutang</option>
                                </select>
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

                        <small class="text-muted d-block mt-2">
                            Untuk transaksi <strong>IN</strong> mode <strong>Pembelian</strong>, saldo akun yang dipilih
                            (Kas/Bank) akan terpotong. Jika pilih <strong>Hutang</strong>, transaksi dicatat sebagai
                            hutang di arus kas dan bisa dibayar dari halaman riwayat. Pilih mode
                            <strong>Import Stok Awal</strong> untuk input stok existing tanpa potong kas. Untuk
                            <strong>OUT</strong>, akun pembayaran diabaikan.
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
            const jenis = document.getElementById('jenis_transaksi');
            const akun = document.getElementById('akun_pembayaran');
            const mode = document.getElementById('mode_transaksi');

            if (!jenis || !akun || !mode) return;

            const syncAkunState = () => {
                const isIn = jenis.value === 'in';
                const isImportAwal = mode.value === 'import_awal';

                mode.disabled = !isIn;
                if (!isIn) {
                    mode.value = 'normal';
                }

                akun.disabled = !isIn || isImportAwal;
                if (!isIn) {
                    akun.value = '';
                } else if (isImportAwal) {
                    akun.value = '';
                } else if (!akun.value) {
                    akun.value = 'kas';
                }
            };

            jenis.addEventListener('change', syncAkunState);
            mode.addEventListener('change', syncAkunState);
            syncAkunState();
        })();
    </script>
@endpush
