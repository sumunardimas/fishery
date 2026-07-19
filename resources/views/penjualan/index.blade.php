@extends('layouts.layout')

@section('title', 'POS Penjualan Ikan')

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendors/select2/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendors/select2-bootstrap-theme/select2-bootstrap.min.css') }}">
@endpush

@section('content')
    <div class="row" x-data="{
        jenisTransaksi: {{ Js::from(old('jenis_transaksi', 'penjualan')) }},
        createNewCustomer: {{ Js::from((bool) old('create_new_customer', data_get($activeDraftPayload ?? [], 'create_new_customer', false))) }},
        ikanMap: {{ Js::from($ikanStock->keyBy('id_ikan')->map(fn($i) => ['nama_ikan' => $i->nama_ikan, 'stok_tersedia' => $i->stok_tersedia])) }},
        items: {{ Js::from(old('items', data_get($activeDraftPayload ?? [], 'items', [['id_ikan' => '', 'berat' => 0, 'harga_per_kg' => '']]))) }},
        bayarTunai: '{{ old('bayar_tunai', data_get($activeDraftPayload ?? [], 'bayar_tunai', 0)) }}',
        bayarTransfer: '{{ old('bayar_transfer', data_get($activeDraftPayload ?? [], 'bayar_transfer', 0)) }}',
        addItem() {
            this.items.push({ id_ikan: '', berat: 0, harga_per_kg: '' });
            window.requestAnimationFrame(() => {
                window.initFishSearchableSelect && window.initFishSearchableSelect();
                window.rupiahInput && window.rupiahInput.init();
            });
        },
        removeItem(idx) {
            if (this.items.length > 1) this.items.splice(idx, 1);
            window.requestAnimationFrame(() => window.initFishSearchableSelect && window.initFishSearchableSelect());
        },
        parseNominal(value) {
            return window.rupiahInput ? (window.rupiahInput.parse(value) || 0) : (parseFloat(value) || 0);
        },
        subtotal(item) { return this.jenisTransaksi === 'lawuhan' ? 0 : item.berat * this.parseNominal(item.harga_per_kg); },
        totalHarga() { return this.items.reduce((sum, it) => sum + this.subtotal(it), 0); },
        piutang() { return Math.max(0, this.totalHarga() - this.parseNominal(this.bayarTunai) - this.parseNominal(this.bayarTransfer)); },
        statusPembayaran() { return this.piutang() <= 0 ? 'Lunas' : 'Piutang'; },
        stokByItem(item) { return !item.id_ikan ? 0 : (this.ikanMap[item.id_ikan]?.stok_tersedia ?? 0); },
    }" x-init="window.requestAnimationFrame(() => {
        window.initFishSearchableSelect && window.initFishSearchableSelect();
        window.rupiahInput && window.rupiahInput.init();
    })">
        <div class="col-12">
            @if ($errors->has('message'))
                <x-alert type="danger" :message="$errors->first('message') ?? null" />
            @elseif ($errors->has('items'))
                <x-alert type="danger" :message="$errors->first('items')" />
            @elseif (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            <div class="card mb-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">POS Penjualan Ikan</h4>
                        <p class="card-description mb-0">Penjualan tetap cepat. Jika stok kurang saat transaksi harus jalan,
                            simpan sebagai selisih sementara lalu rekonsiliasi belakangan.</p>
                    </div>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('penjualan.selisih.index') }}" class="btn btn-outline-warning mr-2">
                            Selisih Stok
                            @if (($pendingDiscrepancyCount ?? 0) > 0)
                                <span class="badge badge-warning ml-1">{{ $pendingDiscrepancyCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('penjualan.riwayat') }}" class="btn btn-outline-primary">Riwayat Transaksi</a>
                    </div>
                </div>
            </div>

            @if (($cartDrafts ?? collect())->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="border rounded p-3 bg-light">
                            <form action="{{ route('penjualan.index') }}" method="GET" class="row align-items-end">
                                <div class="col-md-8 form-group mb-md-0">
                                    <label for="draft_customer_id" class="mb-1">Muat Keranjang Tersimpan</label>
                                    <select name="draft_customer_id" id="draft_customer_id" class="form-control">
                                        <option value="">Pilih customer</option>
                                        @foreach ($cartDrafts as $draft)
                                            <option value="{{ $draft->id_customer }}"
                                                {{ (int) ($activeDraftCustomerId ?? 0) === (int) $draft->id_customer ? 'selected' : '' }}>
                                                {{ $draft->nama_customer }} (terakhir disimpan:
                                                {{ optional($draft->updated_at)->format('d/m/Y H:i') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-outline-primary w-100">Muat Keranjang</button>
                                </div>
                            </form>

                            @if (($activeDraftCustomerId ?? 0) > 0)
                                <small class="text-muted d-block mt-2">
                                    Keranjang aktif: <strong>{{ $activeDraftCustomerName }}</strong>. Anda bisa tambah item
                                    lagi lalu simpan keranjang ulang atau langsung simpan transaksi.
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Transaksi Penjualan Baru</h5>

                    <form action="{{ route('penjualan.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="required-asterisk" for="tanggal_penjualan">Tanggal Transaksi</label>
                                <input type="date" class="form-control @error('tanggal_penjualan') is-invalid @enderror"
                                    id="tanggal_penjualan" name="tanggal_penjualan"
                                    value="{{ old('tanggal_penjualan', now()->toDateString()) }}"
                                    max="{{ now()->toDateString() }}" required>
                                @error('tanggal_penjualan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="required-asterisk d-block">Jenis Transaksi</label>
                                <div class="d-flex flex-wrap pt-2">
                                    <div class="custom-control custom-radio mr-4">
                                        <input type="radio" class="custom-control-input" id="jenis_penjualan"
                                            name="jenis_transaksi" value="penjualan" x-model="jenisTransaksi"
                                            {{ old('jenis_transaksi', 'penjualan') === 'penjualan' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="jenis_penjualan">Penjualan Biasa</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input type="radio" class="custom-control-input" id="jenis_lawuhan"
                                            name="jenis_transaksi" value="lawuhan" x-model="jenisTransaksi"
                                            {{ old('jenis_transaksi') === 'lawuhan' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="jenis_lawuhan">Lawuhan</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Lawuhan mengurangi stok dan tercatat per pelayaran, tetapi tidak
                                menjadi pendapatan atau piutang.</small>
                        </div>

                        <div x-show="jenisTransaksi === 'lawuhan'" class="form-group">
                            <label class="required-asterisk" for="tujuan_lawuhan">Tujuan Lawuhan</label>
                            <input type="text" class="form-control @error('tujuan_lawuhan') is-invalid @enderror"
                                id="tujuan_lawuhan" name="tujuan_lawuhan" value="{{ old('tujuan_lawuhan') }}"
                                placeholder="Contoh: makan staf atau upacara adat"
                                :required="jenisTransaksi === 'lawuhan'">
                            @error('tujuan_lawuhan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Customer section --}}
                        <div x-show="jenisTransaksi === 'penjualan'" class="form-group form-check mb-3"
                            style="padding-left: 1.5rem;">
                            <input type="checkbox" class="form-check-input" id="create_new_customer"
                                name="create_new_customer" value="1" x-model="createNewCustomer"
                                {{ old('create_new_customer') ? 'checked' : '' }}>
                            <label class="form-check-label" for="create_new_customer">Tambah customer baru</label>
                        </div>

                        <div x-show="jenisTransaksi === 'penjualan' && !createNewCustomer" class="form-group">
                            <label class="required-asterisk" for="id_customer">Customer</label>
                            <select class="form-control @error('id_customer') is-invalid @enderror" name="id_customer"
                                id="id_customer" :required="jenisTransaksi === 'penjualan' && !createNewCustomer">
                                <option value="">Pilih customer</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id_customer }}"
                                        {{ (string) old('id_customer', data_get($activeDraftPayload ?? [], 'id_customer')) === (string) $customer->id_customer ? 'selected' : '' }}>
                                        {{ $customer->nama_customer }}
                                    </option>
                                @endforeach
                            </select>
                            @error('id_customer')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div x-show="jenisTransaksi === 'penjualan' && createNewCustomer" class="border rounded p-3 mb-3">
                            <div class="form-group">
                                <label class="required-asterisk" for="nama_customer_baru">Nama Customer</label>
                                <input type="text"
                                    class="form-control @error('nama_customer_baru') is-invalid @enderror"
                                    name="nama_customer_baru" id="nama_customer_baru"
                                    value="{{ old('nama_customer_baru') }}"
                                    :required="jenisTransaksi === 'penjualan' && createNewCustomer">
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

                        {{-- ── Fish line items ── --}}
                        <div class="mb-2 d-flex justify-content-between align-items-center">
                            <label class="required-asterisk mb-0">Daftar Ikan</label>
                            <button type="button" class="btn btn-sm btn-warning" @click="addItem()">
                                <i class="ti-plus mr-1"></i> Tambah Ikan
                            </button>
                        </div>

                        <template x-for="(item, index) in items" :key="index">
                            <div class="border rounded p-3 mb-2">
                                <div class="row">
                                    <div class="col-md-5 form-group mb-2">
                                        <label class="required-asterisk">Pilih Ikan</label>
                                        <select :name="'items[' + index + '][id_ikan]'" x-model="item.id_ikan"
                                            class="form-control js-ikan-select" data-placeholder="Cari ikan / stok"
                                            required>
                                            <option value="">Pilih ikan</option>
                                            @foreach ($ikanStock as $ikan)
                                                <option value="{{ $ikan->id_ikan }}">
                                                    {{ $ikan->nama_ikan }} — Stok:
                                                    {{ number_format($ikan->stok_tersedia, 2) }} kg
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted" x-show="item.id_ikan">
                                            Stok tersedia: <strong x-text="Number(stokByItem(item)).toFixed(2)"></strong>
                                            kg
                                        </small>
                                    </div>
                                    <div class="col-md-3 form-group mb-2">
                                        <label class="required-asterisk">Jumlah (kg)</label>
                                        <input type="number" min="0.01" step="0.01"
                                            :name="'items[' + index + '][berat]'" x-model.number="item.berat"
                                            class="form-control" required>
                                    </div>
                                    <div class="col-md-3 form-group mb-2">
                                        <label class="required-asterisk">Harga / kg</label>
                                        <input type="text" data-rupiah-input
                                            :name="'items[' + index + '][harga_per_kg]'" x-model="item.harga_per_kg"
                                            class="form-control" placeholder="0,00"
                                            :disabled="jenisTransaksi === 'lawuhan'" required>
                                        <input x-show="jenisTransaksi === 'lawuhan'" type="hidden"
                                            :name="'items[' + index + '][harga_per_kg]'" value="0">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end form-group mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100"
                                            @click="removeItem(index)" x-show="items.length > 1"
                                            title="Hapus baris">&times;</button>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <small class="text-muted">
                                        Subtotal:
                                        <strong
                                            x-text="'Rp ' + subtotal(item).toLocaleString('id-ID', {minimumFractionDigits: 2})"></strong>
                                    </small>
                                </div>
                            </div>
                        </template>

                        <div class="mb-2 d-flex justify-content-end">
                            <button type="button" class="btn btn-sm btn-warning" @click="addItem()">
                                <i class="ti-plus mr-1"></i> Tambah Ikan
                            </button>
                        </div>

                        {{-- ── Payment summary ── --}}
                        <div class="card bg-light mb-3 mt-3">
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

                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="form-group form-check mb-2" style="padding-left: 1.5rem;">
                                <input type="checkbox" class="form-check-input" id="allow_pending_discrepancy"
                                    name="allow_pending_discrepancy" value="1"
                                    {{ old('allow_pending_discrepancy', data_get($activeDraftPayload ?? [], 'allow_pending_discrepancy')) ? 'checked' : '' }}>
                                <label class="form-check-label" for="allow_pending_discrepancy">
                                    Simpan sebagai selisih sementara bila stok kurang
                                </label>
                            </div>
                            <div class="form-group mb-0">
                                <label for="catatan_selisih">Catatan selisih sementara</label>
                                <input type="text" name="catatan_selisih" id="catatan_selisih" class="form-control"
                                    value="{{ old('catatan_selisih', data_get($activeDraftPayload ?? [], 'catatan_selisih')) }}"
                                    placeholder="Contoh: timbang aktual lebih besar / campur jenis ikan">
                                <small class="text-muted d-block mt-1">
                                    Gunakan opsi ini jika transaksi harus tetap diproses cepat. Rekonsiliasi detail bisa
                                    dikerjakan di menu Selisih Stok.
                                </small>
                            </div>
                        </div>

                        {{-- ── Payment inputs ── --}}
                        <div class="row" x-show="jenisTransaksi === 'penjualan'">
                            <div class="col-md-6 form-group">
                                <label for="bayar_tunai">Bayar Tunai (Rp)</label>
                                <input type="text" data-rupiah-input x-model="bayarTunai"
                                    class="form-control @error('bayar_tunai') is-invalid @enderror" name="bayar_tunai"
                                    id="bayar_tunai" value="{{ old('bayar_tunai', 0) }}">
                                @error('bayar_tunai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="bayar_transfer">Bayar Transfer (Rp)</label>
                                <input type="text" data-rupiah-input x-model="bayarTransfer"
                                    class="form-control @error('bayar_transfer') is-invalid @enderror"
                                    name="bayar_transfer" id="bayar_transfer" value="{{ old('bayar_transfer', 0) }}">
                                @error('bayar_transfer')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="keterangan">Catatan Penjualan</label>
                            <textarea name="keterangan" id="keterangan" rows="2" class="form-control">{{ old('keterangan', data_get($activeDraftPayload ?? [], 'keterangan')) }}</textarea>
                        </div>

                        <div class="d-flex flex-wrap">
                            <button x-show="jenisTransaksi === 'penjualan'" type="submit"
                                formaction="{{ route('penjualan.cart-draft.save') }}" formmethod="POST"
                                class="btn btn-outline-secondary mr-2 mb-2">
                                Simpan Keranjang
                            </button>
                            <button type="submit" class="btn btn-primary mb-2">
                                Simpan Transaksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('vendors/select2/select2.min.js') }}"></script>
    <script>
        window.initFishSearchableSelect = function() {
            if (!window.jQuery || !jQuery.fn.select2) {
                return;
            }

            $('.js-ikan-select').each(function() {
                var $select = $(this);

                if ($select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                $select.select2({
                    width: '100%',
                    theme: 'bootstrap',
                    placeholder: $select.data('placeholder') || 'Cari ikan',
                });
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            window.initFishSearchableSelect();
        });
    </script>
@endpush
