@extends('layouts.layout')

@section('title', 'Edit Pelayaran')

@section('content')
    <div class="col-12">
        @if ($errors->has('message'))
            <x-alert type="danger" :message="$errors->first('message') ?? null" />
        @endif
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Perbarui Pelayaran #{{ $pelayaran->id_pelayaran }}</h4>
                <p class="card-description">Ubah jadwal dan detail perjalanan kapal dengan tetap menjaga integritas proses bisnis.</p>

                <form class="forms-sample" action="{{ route('pelayaran.update', $pelayaran) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('pelayaran.form-fillings')

                    <button type="submit" class="btn btn-primary mr-2">Perbarui</button>
                    <a href="{{ route('pelayaran.index') }}" class="btn btn-light">Batal</a>
                </form>
            </div>
        </div>
    </div>
@endsection
