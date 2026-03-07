@extends('layouts.layout-hless')

@section('title', 'Daftar Akun Sistem Fisherya')

@section('content')

<div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper" x-data="form_register">
      <div class="content-wrapper d-flex align-items-center auth px-0">
        <div class="row w-100 mx-0">
          <div class="col-lg-4 mx-auto">
            <div class="auth-form-light text-left py-5 px-4 px-sm-5">
              <div class="brand-logo">
                <img src="{{ asset('images/logo.svg') }}" alt="logo">
              </div>
              <h4>Form Pendaftaran Akun</h4>
              <h6 class="font-weight-light">Lengkapi form berikut ini untuk mendaftarkan akun.</h6>
                <form class="forms-sample" x-data="form_register" @submit.prevent="submit">

                    <div class="form-group">
                          @php($formName = 'nama')
                          @php($formLabel = 'Nama')
                          <label class="required-asterisk" for="{{$formName}}">{{$formLabel}}</label>
                          <input type="text" class="form-control" id="{{$formName}}" placeholder="{{$formLabel}}"
                                 x-model.lazy="{{$formName}}"
                                 :class="{'is-invalid': validationErrors.{{$formName}}}">
                          <template x-if="validationErrors.{{$formName}}">
                              <div class="invalid-feedback" x-text="validationErrors.{{$formName}}"></div>
                          </template>
                      </div>

                    <div class="form-group">
                        @php($formName = 'email')
                        @php($formLabel = 'Alamat Email')
                        <label class="required-asterisk" for="{{$formName}}">{{$formLabel}}</label>
                        <input type="text" class="form-control" id="{{$formName}}" placeholder="{{$formLabel}}"
                               x-model.lazy="{{$formName}}"
                               :class="{'is-invalid': validationErrors.{{$formName}}}">
                        <template x-if="validationErrors.{{$formName}}">
                            <div class="invalid-feedback" x-text="validationErrors.{{$formName}}"></div>
                        </template>
                    </div>

                    <div class="form-group">
                        @php($formName = 'whatsapp')
                        @php($formLabel = 'No WhatsApp')
                        <label class="required-asterisk" for="{{$formName}}">{{$formLabel}}</label>
                        <input type="text" class="form-control" id="{{$formName}}" placeholder="{{$formLabel}}"
                               x-model.lazy="{{$formName}}"
                               :class="{'is-invalid': validationErrors.{{$formName}}}">
                        <template x-if="validationErrors.{{$formName}}">
                            <div class="invalid-feedback" x-text="validationErrors.{{$formName}}"></div>
                        </template>
                    </div>

                    <div class="form-group">
                        @php($formName = 'password')
                        @php($formLabel = 'Password')
                        <label class="required-asterisk" for="{{$formName}}">{{$formLabel}}</label>
                        <input type="password" class="form-control" id="{{$formName}}" placeholder="{{$formLabel}}"
                               x-model.lazy="{{$formName}}"
                               :class="{'is-invalid': validationErrors.{{$formName}}}">
                        <template x-if="validationErrors.{{$formName}}">
                            <div class="invalid-feedback" x-text="validationErrors.{{$formName}}"></div>
                        </template>
                    </div>

                      <div class="form-group">
                          @php($formName = 'gender')
                          @php($formLabel = 'Jenis Kelamin')
                          <label class="required-asterisk" for="{{$formName}}">{{$formLabel}}</label>
                          <select type="text" class="form-control" id="{{$formName}}"
                                  x-model.lazy="{{$formName}}"
                                  :class="{'is-invalid': validationErrors.{{$formName}}}">
                              <option value="">Pilih Jenis Kelamin</option>
                              <option value="1">Laki-laki</option>
                              <option value="0">Perempuan</option>
                          </select>
                          <template x-if="validationErrors.{{$formName}}">
                              <div class="invalid-feedback" x-text="validationErrors.{{$formName}}"></div>
                          </template>
                      </div>

                      <div class="form-group">
                          @php($formName = 'role')
                          @php($formLabel = 'Peran')
                          <label class="required-asterisk" for="{{$formName}}">{{$formLabel}}</label>
                          <select type="text" class="form-control" id="{{$formName}}"
                                  x-model.lazy="{{$formName}}"
                                  :class="{'is-invalid': validationErrors.{{$formName}}}">
                              <option value="">Pilih Salah Satu</option>
                              <option value="admin">Admin</option>
                              <option value="kasir">Kasir</option>
                              <option value="staff">Staff</option>
                          </select>
                          <template x-if="validationErrors.{{$formName}}">
                              <div class="invalid-feedback" x-text="validationErrors.{{$formName}}"></div>
                          </template>
                      </div>

                      <div class="form-group">
                          @php($formName = 'institusi_id')
                          @php($formLabel = 'Institusi')
                          <label class="required-asterisk" for="{{$formName}}">{{$formLabel}}</label>
                          <select type="text" class="form-control" id="{{$formName}}"
                                  x-model.lazy="{{$formName}}"
                                  :class="{'is-invalid': validationErrors.{{$formName}}}">
                              <option value="">Pilih Salah Satu</option>
                              @foreach($institusis as $institusi)
                                  <option value="{{$institusi->id}}">{{$institusi->nama}}</option>
                              @endforeach
                          </select>
                          <template x-if="validationErrors.{{$formName}}">
                              <div class="invalid-feedback" x-text="validationErrors.{{$formName}}"></div>
                          </template>
                      </div>


                    <div class="form-group">
                        @php($formName = 'document')
                        @php($formLabel = 'Unggah Dokumen Pendukung')
                        <label for="{{$formName}}">{{$formLabel}}</label>
                        <input type="file" class="form-control" id="{{$formName}}" placeholder="{{$formLabel}}"
                               :class="{'is-invalid': validationErrors.{{$formName}}}"
                               @change="{{$formName}} = $event.target.files[0]">
                        <template x-if="validationErrors.{{$formName}}">
                            <div class="invalid-feedback" x-text="validationErrors.{{$formName}}"></div>
                        </template>
                    </div>

                      <div class="mt-3">
                        <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">
                            Daftar
                        </button>
                      </div>


                      <div class="text-center mt-4 font-weight-light">
                    Sudah memiliki akun? <a href="/login" class="text-primary">Login</a>
                  </div>

                </form>
            </div>
          </div>
        </div>
      </div>

    </div>

  </div>
@vite('resources/js/register.js')

@endsection
