@extends('layouts.layout')

@section('title', 'Laporan Stok Barang Gudang')

@section('content')
    <div class="row">
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-1">Total Item</p>
                    <h3 class="mb-0">{{ $summary['total_item'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-1">Barang</p>
                    <h3 class="mb-0">{{ $summary['barang'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-1">Perbekalan</p>
                    <h3 class="mb-0">{{ $summary['perbekalan'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <p class="card-title mb-1">Stok Rendah</p>
                    <h3 class="mb-0 text-danger">{{ $summary['stok_rendah'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-1">Laporan Stok Barang Gudang</h4>
                    <p class="card-description">Stok Barang dan Perbekalan dalam satu tabel.</p>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="filter-jenis">Jenis</label>
                            <select id="filter-jenis" class="form-control">
                                <option value="">Semua jenis</option>
                                <option value="Barang">Barang</option>
                                <option value="Perbekalan">Perbekalan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter-status">Status</label>
                            <select id="filter-status" class="form-control">
                                <option value="">Semua status</option>
                                <option value="Aman">Aman</option>
                                <option value="Stok rendah">Stok rendah</option>
                                <option value="Belum diatur">Belum diatur</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="laporan-stok-gudang-table" class="display expandable-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th>Nama Item</th>
                                    <th>Kategori</th>
                                    <th>Satuan</th>
                                    <th>Stok Aktual</th>
                                    <th>Limit Minimal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>
                                            <span
                                                class="badge {{ $item->jenis === 'Barang' ? 'badge-primary' : 'badge-info' }}">
                                                {{ $item->jenis }}
                                            </span>
                                        </td>
                                        <td><strong>{{ $item->nama }}</strong></td>
                                        <td>{{ $item->kategori }}</td>
                                        <td>{{ $item->satuan }}</td>
                                        <td data-order="{{ (float) $item->stok_aktual }}">
                                            {{ number_format((float) $item->stok_aktual, 2, ',', '.') }}
                                        </td>
                                        <td data-order="{{ (float) $item->limit_minimal }}">
                                            {{ number_format((float) $item->limit_minimal, 2, ',', '.') }}
                                        </td>
                                        <td>
                                            @if ($item->status === 'Stok rendah')
                                                <span class="badge badge-danger">Stok rendah</span>
                                            @elseif ($item->status === 'Aman')
                                                <span class="badge badge-success">Aman</span>
                                            @else
                                                <span class="badge badge-secondary">Belum diatur</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
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
            const table = $('#laporan-stok-gudang-table').DataTable({
                ...window.dataTableGeneralConfig,
                processing: false,
                serverSide: false,
                pageLength: 100,
                order: [
                    [1, 'asc']
                ],
            });

            $('#filter-jenis').on('change', function() {
                table.column(0).search(this.value).draw();
            });

            $('#filter-status').on('change', function() {
                table.column(6).search(this.value).draw();
            });
        });
    </script>
@endpush
