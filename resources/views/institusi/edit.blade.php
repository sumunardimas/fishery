@extends('layouts.layout')

@section('title', 'Form Perubahan Institusi')

@section('content')

<div class="col-12">
    @if ($errors->has('message'))
        <x-alert type="danger" :message="$errors->first('message') ?? null" />
    @endif
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Perbarui Institusi</h4>
            <p class="card-description">Ubah data institusi.</p>
            <form class="forms-sample" action="{{ route('institusi.update', $institusi->id) }}" method="POST">
                @method('PUT')
                @csrf
                @include('institusi/form-fillings')
                <button type="submit" class="btn btn-primary mr-2">Perbarui</button>
                <a href="{{ route('institusi.index') }}" class="btn btn-light">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
