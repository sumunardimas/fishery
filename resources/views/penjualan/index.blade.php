@extends('layouts.layout')

@section('title', 'POS Penjualan Ikan')

@section('content')
    <div class="row" x-data="{
        createNewCustomer: false,
        ikanMap: {{ Js::from($ikanStock->keyBy('id_ikan')->map(fn($i) => ['stok_tersedia' => $i->stok_tersedia])) }},
        selectedIkan: '{{ old('id_ikan') }}',
        berat: {{ (float) old('berat', 0) }},
        hargaPerKg: {{ (float) old('harga_per_kg', 0) }},
        bayarTunai: {{ (float) old('bayar_tunai', 0) }},
        bayarTransfer: {{ (float) old('bayar_transfer', 0) }},
        totalHarga() {
            return this.berat * this.hargaPerKg;
        },
        piutang() {
            return Math.max(0, this.totalHarga() - this.bayarTunai - this.bayarTransfer);
        },
        statusPembayaran() {
            return this.piutang() <= 0 ? 'Lunas' : 'Piutang';
        },
        stokTersedia() {
            if (!this.selectedIkan) return 0;
            return this.ikanMap[this.selectedIkan]?.stok_tersedia ?? 0;
        }
    }">
        <div class="col-12">
            @if ($errors->has('message'))
                <x-alert type="danger" :message="$errors->first('message') ?? null" />
            @elseif (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            <div class="card mb-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">POS Penjualan Ikan</h4>
                        <p class="card-description mb-0">Penjualan berdasarkan stok hasil tangkapan yang tersedia.</p>
                    </div>
                    <a href="{{ route('penjualan.riwayat') }}" class="btn btn-outline-primary">Riwayat Transaksi</a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Transaksi Penjualan Baru</h5>
                    <form action="{{ route('penjualan.store') }}" method="POST">
                        @csrf

                        <div class="form-group form-check mb-3" style="padding-left: 1.5rem;">
                            <input type="checkbox" class="form-check-input" id="create_new_customer"
                                name="create_new_customer" value="1" x-model="createNewCustomer"
                                {{ old('create_new_customer') ? 'checked' : '' }}>
                            <label class="form-check-label" for="create_new_customer">Tambah customer baru</label>
                        </div>

                        <div x-show="!createNewCustomer" class="form-group">
                            <label class="required-asterisk" for="id_customer">Customer</label>
                            <select class="form-control @error('id_customer') is-invalid @enderror" name="id_customer"
                                id="id_customer" :required="!createNewCustomer">
                                <option value="">Pilih customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id_customer }}"
                                        {{ (string) old('id_customer') === (string) $customer->id_customer ? 'selected' : '' }}>
                                        {{ $customer->nama_customer }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_customer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div x-show="createNewCustomer" class="border rounded p-3 mb-3">
                            <div class="form-group">
                                <label class="required-asterisk" for="nama_customer_baru">Nama Customer</label>
                                <input type="text" class="form-control @error('nama_customer_baru') is-invalid @enderror"
                                    name="nama_customer_baru" id="nama_customer_baru"
                                    value="{{ old('nama_customer_baru') }}" :required="createNewCustomer">
                                @error('nama_customer_baru')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="alamat_customer_baru">Alamat (Opsional)</label>
                                <input type="text"
                                    class="form-control @error('alamat_customer_baru') is-invalid @enderror"
                                    name="alamat_customer_baru" id="alamat_customer_baru"
                                    value="{{ old('alamat_customer_baru') }}">
                                @error('alamat_customer_baru')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-0">
                                <label for="telepon_customer_baru">Telepon (Opsional)</label>
                                <input type="text"
                                    class="form-control @error('telepon_customer_baru') is-invalid @enderror"
                                    name="telepon_customer_baru" id="telepon_customer_baru"
                                    value="{{ old('telepon_customer_baru') }}">
                                @error('telepon_customer_baru')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="required-asterisk" for="id_ikan">Pilih Ikan</label>
                                <select class="form-control @error('id_ikan') is-invalid @enderror" name="id_ikan"
                                    id="id_ikan" x-model="selectedIkan" @change="updateHarga()" required>
                                    <option value="">Pilih ikan</option>
                                    @foreach ($ikanStock as $ikan)
                                        <option value="{{ $ikan->id_ikan }}"
                                            {{ (string) old('id_ikan') === (string) $ikan->id_ikan ? 'selected' : '' }}>
                                            {{ $ikan->nama_ikan }} - Stok: {{ number_format($ikan->stok_tersedia, 2) }} kg
                                        </option>
                                    @endforeach
                                </select>
                                @error('id_ikan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 form-group">
                                <label class="required-asterisk" for="berat">Jumlah (kg)</label>
                                <input type="number" min="0.01" step="0.01" x-model.number="berat"
                                    class="form-control @error('berat') is-invalid @enderror" name="berat" id="berat"
                                    value="{{ old('berat') }}" required>
                                @error('berat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 form-group">
                                <label class="required-asterisk" for="harga_per_kg">Harga per kg</label>
                                <input type="number" min="1" step="0.01" x-model.number="hargaPerKg"
                                    class="form-control @error('harga_per_kg') is-invalid @enderror" name="harga_per_kg"
                                    id="harga_per_kg" value="{{ old('harga_per_kg') }}" required>
                                @error('harga_per_kg')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">Stok tersedia saat ini: <strong
                                    x-text="Number(stokTersedia()).toFixed(2)"></strong> kg</small>
                        </div>

                        <div class="card bg-light mb-3">
                            <div class="card-body py-2">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Total Tagihan</small>
                                        <strong
                                            x-text="'Rp ' + totalHarga().toLocaleString('id-ID', {minimumFractionDigits: 2})"></strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Sisa Piutang</small>
                                        <strong :class="piutang() > 0 ? 'text-danger' : 'text-success'"
                                            x-text="'Rp ' + piutang().toLocaleString('id-ID', {minimumFractionDigits: 2})"></strong>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Status</small>
                                        <span :class="piutang() > 0 ? 'badge badge-warning' : 'badge badge-success'"
                                            x-text="statusPembayaran()"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="bayar_tunai">Bayar Tunai (Rp)</label>
                                <input type="number" min="0" step="0.01" x-model.number="bayarTunai"
                                    class="form-control @error('bayar_tunai') is-invalid @enderror" name="bayar_tunai"
                                    id="bayar_tunai" value="{{ old('bayar_tunai', 0) }}">
                                @error('bayar_tunai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="bayar_transfer">Bayar Transfer (Rp)</label>
                                <input type="number" min="0" step="0.01" x-model.number="bayarTransfer"
                                    class="form-control @error('bayar_transfer') is-invalid @enderror"
                                    name="bayar_transfer" id="bayar_transfer" value="{{ old('bayar_transfer', 0) }}">
                                @error('bayar_transfer')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>



                        <div class="form-group">
                            <label for="keterangan">Catatan Penjualan</label>
                            <textarea name="keterangan" id="keterangan" rows="2" class="form-control">{{ old('keterangan') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Simpan Transaksi
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
