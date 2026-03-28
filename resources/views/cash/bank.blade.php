@extends('layouts.layout')

@section('title', 'Manajemen Bank')

@section('content')
    @include('cash.partials.ledger', ['title' => 'Bank', 'akun' => 'bank'])
@endsection
