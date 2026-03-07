@extends('layouts.layout')

@section('title', 'Pengguna')

@section('content')

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title">Daftar Pengguna</p>

                    <button type="button" onclick="window.location.href='/users/create'"
                        class="btn btn-success btn-md btn-icon-text mt-3 mb-3">
                        <i class="ti-plus btn-icon-prepend"></i>
                        Tambah
                    </button>

                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table id="users-table" class="display expandable-table" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>No</th>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>No. WhatsApp</th>
                                            <th>Institusi</th>
                                            <th>Peran</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#users-table').DataTable({
                ...window.dataTableGeneralConfig,
                ajax: '{{ route('users.data') }}',
                columns: [
                    { data: 'id' },
                    { data: 'DT_RowIndex', name: 'DT_RowIndex' },
                    { data: 'nama' },
                    { data: 'email' },
                    { data: 'whatsapp' },
                    { data: 'institusi' },
                    { data: 'role' },
                    {
                        data: 'action',
                        searchable: false,
                        orderable: false,
                    },
                ]
            });
        });
    </script>
@endpush
