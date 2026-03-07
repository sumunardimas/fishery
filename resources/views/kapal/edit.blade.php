@extends('layouts.layout')

@section('title', 'Edit Kapal')

@section('content')
    <div class="col-12">
        @if ($errors->has('message'))
            <x-alert type="danger" :message="$errors->first('message') ?? null" />
        @endif
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Kapal</h4>
                <p class="card-description">Perbarui data kapal {{ $kapal->nama_kapal }}.</p>

                <form class="forms-sample" action="{{ route('kapal.update', $kapal) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('kapal.form-fillings')

                    <button type="submit" class="btn btn-primary mr-2">Perbarui</button>
                    <a href="{{ route('kapal.index') }}" class="btn btn-light">Batal</a>
                </form>
            </div>
        </div>
    </div>
@endsection
