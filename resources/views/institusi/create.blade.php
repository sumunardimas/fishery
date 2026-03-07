@extends('layouts.layout')

@section('title', 'Form Pembuatan Institusi')

@section('content')

<div class="col-12">
    @if ($errors->has('message'))
        <x-alert type="danger" :message="$errors->first('message') ?? null" />
    @endif
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Tambah Institusi</h4>
            <p class="card-description">Lengkapi data-data berikut ini untuk menambahkan institusi baru.</p>
            <form class="forms-sample" action="{{ route('institusi.store') }}" method="POST">
                @csrf
                @include('institusi/form-fillings')
                <button type="submit" class="btn btn-primary mr-2">Simpan</button>
                <a href="{{ route('institusi.index') }}" class="btn btn-light">Batal</a>
            </form>
        </div>
    </div>
</div>


@endsection
