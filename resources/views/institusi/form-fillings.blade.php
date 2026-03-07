<div class="form-group">
    <label class="required-asterisk" for="Name1">Nama Institusi</label>
    <input type="text" required maxlength="60" name="nama" value="{{ old('nama', $institusi->nama ?? null) }}" class="form-control" id="Nama1" placeholder="Nama">
    <x-input-error :message="$errors->first('nama')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="alamat1">Alamat Institusi</label>
    <input type="text" required maxlength="255" name="alamat" value="{{ old('alamat', $institusi->alamat ?? null ) }}" class="form-control" id="alamat1" placeholder="Alamat">
    <x-input-error :message="$errors->first('alamat')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="Email3">Alamat Email</label>
    <input type="email" required maxlength="60" name="email" value="{{ old('email', $institusi->email ?? null ) }}" class="form-control" id="Email3" placeholder="Email">
    <x-input-error :message="$errors->first('email')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="telepon">Telepon</label>
    <input type="text" required name="telepon" value="{{ old('telepon', $institusi->telepon ?? null ) }}" minlength="7" maxlength="20" class="form-control" id="telepon" placeholder="0274000000">
    <x-input-error :message="$errors->first('telepon')" />
</div>

<div class="form-group">
    <label class="required-asterisk" for="website">Website</label>
    <input type="text" required maxlength="255" name="website" value="{{ old('website', $institusi->website ?? null ) }}" class="form-control" id="website" placeholder="https://">
    <x-input-error :message="$errors->first('website')" />
</div>
