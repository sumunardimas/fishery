<div class="form-group">
    <label class="required-asterisk" for="id_kapal">Pilih Kapal</label>
    <select name="id_kapal" id="id_kapal" class="form-control" required>
        <option value="">Pilih salah satu kapal</option>
        @foreach ($kapals as $kapal)
            <option value="{{ $kapal->id_kapal }}"
                {{ (string) old('id_kapal', $pelayaran->id_kapal ?? '') === (string) $kapal->id_kapal ? 'selected' : '' }}>
                {{ $kapal->nama_kapal }} ({{ $kapal->tahun_dibangun }})
            </option>
        @endforeach
    </select>
    <x-input-error :message="$errors->first('id_kapal')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="tanggal_berangkat">Tanggal Berangkat</label>
    <input type="date" name="tanggal_berangkat" id="tanggal_berangkat" class="form-control"
        value="{{ old('tanggal_berangkat', isset($pelayaran) ? $pelayaran->tanggal_berangkat?->format('Y-m-d') : null) }}" required>
    <x-input-error :message="$errors->first('tanggal_berangkat')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="tanggal_tiba">Tanggal Tiba</label>
    <input type="date" name="tanggal_tiba" id="tanggal_tiba" class="form-control"
        value="{{ old('tanggal_tiba', isset($pelayaran) ? $pelayaran->tanggal_tiba?->format('Y-m-d') : null) }}" required>
    <x-input-error :message="$errors->first('tanggal_tiba')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="pelabuhan_asal">Pelabuhan Asal</label>
    <input type="text" name="pelabuhan_asal" id="pelabuhan_asal" class="form-control" maxlength="255"
        value="{{ old('pelabuhan_asal', $pelayaran->pelabuhan_asal ?? null) }}" placeholder="Contoh: Belawan" required>
    <x-input-error :message="$errors->first('pelabuhan_asal')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="pelabuhan_tujuan">Pelabuhan Tujuan</label>
    <input type="text" name="pelabuhan_tujuan" id="pelabuhan_tujuan" class="form-control" maxlength="255"
        value="{{ old('pelabuhan_tujuan', $pelayaran->pelabuhan_tujuan ?? null) }}" placeholder="Contoh: Sabang" required>
    <x-input-error :message="$errors->first('pelabuhan_tujuan')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="jumlah_trip">Jumlah Trip</label>
    <input type="number" name="jumlah_trip" id="jumlah_trip" class="form-control" min="1" step="1"
        value="{{ old('jumlah_trip', $pelayaran->jumlah_trip ?? 1) }}" required>
    <x-input-error :message="$errors->first('jumlah_trip')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="keterangan">Keterangan Operasional</label>
    <textarea name="keterangan" id="keterangan" rows="4" class="form-control" required
        placeholder="Contoh: Rencana trip menangkap tuna, estimasi 9 hari melaut.">{{ old('keterangan', $pelayaran->keterangan ?? null) }}</textarea>
    <x-input-error :message="$errors->first('keterangan')" />
</div>

<div class="card mt-4 border">
    <div class="card-body"
        x-data="{
            tick: 0,
            hitungTerisi() {
                return Array.from(this.$el.querySelectorAll('input[data-perbekalan-qty]')).filter((el) => Number(el.value) > 0).length;
            }
        }"
        x-on:input="tick++">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="mb-1">Perbekalan Saat Berlayar</h5>
                <p class="text-muted mb-0">Isi jumlah hanya untuk barang yang dibawa. Barang yang kosong/0 tidak disimpan ke database.</p>
            </div>
            <span class="badge badge-info" x-text="hitungTerisi() + ' item diisi'"></span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead>
                    <tr>
                        <th style="width: 40%;">Nama Barang</th>
                        <th style="width: 20%;">Kategori</th>
                        <th style="width: 15%;">Satuan</th>
                        <th style="width: 25%;">Jumlah Dibawa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($masterPerbekalan as $barang)
                        @php
                            $existingQty = $selectedPerbekalan[$barang->id_barang] ?? null;
                            $value = old('perbekalan_qty.' . $barang->id_barang, $existingQty);
                        @endphp
                        <tr>
                            <td>{{ $barang->nama_barang }}</td>
                            <td>{{ $barang->kategori }}</td>
                            <td>{{ $barang->satuan }}</td>
                            <td>
                                <input
                                    type="number"
                                    data-perbekalan-qty
                                    @input="$dispatch('input')"
                                    name="perbekalan_qty[{{ $barang->id_barang }}]"
                                    class="form-control"
                                    step="0.01"
                                    min="0"
                                    placeholder="0"
                                    value="{{ $value }}">
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
