@extends('layouts.layout')

@section('title', 'Tambah Pelayaran')

@section('content')
    <div class="col-12">
        @if ($errors->has('message'))
            <x-alert type="danger" :message="$errors->first('message') ?? null" />
        @endif
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Rencana Pelayaran Baru</h4>
                <p class="card-description">Lengkapi data perencanaan pelayaran sesuai proses operasional perusahaan perikanan.</p>

                <form class="forms-sample" action="{{ route('pelayaran.store') }}" method="POST">
                    @csrf
                    @include('pelayaran.form-fillings')

                    <button type="submit" class="btn btn-primary mr-2">Simpan Rencana</button>
                    <a href="{{ route('pelayaran.index') }}" class="btn btn-light">Batal</a>
                </form>
            </div>
        </div>
    </div>
@endsection
