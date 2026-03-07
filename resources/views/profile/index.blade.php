@extends('layouts.layout')

@section('title', 'Profil Pengguna')

@section('content')

    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="border-bottom text-center pb-4">
                            <img src="{{ asset('images/man.png') }}" alt="Pofile"
                                class="img-lg rounded-circle mb-3 mx-auto d-block">
                            <div class="mb-3">
                                <h3>{{ $user->profile->name ?? 'No Name' }}</h3>
                                <div class="d-flex align-items-center justify-content-center">
                                    <h5 class="mb-0 me-2 text-muted">{{ $user->email ?? 'No Email' }}</h5>
                                </div>
                            </div>
                            <p class="w-75 mx-auto mb-3">{{ $user->profile->institusi->nama ?? 'No Institution' }}</p>
                        </div>
                        <div class="py-4">
                            <p class="clearfix">
                                <span class="float-left">
                                    Jenis Kelamin
                                </span>
                                <span class="float-right text-muted">
                                    {{ $user->profile->gender == 1 ? 'Laki-laki' : 'Perempuan' }}
                                </span>
                            </p>
                            <p class="clearfix">
                                <span class="float-left">
                                    WhatsApp
                                </span>
                                <span class="float-right text-muted">
                                    {{ $user->profile->institusi->telepon ?? '-' }}
                                </span>
                            </p>
                            <p class="clearfix">
                                <span class="float-left">
                                    Peran
                                </span>
                                <span class="float-right text-muted">
                                    {{ $user->getRoleNames()->first() ?? '-' }}
                                </span>
                            </p>
                            <p class="clearfix">
                                <span class="float-left">
                                    Akun aktif sejak
                                </span>
                                <span class="float-right text-muted">
                                    {{ \Carbon\Carbon::parse($user->created_at)->locale('id')->isoFormat('D MMMM Y') }}
                                </span>
                            </p>

                        </div>
                        <button class="btn btn-primary btn-block mb-2" onclick="window.location.href='/profile/edit'">Ubah
                            Data</button>
                    </div>

                </div>
            </div>
        </div>

    </div>


@endsection
