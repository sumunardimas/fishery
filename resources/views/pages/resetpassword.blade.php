@extends('layouts.layout-hless')

@section('title', 'Ubah Password Sistem Perikanan')

@section('content')

<div class="content-wrapper d-flex align-items-center auth px-0">
        <div class="row w-100 mx-0">
          <div class="col-lg-4 mx-auto">
            <div class="auth-form-light text-left py-5 px-4 px-sm-5">
              <div class="brand-logo">
                <img src="{{ asset('images/logo.svg') }}" alt="logo">
              </div>
              <h4>Form Reset Password.</h4>
              <h6 class="font-weight-light">Silahkan tuliskan email Anda!</h6>
              <form class="pt-3" method="post" action="/forgot-password">
                  @csrf
                <div class="form-group">
                  <input name="email" type="email" class="{{$errors->has('email') ? 'is-invalid' : ''}} {{session('status') ? 'is-valid' : ''}} form-control form-control-lg" id="Email1" placeholder="Email">
                    <div class="invalid-feedback">
                        {{$errors->first('email')}}
                    </div>
                    <div class="valid-feedback">
                        {{session('status')}}
                    </div>
                </div>

                <div class="mt-3">
                  <button type="submit" class="btn btn-block btn-warning btn-lg font-weight-medium auth-form-btn">Kirim Link Reset Password</button>
                </div>

                <div class="text-center mt-4 font-weight-light">
                  Sudah memiliki akun? <a href="/login" class="text-primary">Login</a>
                </div>
                <div class="text-center mt-4 font-weight-light">
                  Belum memiliki akun? <a href="/register" class="text-primary">Daftar</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

@endsection

