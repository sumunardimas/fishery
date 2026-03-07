@extends('layouts.layout')

@section('title', 'Tambah Kapal')

@section('content')
    <div class="col-12">
        @if ($errors->has('message'))
            <x-alert type="danger" :message="$errors->first('message') ?? null" />
        @endif
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Tambah Kapal</h4>
                <p class="card-description">Lengkapi data kapal baru pada formulir berikut.</p>

                <form class="forms-sample" action="{{ route('kapal.store') }}" method="POST">
                    @csrf
                    @include('kapal.form-fillings')

                    <button type="submit" class="btn btn-primary mr-2">Simpan</button>
                    <a href="{{ route('kapal.index') }}" class="btn btn-light">Batal</a>
                </form>
            </div>
        </div>
    </div>
@endsection
