@extends('layouts.layout')

@section('title', 'Form Pengguna Baru')

@section('content')
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Buat Akun Pengguna</h4>
                <p class="card-description">Isi data pengguna di bawah ini.</p>

                @if ($errors->has('message'))
                    <x-alert type="danger" :message="$errors->first('message')" />
                @endif
                @if (session('status'))
                    <x-alert type="success" :message="session('status')" />
                @endif

                <form class="forms-sample" action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama"
                            name="nama" value="{{ old('nama') }}" placeholder="Nama">
                        @error('nama')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">Alamat Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" value="{{ old('email') }}" placeholder="Email">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="whatsapp">No WhatsApp</label>
                        <input type="text" class="form-control @error('whatsapp') is-invalid @enderror" id="whatsapp"
                            name="whatsapp" value="{{ old('whatsapp') }}" placeholder="081234567890">
                        @error('whatsapp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                            name="password" placeholder="Password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- RegisterRequest expects "Laki-laki" | "Perempuan" --}}
                    <div class="form-group">
                        <label for="gender">Jenis Kelamin</label>
                        @php $g = old('gender'); @endphp
                        <select class="form-control @error('gender') is-invalid @enderror" id="gender" name="gender">
                            <option value="">Pilih Salah Satu</option>
                            <option value="Laki-laki" {{ $g === 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="Perempuan" {{ $g === 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    
                    <div class="form-group">
                        <label for="role">Peran</label>
                        @php $r = old('role'); @endphp
                        <select class="form-control @error('role') is-invalid @enderror" id="role" name="role">
                            <option value="">Pilih Salah Satu</option>
                            <option value="Admin" {{ $r === 'Admin' ? 'selected' : '' }}>Admin</option>
                            <option value="Kasir" {{ $r === 'Kasir' ? 'selected' : '' }}>Kasir</option>
                            <option value="Staff" {{ $r === 'Staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tugas Panitia (muncul hanya jika role Panitia) --}}
                    @php $showCommittee = old('role')==='Panitia'; @endphp
                    <div class="form-group" id="committee-wrapper"
                        @if (!$showCommittee) style="display:none" @endif>
                        <label for="committee">Tugas Panitia</label>
                        @php $c = old('committee'); @endphp
                        <select class="form-control @error('committee') is-invalid @enderror" id="committee"
                            name="committee">
                            <option value="">Pilih Salah Satu</option>
                            @foreach (['Ketua', 'Sekretaris', 'Bendahara', 'Validator', 'Bidang 1', 'Bidang 2', 'Pengawas'] as $opt)
                                <option value="{{ $opt }}" {{ $c === $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                            @endforeach
                        </select>
                        @error('committee')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- RegisterRequest expects "institution" (string). We’ll submit the selected Institusi name. --}}
                    <div class="form-group">
                        <label for="institusi_id">Institusi</label>
                        <select class="form-control @error('institusi_id') is-invalid @enderror" id="institusi_id"
                            name="institusi_id" required>
                            <option value="">Pilih Salah Satu</option>
                            @foreach (\App\Models\Institusi::all() as $item)
                                <option value="{{ $item->id }}"
                                    {{ (string) old('institusi_id') === (string) $item->id ? 'selected' : '' }}>
                                    {{ $item->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('institusi_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="document">Unggah Dokumen Pendukung</label>
                        <input type="file" class="form-control @error('document') is-invalid @enderror" id="document"
                            name="document">
                        @error('document')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary mr-2">Buat Akun Pengguna</button>
                    <a href="{{ route('users.index') }}" class="btn btn-light">Batal</a>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const role = document.getElementById('role');
            const wrap = document.getElementById('committee-wrapper');
            role?.addEventListener('change', () => {
                wrap.style.display = (role.value === 'Panitia') ? '' : 'none';
            });
        });
    </script>
@endsection
