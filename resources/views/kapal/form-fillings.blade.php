<div class="form-group">
    <label class="required-asterisk" for="nama_kapal">Nama Kapal</label>
    <input type="text" required maxlength="255" name="nama_kapal"
        value="{{ old('nama_kapal', $kapal->nama_kapal ?? null) }}" class="form-control" id="nama_kapal"
        placeholder="Contoh: KM Laut Sejahtera">
    <x-input-error :message="$errors->first('nama_kapal')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="nahkoda">Nahkoda</label>
    <input type="text" required maxlength="255" name="nahkoda" value="{{ old('nahkoda', $kapal->nahkoda ?? null) }}"
        class="form-control" id="nahkoda" placeholder="Contoh: Bapak Suryanto">
    <x-input-error :message="$errors->first('nahkoda')" />
</div>
