@extends('layouts.layout')

@section('title', 'Riwayat Pelayaran Selesai')

@section('content')
    <div class="row">
        <div class="col-12">
            @if ($errors->has('message'))
                <x-alert type="danger" :message="$errors->first('message') ?? null" />
            @elseif (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-1">Riwayat Pelayaran Selesai</h4>
                    <p class="card-description mb-0">
                        Daftar trip yang sudah ditutup. Klik <strong>Lihat Detail</strong> untuk membuka card penutupan
                        dalam
                        mode baca saja.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="pelayaran-sisa-history-table" class="display expandable-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID Trip</th>
                                    <th>Kapal</th>
                                    <th>Tanggal Berangkat</th>
                                    <th>Tanggal Tiba</th>
                                    <th>Tanggal Ditutup</th>
                                    <th>Keterangan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>#{{ $item->id_pelayaran }}</td>
                                        <td>{{ $item->kapal->nama_kapal ?? '-' }}</td>
                                        <td>{{ $item->tanggal_berangkat ? $item->tanggal_berangkat->format('d-m-Y') : '-' }}
                                        </td>
                                        <td>{{ $item->tanggal_tiba ? $item->tanggal_tiba->format('d-m-Y') : '-' }}</td>
                                        <td>{{ $item->tanggal_selesai ? $item->tanggal_selesai->format('d-m-Y') : '-' }}
                                        </td>
                                        <td>{{ $item->keterangan ?: '-' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <a href="{{ route('pelayaran.sisa.history.show', ['pelayaran' => $item->id_pelayaran]) }}"
                                                    class="btn btn-sm btn-primary mr-2 mb-1">
                                                    Lihat Detail
                                                </a>
                                                <a href="{{ route('pelayaran.report.show', ['pelayaran' => $item->id_pelayaran]) }}"
                                                    class="btn btn-sm btn-outline-warning mb-1">
                                                    Laporan
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada pelayaran yang sudah
                                            ditutup.</td>
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
            $('#pelayaran-sisa-history-table').DataTable({
                ...window.dataTableGeneralConfig,
                processing: false,
                serverSide: false,
            });
        });
    </script>
@endpush
