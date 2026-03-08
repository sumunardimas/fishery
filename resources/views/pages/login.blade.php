@extends('layouts.layout-hless')

@section('title', 'Login Sistem Perikanan')

@section('content')

<div class="content-wrapper d-flex align-items-center auth px-0" x-data="form">
        <div class="row w-100 mx-0">
          <div class="col-lg-4 mx-auto">
            <div class="auth-form-light text-left py-5 px-4 px-sm-5">
              <div class="brand-logo">
                <img src="{{ asset('images/logo.svg') }}" alt="logo">
              </div>
              <h4>Selamat Datang!</h4>
              <h6 class="font-weight-light">Silahkan login</h6>

                @if(session('status'))
                    <p>{{session('status')}}</p>
                @endif

              <form class="pt-3" @submit.prevent="submit">
                <div class="form-group">
                  <input type="text" class="form-control form-control-lg" id="Email1" placeholder="Email" x-model.lazy="formData.email">
                </div>
                <div class="form-group">
                  <input type="password" class="form-control form-control-lg" id="Password1" placeholder="Password" x-model.lazy="formData.password">
                </div>
                <div class="mt-3">
                  <button class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn" type="submit">
                      Login
                  </button>
                </div>
                <div class="my-2 d-flex justify-content-between align-items-center">
                  <div class="form-check">
                    <label class="form-check-label text-muted">
                      <input type="checkbox" name="remember" class="form-check-input" x-model="formData.remember">
                      Ingat saya
                    </label>
                  </div>
                  <a href="/resetpassword" class="auth-link text-black">Reset password</a>
                </div>

                <div class="text-center mt-4 font-weight-light">
                  Belum memiliki akun? <a href="/register" class="text-primary">Daftar</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

@vite('resources/js/login.js')
@endsection

