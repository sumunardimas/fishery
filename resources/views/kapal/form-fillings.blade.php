<div class="form-group">
    <label class="required-asterisk" for="nama_kapal">Nama Kapal</label>
    <input type="text" required maxlength="255" name="nama_kapal"
        value="{{ old('nama_kapal', $kapal->nama_kapal ?? null) }}" class="form-control" id="nama_kapal"
        placeholder="Contoh: KM Laut Sejahtera">
    <x-input-error :message="$errors->first('nama_kapal')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="tahun_dibangun">Tahun Dibangun</label>
    <input type="number" required min="1900" max="2100" name="tahun_dibangun"
        value="{{ old('tahun_dibangun', $kapal->tahun_dibangun ?? null) }}" class="form-control" id="tahun_dibangun"
        placeholder="Contoh: 2018">
    <x-input-error :message="$errors->first('tahun_dibangun')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="gross_tonnage">Gross Tonnage</label>
    <input type="number" required step="0.01" min="0" name="gross_tonnage"
        value="{{ old('gross_tonnage', $kapal->gross_tonnage ?? null) }}" class="form-control" id="gross_tonnage"
        placeholder="Contoh: 120.50">
    <x-input-error :message="$errors->first('gross_tonnage')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="deadweight_tonnage">Deadweight Tonnage</label>
    <input type="number" required step="0.01" min="0" name="deadweight_tonnage"
        value="{{ old('deadweight_tonnage', $kapal->deadweight_tonnage ?? null) }}" class="form-control"
        id="deadweight_tonnage" placeholder="Contoh: 98.30">
    <x-input-error :message="$errors->first('deadweight_tonnage')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="panjang_meter">Panjang (Meter)</label>
    <input type="number" required step="0.01" min="0" name="panjang_meter"
        value="{{ old('panjang_meter', $kapal->panjang_meter ?? null) }}" class="form-control" id="panjang_meter"
        placeholder="Contoh: 28.40">
    <x-input-error :message="$errors->first('panjang_meter')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="lebar_meter">Lebar (Meter)</label>
    <input type="number" required step="0.01" min="0" name="lebar_meter"
        value="{{ old('lebar_meter', $kapal->lebar_meter ?? null) }}" class="form-control" id="lebar_meter"
        placeholder="Contoh: 6.20">
    <x-input-error :message="$errors->first('lebar_meter')" />
</div>
