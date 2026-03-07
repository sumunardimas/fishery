@extends('layouts.layout')

@section('title', 'Perbarui Profil')

@section('content')

    <div class="col-12">
        @if ($errors->has('message'))
            <x-alert type="danger" :message="$errors->first('message')" />
        @endif

        @if (session('status'))
            <x-alert type="success" :message="session('status')" />
        @endif

        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Perbarui Profil</h4>
                <p class="card-description">Ubah data profil Anda.</p>

                <form class="forms-sample" action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @method('PUT')
                    @csrf

                    {{-- Nama --}}
                    <div class="form-group">
                        <label class="required-asterisk" for="nama">Nama</label>
                        <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama"
                            name="nama" value="{{ old('nama', $profile->name ?? $user->name) }}" placeholder="Nama">
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <label class="required-asterisk" for="email">Alamat Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" value="{{ old('email', $user->email) }}" placeholder="Alamat Email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- WhatsApp --}}
                    <div class="form-group">
                        <label class="required-asterisk" for="whatsapp">No WhatsApp</label>
                        <input type="text" class="form-control @error('whatsapp') is-invalid @enderror" id="whatsapp"
                            name="whatsapp" value="{{ old('whatsapp', $profile->whatsapp) }}" placeholder="No WhatsApp">
                        @error('whatsapp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Jenis Kelamin --}}
                    {{-- <div class="form-group">
                        <label class="required-asterisk" for="gender">Jenis Kelamin</label>
                        @php $g = old('gender', isset($profile->gender) ? (string)$profile->gender : ''); @endphp
                        {{-- <select class="form-control @error('gender') is-invalid @enderror" id="gender" name="gender">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="1" {{ $g === '1' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="0" {{ $g === '0' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                       
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div> --}}

                    {{-- Peran --}}
                    {{-- <div class="form-group">
                        <label class="required-asterisk" for="role">Peran</label>
                        @php $r = old('role', $user->role); @endphp
                        <select class="form-control @error('role') is-invalid @enderror" id="role" name="role">
                            <option value="">Pilih Salah Satu</option>
                            <option value="panitia" {{ $r === 'panitia' ? 'selected' : '' }}>Panitia</option>
                            <option value="kasir" {{ $r === 'kasir' ? 'selected' : '' }}>Ketua Program Studi</option>
                            <option value="staff" {{ $r === 'staff' ? 'selected' : '' }}>Penguji</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div> --}}

                    {{-- Tugas Panitia (hanya tampil jika role panitia) --}}
                    {{-- @php $showCommittee = old('role', $user->role) === 'panitia'; @endphp
                    <div class="form-group" @if (!$showCommittee) style="display:none" @endif
                        id="committee-wrapper">
                        <label for="committee">Tugas Panitia</label>
                        <select class="form-control @error('committee') is-invalid @enderror" id="committee"
                            name="committee">
                            <option value="">Pilih Salah Satu</option>
                            @php $c = old('committee', $user->committee); @endphp
                            @foreach (['Ketua', 'Sekretaris', 'Bendahara', 'Validator', 'Bidang 1', 'Bidang 2', 'Pengawas'] as $opt)
                                <option value="{{ $opt }}" {{ $c === $opt ? 'selected' : '' }}>
                                    {{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('committee')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div> --}}

                    {{-- Institusi --}}
                    {{-- <div class="form-group">
                        <label class="required-asterisk" for="institusi_id">Institusi</label>
                        <select class="form-control @error('institusi_id') is-invalid @enderror" id="institusi_id"
                            name="institusi_id">
                            <option value="">Pilih Salah Satu</option>
                            @php $inst = old('institusi_id', $user->institusi_id); @endphp
                            @foreach ($institusis as $institusi)
                                <option value="{{ $institusi->id }}"
                                    {{ (string) $inst === (string) $institusi->id ? 'selected' : '' }}>
                                    {{ $institusi->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('institusi_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div> --}}

                    {{-- Dokumen Pendukung (opsional) --}}
                    <div class="form-group">
                        <label for="document">Unggah Dokumen Pendukung (opsional)</label>
                        @if (!empty($user->document_url))
                            <div class="mb-2">
                                <a href="{{ $user->document_url }}" target="_blank" rel="noopener">Lihat dokumen saat
                                    ini</a>
                            </div>
                        @endif
                        <input type="file" class="form-control @error('document') is-invalid @enderror" id="document"
                            name="document">
                        <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah dokumen.</small>
                        @error('document')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Ubah Password (opsional) --}}
                    <hr>
                    <p class="card-description mb-2">Ubah Password</p>
                    <small class="text-primary">Lengkapi kolom di bawah ini untuk mengubah password</small>

                    <div class="form-group">
                        <label for="current_password">Password Saat Ini</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror"
                            id="current_password" name="current_password" placeholder="Password Saat Ini">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                            name="password" placeholder="Password Baru">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation"
                            placeholder="Konfirmasi Password Baru">
                    </div>

                    <button type="submit" class="btn btn-primary mr-2">Perbarui Profil</button>
                    <a href="{{ route('profile.index') }}" class="btn btn-light">Batal</a>
                </form>
            </div>
        </div>
    </div>

    @vite('resources/js/updateprofile.js')

    {{-- Optional: tiny script to show/hide committee when role changes (no Alpine needed) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const role = document.getElementById('role');
            const wrap = document.getElementById('committee-wrapper');
            role?.addEventListener('change', () => {
                if (role.value === 'panitia') {
                    wrap.style.display = '';
                } else {
                    wrap.style.display = 'none';
                }
            });
        });
    </script>

@endsection
