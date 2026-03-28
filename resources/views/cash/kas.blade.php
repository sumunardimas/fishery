@extends('layouts.layout')

@section('title', 'Manajemen Kas')

@section('content')
    @include('cash.partials.ledger', ['title' => 'Kas', 'akun' => 'kas'])
@endsection
