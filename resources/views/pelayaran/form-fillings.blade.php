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
