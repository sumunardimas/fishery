@php
    $defaultTanggalBerangkat =
        isset($pelayaran) && $pelayaran->tanggal_berangkat
            ? $pelayaran->tanggal_berangkat->format('Y-m-d')
            : now()->format('Y-m-d');
    $defaultTanggalTiba =
        isset($pelayaran) && $pelayaran->tanggal_tiba
            ? $pelayaran->tanggal_tiba->format('Y-m-d')
            : now()->addWeek()->format('Y-m-d');
@endphp

<div class="form-group">
    <label class="required-asterisk" for="id_kapal">Pilih Kapal</label>
    <select name="id_kapal" id="id_kapal" class="form-control" required>
        <option value="">Pilih salah satu kapal</option>
        @forelse ($kapals as $kapal)
            <option value="{{ $kapal->id_kapal }}"
                {{ (string) old('id_kapal', $pelayaran->id_kapal ?? '') === (string) $kapal->id_kapal ? 'selected' : '' }}>
                {{ $kapal->nama_kapal }}{{ $kapal->nahkoda ? ' - Nahkoda: ' . $kapal->nahkoda : '' }}
            </option>
        @empty
            <option value="" disabled>Tidak ada kapal tersedia</option>
        @endforelse
    </select>
    <small class="text-muted">Hanya kapal yang tidak sedang berlayar ditampilkan.</small>
    <x-input-error :message="$errors->first('id_kapal')" />
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label class="required-asterisk" for="tanggal_berangkat">Tanggal Berangkat</label>
            <input type="date" name="tanggal_berangkat" id="tanggal_berangkat" class="form-control"
                value="{{ old('tanggal_berangkat', $defaultTanggalBerangkat) }}" required>
            <x-input-error :message="$errors->first('tanggal_berangkat')" />
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label class="required-asterisk" for="tanggal_tiba">Tanggal Kembali Estimasi</label>
            <input type="date" name="tanggal_tiba" id="tanggal_tiba" class="form-control"
                value="{{ old('tanggal_tiba', $defaultTanggalTiba) }}" required>
            <x-input-error :message="$errors->first('tanggal_tiba')" />
        </div>
    </div>
</div>

<input type="hidden" name="jumlah_trip" value="{{ old('jumlah_trip', $pelayaran->jumlah_trip ?? 1) }}">

<div class="form-group">
    <label for="keterangan">Keterangan Operasional <small class="text-muted">(opsional)</small></label>
    <textarea name="keterangan" id="keterangan" rows="4" class="form-control"
        placeholder="Contoh: Rencana trip menangkap tuna, estimasi 7 hari melaut.">{{ old('keterangan', $pelayaran->keterangan ?? null) }}</textarea>
    <x-input-error :message="$errors->first('keterangan')" />
</div>

<div class="card mt-4 border">
    <div class="card-body" x-data="{
        tick: 0,
        hitungTerisi() {
            return Array.from(this.$el.querySelectorAll('input[data-perbekalan-qty]')).filter((el) => Number(el.value) > 0).length;
        }
    }" x-on:input="tick++">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Perbekalan Saat Berlayar</h5>
                <p class="text-muted mb-0">Isi jumlah hanya untuk barang yang dibawa. Barang yang kosong/0 tidak
                    disimpan ke database.</p>
            </div>
            <span class="badge badge-info" x-text="hitungTerisi() + ' item diisi'"></span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width: 40%;">Nama Barang</th>
                        <th style="width: 15%;">Satuan</th>
                        <th style="width: 20%;">Stok</th>
                        <th style="width: 25%;">Jumlah Dibawa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($masterPerbekalan as $barang)
                        @php
                            $existingQty = $selectedPerbekalan[$barang->id_barang] ?? null;
                            $defaultQty = isset($pelayaran) ? null : (float) ($barang->default_qty ?? 0);
                            $value = old('perbekalan_qty.' . $barang->id_barang, $existingQty ?? $defaultQty);
                        @endphp
                        <tr>
                            <td>{{ $barang->nama_barang }}</td>
                            <td>{{ $barang->satuan }}</td>
                            <td>
                                <span class="badge badge-light">
                                    {{ number_format((float) ($barang->stok_aktual ?? 0), 2, ',', '.') }}
                                </span>
                            </td>
                            <td>
                                <input type="number" data-perbekalan-qty @input="$dispatch('input')"
                                    data-stock="{{ (float) ($barang->stok_aktual ?? 0) }}"
                                    name="perbekalan_qty[{{ $barang->id_barang }}]"
                                    class="form-control js-perbekalan-qty" step="0.01" min="0"
                                    max="{{ (float) ($barang->stok_aktual ?? 0) }}" placeholder="0"
                                    value="{{ $value }}">
                                <small class="text-danger js-stock-helper d-none">
                                    Jumlah melebihi stok yang tersedia.
                                </small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">Master perbekalan belum tersedia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-input-error :message="$errors->first('perbekalan_qty')" />
        <x-input-error :message="$errors->first('perbekalan_qty.*')" />
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const inputs = document.querySelectorAll('.js-perbekalan-qty');
            if (!inputs.length) return;

            const toggleWarning = (input) => {
                const stock = Number(input.dataset.stock || 0);
                const qty = Number(input.value || 0);
                const helper = input.closest('td')?.querySelector('.js-stock-helper');

                if (!helper) return;

                const exceeded = qty > stock;
                helper.classList.toggle('d-none', !exceeded);
                input.classList.toggle('is-invalid', exceeded);
            };

            inputs.forEach((input) => {
                input.addEventListener('input', () => toggleWarning(input));
                toggleWarning(input);
            });
        })();
    </script>
@endpush
