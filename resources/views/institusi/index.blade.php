@extends('layouts.layout')

@section('title', 'Daftar Institusi')

@section('content')
<div class="row">
    <div class="col-md-12">
        @if ($errors->has('message'))
            <x-alert type="danger" :message="$errors->first('message') ?? null" />
        @elseif(session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif
        <div class="card">
            <div class="card-body">
                <p class="card-title">Daftar Institusi</p>
                @can('create institution')
                    <a href="{{ route('institusi.create') }}" class="btn btn-success btn-md btn-icon-text mt-3 mb-3">
                        <i class="ti-plus btn-icon-prepend"></i>
                        Tambah
                    </a>
                @endcanany
                <div class="table-responsive">
                    <table id="tabel-institusi" class="display expandable-table w-100">
                        <thead>
                            <tr>
                                <th>ID#</th>
                                <th>Nama</th>
                                <th>Alamat</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Website</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#tabel-institusi').DataTable({
                ...window.dataTableGeneralConfig,
                ajax: '{{ route('institusi.data') }}',
                columns: [{
                    data: 'id',
                },
                    {
                        data: 'nama',
                    },
                    {
                        data: 'alamat',
                    },
                    {
                        data: 'email',
                    },
                    {
                        data: 'telepon',
                    },
                    {
                        data: 'website',
                        render: function(data, type, row, meta) {
                            return '<a href="' + data + '" target="_blank">' + data + '</a>'
                        }
                    },
                    {
                        data: 'action',
                        searchable:false,
                        orderable:false,
                    },
                ]
            })
        })
    </script>
@endpush
