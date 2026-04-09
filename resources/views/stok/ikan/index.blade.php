@extends('layouts.layout')

@section('title', 'Stok Ikan')

@section('content')
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-1">Stok Ikan Aktual</p>
                    <p class="text-muted">Kolom storage mengikuti jumlah kapal yang sudah memiliki storage ikan.</p>

                    <div class="table-responsive">
                        <table id="stok-ikan-table" class="display expandable-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama Ikan Tangkapan</th>
                                    @foreach ($storages as $storage)
                                        <th>{{ $storage->nama_storage }} ({{ $storage->nama_kapal }})</th>
                                    @endforeach
                                    <th>Total Stok Aktual (Kg)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>{{ $item->id_ikan }}</td>
                                        <td>{{ $item->nama_ikan }}</td>
                                        @foreach ($storages as $storage)
                                            <td>{{ number_format((float) ($item->stok_per_storage[$storage->id_storage] ?? 0), 2, ',', '.') }}
                                            </td>
                                        @endforeach
                                        <td>{{ number_format((float) $item->stok_aktual, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 3 + $storages->count() }}" class="text-center text-muted">Belum ada
                                            data stok ikan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
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
            $('#stok-ikan-table').DataTable({
                ...window.dataTableGeneralConfig,
                processing: false,
                serverSide: false,
                pageLength: 50,
                scrollX: true,
            });
        });
    </script>
@endpush
