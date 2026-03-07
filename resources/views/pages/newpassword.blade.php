@extends('layouts.layout-hless')

@section('title', 'Ubah Password Sistem Fisherya')

@section('content')

    <div class="content-wrapper d-flex align-items-center auth px-0">
        <div class="row w-100 mx-0">
            <div class="col-lg-4 mx-auto">
                <div class="auth-form-light text-left py-5 px-4 px-sm-5">
                    <div class="brand-logo">
                        <img src="{{ asset('images/logo.svg') }}" alt="logo">
                    </div>
                    <h4>Form Reset Password.</h4>
                    <h6 class="font-weight-light">Silahkan tuliskan password baru Anda!</h6>
                    <form class="pt-3" method="post" action="/reset-password">
                        @csrf

                        <input type="hidden" name="token" value="{{request('token')}}">

                        <div class="form-group">
                            <input readonly name="email" value="{{request('email')}}" type="email" class="{{$errors->has('email') ? 'is-invalid' : ''}} form-control form-control-lg" id="email" placeholder="Email">
                            <div class="invalid-feedback">
                                {{$errors->first('email')}}
                            </div>
                        </div>

                        <div class="form-group">
                            <input name="password" value="{{old('password')}}" type="password" class="{{$errors->has('password') ? 'is-invalid' : ''}} form-control form-control-lg" id="password" placeholder="Password">
                            <div class="invalid-feedback">
                                {{$errors->first('password')}}
                            </div>
                        </div>

                        <div class="form-group">
                            <input name="password_confirmation" value="{{old('password_confirmation')}}" type="password" class="{{$errors->has('password_confirmation') ? 'is-invalid' : ''}} form-control form-control-lg" id="password_confirmation" placeholder="Password Confirmation">
                            <div class="invalid-feedback">
                                {{$errors->first('password_confirmation')}}
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-block btn-warning btn-lg font-weight-medium auth-form-btn">Reset Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

