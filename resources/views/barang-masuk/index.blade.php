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
                    <h4 class="card-title mb-1">Barang Masuk</h4>
                    <p class="card-description mb-4">
                        Catat pembelian atau penerimaan stok dari item Pembelian Barang dan Perbekalan.
                    </p>

                    <form action="{{ route('barang-masuk.store') }}" method="POST">
                        @csrf

                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label class="required-asterisk" for="tanggal_transaksi">Tanggal</label>
                                <input type="date" id="tanggal_transaksi" name="tanggal_transaksi" class="form-control"
                                    value="{{ old('tanggal_transaksi', now()->toDateString()) }}" required>
                            </div>
                            <div class="form-group col-md-2">
                                <label class="required-asterisk" for="mode_transaksi">Mode</label>
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
                            <div class="form-group col-md-3">
                                <label for="sumber_tujuan">Supplier</label>
                                <input type="text" id="sumber_tujuan" name="sumber_tujuan" class="form-control"
                                    value="{{ old('sumber_tujuan') }}" placeholder="Nama supplier">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="keterangan">Keterangan</label>
                                <input type="text" id="keterangan" name="keterangan" class="form-control"
                                    value="{{ old('keterangan') }}" placeholder="Opsional">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                            <div>
                                <h5 class="mb-0">Daftar Barang</h5>
                                <small class="text-muted">Tentukan item, jumlah, dan harga satuan untuk setiap
                                    barang.</small>
                            </div>
                            <button type="button" id="tambah-barang" class="btn btn-outline-primary btn-sm">
                                <i class="ti-plus mr-1"></i>Tambah Barang
                            </button>
                        </div>

                        <div id="barang-rows">
                            @foreach (old('items', [['item' => '', 'jumlah' => '', 'harga_satuan' => '']]) as $index => $row)
                                <div class="form-row align-items-end barang-row border rounded p-2 mb-2">
                                    <div class="form-group col-md-6 mb-0">
                                        <label class="required-asterisk">Item</label>
                                        <select name="items[{{ $index }}][item]" class="form-control" required>
                                            <option value="">Pilih item</option>
                                            <optgroup label="Pembelian Barang">
                                                @foreach ($itemPembelian as $item)
                                                    <option value="pembelian:{{ $item->id }}"
                                                        @selected(($row['item'] ?? '') === 'pembelian:' . $item->id)>
                                                        {{ $item->nama }} ({{ $item->satuan }}) - Barang
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Perbekalan">
                                                @foreach ($perbekalan as $item)
                                                    <option value="perbekalan:{{ $item->id }}"
                                                        @selected(($row['item'] ?? '') === 'perbekalan:' . $item->id)>
                                                        {{ $item->nama }} ({{ $item->satuan }}) - Perbekalan
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2 mb-0">
                                        <label class="required-asterisk">Qty</label>
                                        <input type="number" name="items[{{ $index }}][jumlah]"
                                            class="form-control" step="0.01" min="0.01"
                                            value="{{ $row['jumlah'] ?? '' }}" required>
                                    </div>
                                    <div class="form-group col-md-3 mb-0">
                                        <label class="required-asterisk">Harga Satuan</label>
                                        <input type="text" name="items[{{ $index }}][harga_satuan]"
                                            class="form-control" data-rupiah-input value="{{ $row['harga_satuan'] ?? '' }}"
                                            placeholder="0,00">
                                    </div>
                                    <div class="form-group col-md-1 mb-0">
                                        <button type="button" class="btn btn-outline-danger btn-block hapus-barang"
                                            title="Hapus barang">
                                            <i class="ti-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="text-right mt-3">
                            <button type="submit" class="btn btn-primary">Simpan Barang Masuk</button>
                        </div>

                        <small class="text-muted d-block mt-2">
                            Mode <strong>Pembelian</strong> menambah stok dan mencatat pembayaran dari Kas, Bank, atau
                            Hutang. Mode <strong>Import Stok Awal</strong> hanya menambah stok tanpa mencatat pembayaran.
                        </small>
                    </form>

                    <template id="barang-row-template">
                        <div class="form-row align-items-end barang-row border rounded p-2 mb-2">
                            <div class="form-group col-md-6 mb-0">
                                <label class="required-asterisk">Item</label>
                                <select name="items[__INDEX__][item]" class="form-control" required>
                                    <option value="">Pilih item</option>
                                    <optgroup label="Pembelian Barang">
                                        @foreach ($itemPembelian as $item)
                                            <option value="pembelian:{{ $item->id }}">{{ $item->nama }}
                                                ({{ $item->satuan }}) - Barang</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Perbekalan">
                                        @foreach ($perbekalan as $item)
                                            <option value="perbekalan:{{ $item->id }}">{{ $item->nama }}
                                                ({{ $item->satuan }}) - Perbekalan</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>
                            <div class="form-group col-md-2 mb-0">
                                <label class="required-asterisk">Qty</label>
                                <input type="number" name="items[__INDEX__][jumlah]" class="form-control"
                                    step="0.01" min="0.01" required>
                            </div>
                            <div class="form-group col-md-3 mb-0">
                                <label class="required-asterisk">Harga Satuan</label>
                                <input type="text" name="items[__INDEX__][harga_satuan]" class="form-control"
                                    data-rupiah-input placeholder="0,00">
                            </div>
                            <div class="form-group col-md-1 mb-0">
                                <button type="button" class="btn btn-outline-danger btn-block hapus-barang"
                                    title="Hapus barang">
                                    <i class="ti-trash"></i>
                                </button>
                            </div>
                        </div>
                    </template>
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
            const rows = document.getElementById('barang-rows');
            const template = document.getElementById('barang-row-template');
            const tambahBarang = document.getElementById('tambah-barang');

            if (!akun || !mode || !rows || !template || !tambahBarang) return;

            let nextIndex = rows.querySelectorAll('.barang-row').length;

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

            tambahBarang.addEventListener('click', () => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
                const newRow = wrapper.firstElementChild;
                rows.appendChild(newRow);
                window.rupiahInput.init(newRow);
            });

            rows.addEventListener('click', (event) => {
                const removeButton = event.target.closest('.hapus-barang');
                if (!removeButton) return;

                const allRows = rows.querySelectorAll('.barang-row');
                if (allRows.length === 1) {
                    allRows[0].querySelectorAll('input').forEach((input) => input.value = '');
                    allRows[0].querySelector('select').value = '';
                    return;
                }

                removeButton.closest('.barang-row').remove();
            });

            syncAkunState();
        })();
    </script>
@endpush
