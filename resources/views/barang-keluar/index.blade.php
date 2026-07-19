@extends('layouts.layout')

@section('title', 'Barang Keluar')

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
                    <h4 class="card-title mb-1">Items Out</h4>
                    <p class="card-description mb-4">
                        Catat beberapa barang keluar sekaligus. Nilai barang otomatis mengikuti lapisan pembelian
                        paling lama yang masih tersedia (FIFO).
                    </p>

                    <form action="{{ route('barang-keluar.store') }}" method="POST">
                        @csrf

                        <div class="form-row">
                            <div class="form-group col-md-2">
                                <label class="required-asterisk" for="tanggal_transaksi">Tanggal</label>
                                <input type="date" id="tanggal_transaksi" name="tanggal_transaksi" class="form-control"
                                    value="{{ old('tanggal_transaksi', now()->toDateString()) }}" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label for="sumber_tujuan">Tujuan / Pemakai</label>
                                <input type="text" id="sumber_tujuan" name="sumber_tujuan" class="form-control"
                                    value="{{ old('sumber_tujuan') }}" placeholder="Contoh: Divisi admin / Kapal">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="keterangan">Keterangan</label>
                                <input type="text" id="keterangan" name="keterangan" class="form-control"
                                    value="{{ old('keterangan') }}" placeholder="Opsional">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                            <div>
                                <h5 class="mb-0">Daftar Barang</h5>
                                <small class="text-muted">Harga keluar dihitung otomatis dari pembelian FIFO.</small>
                            </div>
                            <button type="button" id="tambah-barang" class="btn btn-outline-primary btn-sm">
                                <i class="ti-plus mr-1"></i>Tambah Barang
                            </button>
                        </div>

                        <div id="barang-rows">
                            @foreach (old('items', [['item' => '', 'jumlah' => '']]) as $index => $row)
                                <div class="form-row align-items-end barang-row border rounded p-2 mb-2">
                                    <div class="form-group col-md-8 mb-0">
                                        <label class="required-asterisk">Item</label>
                                        <select name="items[{{ $index }}][item]" class="form-control" required>
                                            <option value="">Pilih item</option>
                                            <optgroup label="Pembelian Barang">
                                                @foreach ($itemPembelian as $item)
                                                    <option value="pembelian:{{ $item->id }}"
                                                        style="color: {{ (float) $item->stok > 0 ? '#198754' : '#dc3545' }}"
                                                        @selected(($row['item'] ?? '') === 'pembelian:'.$item->id)>
                                                        {{ $item->nama }} ({{ $item->satuan }}) - Barang — Stok: {{ number_format((float) $item->stok, 2, ',', '.') }} {{ $item->satuan }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Perbekalan">
                                                @foreach ($perbekalan as $item)
                                                    <option value="perbekalan:{{ $item->id }}"
                                                        style="color: {{ (float) $item->stok > 0 ? '#198754' : '#dc3545' }}"
                                                        @selected(($row['item'] ?? '') === 'perbekalan:'.$item->id)>
                                                        {{ $item->nama }} ({{ $item->satuan }}) - Perbekalan — Stok: {{ number_format((float) $item->stok, 2, ',', '.') }} {{ $item->satuan }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-3 mb-0">
                                        <label class="required-asterisk">Qty</label>
                                        <input type="number" name="items[{{ $index }}][jumlah]" class="form-control"
                                            step="0.01" min="0.01" value="{{ $row['jumlah'] ?? '' }}" required>
                                    </div>
                                    <div class="form-group col-md-1 mb-0">
                                        <button type="button" class="btn btn-outline-danger btn-block hapus-barang" title="Hapus barang">
                                            <i class="ti-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="text-right mt-3">
                            <button type="submit" class="btn btn-primary">Simpan Barang Keluar</button>
                        </div>

                        <small class="text-muted d-block mt-2">
                            Semua baris disimpan sebagai satu proses. Jika stok atau lapisan FIFO salah satu item tidak
                            mencukupi, tidak ada barang yang dikeluarkan.
                        </small>
                    </form>

                    <template id="barang-row-template">
                        <div class="form-row align-items-end barang-row border rounded p-2 mb-2">
                            <div class="form-group col-md-8 mb-0">
                                <label class="required-asterisk">Item</label>
                                <select name="items[__INDEX__][item]" class="form-control" required>
                                    <option value="">Pilih item</option>
                                    <optgroup label="Pembelian Barang">
                                        @foreach ($itemPembelian as $item)
                                            <option value="pembelian:{{ $item->id }}"
                                                style="color: {{ (float) $item->stok > 0 ? '#198754' : '#dc3545' }}">
                                                {{ $item->nama }} ({{ $item->satuan }}) - Barang — Stok: {{ number_format((float) $item->stok, 2, ',', '.') }} {{ $item->satuan }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Perbekalan">
                                        @foreach ($perbekalan as $item)
                                            <option value="perbekalan:{{ $item->id }}"
                                                style="color: {{ (float) $item->stok > 0 ? '#198754' : '#dc3545' }}">
                                                {{ $item->nama }} ({{ $item->satuan }}) - Perbekalan — Stok: {{ number_format((float) $item->stok, 2, ',', '.') }} {{ $item->satuan }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>
                            <div class="form-group col-md-3 mb-0">
                                <label class="required-asterisk">Qty</label>
                                <input type="number" name="items[__INDEX__][jumlah]" class="form-control" step="0.01" min="0.01" required>
                            </div>
                            <div class="form-group col-md-1 mb-0">
                                <button type="button" class="btn btn-outline-danger btn-block hapus-barang" title="Hapus barang">
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
            const rows = document.getElementById('barang-rows');
            const template = document.getElementById('barang-row-template');
            const tambahBarang = document.getElementById('tambah-barang');

            if (!rows || !template || !tambahBarang) return;

            let nextIndex = rows.querySelectorAll('.barang-row').length;

            tambahBarang.addEventListener('click', () => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
                rows.appendChild(wrapper.firstElementChild);
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
        })();
    </script>
@endpush
