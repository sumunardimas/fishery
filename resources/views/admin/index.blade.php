@extends('layouts.layout')

@section('title', 'Administrator')

@section('content')

<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <p class="card-title">Daftar Admin</p>
                <div class="row">
                    <div class="col-12">
                        <div class="table-responsive">
                            <table id="" class="display expandable-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID#</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>No. WhatsApp</th>
                                        <th>Institusi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
