@extends('layouts.layout')

@section('title', 'Perbarui Profil')

@section('content')
    @php
        $profile = $profile ?? $user->getProfile();
    @endphp

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Perbarui Profil</h4>
                <p class="card-description">Ubah data profil Anda.</p>

                <form class="forms-sample" x-data="form_update" x-init="init()" @submit.prevent="submit()"
                    data-endpoint="{{ route('profile.update') }}" data-method="PUT"
                    data-initial="{{ base64_encode(
                        json_encode([
                            'nama' => old('nama', $profile->name ?? ($user->name ?? '')),
                            'email' => old('email', $user->email ?? ''),
                            'whatsapp' => old('whatsapp', $profile->whatsapp ?? ''),
                            'gender' => old('gender', isset($profile->gender) ? (string) $profile->gender : ''),
                            'document_url' => $user->document_url ?? null,
                        ]),
                    ) }}"
                    enctype="multipart/form-data">
                    {{-- Nama --}}
                    <div class="form-group">
                        <label class="required-asterisk" for="nama">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" x-model="nama"
                            :class="validationErrors.nama ? 'is-invalid' : ''" placeholder="Nama">
                        <template x-if="validationErrors.nama">
                            <div class="invalid-feedback" x-text="validationErrors.nama[0]"></div>
                        </template>
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <label class="required-asterisk" for="email">Alamat Email</label>
                        <input type="email" class="form-control" id="email" name="email" x-model="email"
                            :class="validationErrors.email ? 'is-invalid' : ''" placeholder="Alamat Email">
                        <template x-if="validationErrors.email">
                            <div class="invalid-feedback" x-text="validationErrors.email[0]"></div>
                        </template>
                    </div>

                    {{-- WhatsApp --}}
                    <div class="form-group">
                        <label class="required-asterisk" for="whatsapp">No WhatsApp</label>
                        <input type="text" class="form-control" id="whatsapp" name="whatsapp" x-model="whatsapp"
                            :class="validationErrors.whatsapp ? 'is-invalid' : ''" placeholder="No WhatsApp">
                        <template x-if="validationErrors.whatsapp">
                            <div class="invalid-feedback" x-text="validationErrors.whatsapp[0]"></div>
                        </template>
                    </div>

                    <div class="form-group">
                        <label for="document">Unggah Dokumen Pendukung (opsional)</label>

                        <template x-if="document_url">
                            <div class="mb-2">
                                <a :href="document_url" target="_blank" rel="noopener">Lihat dokumen saat ini</a>
                            </div>
                        </template>

                        <input type="file" class="form-control" id="document" name="document" @change="handleFileUpload"
                            :class="validationErrors.document ? 'is-invalid' : ''">
                        <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah dokumen.</small>

                        <template x-if="validationErrors.document">
                            <div class="invalid-feedback" x-text="validationErrors.document[0]"></div>
                        </template>
                    </div>

                    {{-- Password --}}
                    <hr>
                    <p class="card-description mb-2">Ubah Password</p>
                    <small class="text-primary">Centang untuk mengubah password</small>

                    <div class="form-check form-check-flat form-check-primary my-2">
                        <label class="form-check-label" for="changePassword">
                            <input type="checkbox" class="form-check-input" id="changePassword" x-model="changePassword">
                            Ubah password
                            <i class="input-helper"></i>
                        </label>
                    </div>


                    <div x-show="changePassword" x-cloak>
                        <div class="form-group">
                            <label for="current_password">Password Saat Ini</label>
                            <input type="password" class="form-control" id="current_password" name="current_password"
                                x-model="current_password" :class="validationErrors.current_password ? 'is-invalid' : ''"
                                placeholder="Password Saat Ini">
                            <template x-if="validationErrors.current_password">
                                <div class="invalid-feedback" x-text="validationErrors.current_password[0]"></div>
                            </template>
                        </div>

                        <div class="form-group">
                            <label for="password">Password Baru</label>
                            <input type="password" class="form-control" id="password" name="password" x-model="password"
                                :class="validationErrors.password ? 'is-invalid' : ''" placeholder="Password Baru">
                            <template x-if="validationErrors.password">
                                <div class="invalid-feedback" x-text="validationErrors.password[0]"></div>
                            </template>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                name="password_confirmation" x-model="password_confirmation"
                                placeholder="Konfirmasi Password Baru">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2" :disabled="isSubmitting">
                        <span x-show="!isSubmitting">Perbarui Profil</span>
                        <span x-show="isSubmitting" x-cloak>Menyimpan...</span>
                    </button>
                    <a href="{{ route('profile.index') }}" class="btn btn-light">Batal</a>
                </form>
            </div>
        </div>
    </div>

    @vite('resources/js/updateprofile.js')
@endsection
