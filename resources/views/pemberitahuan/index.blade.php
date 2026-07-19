@extends('layouts.layout')

@section('title', 'Pemberitahuan')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-1">Pemberitahuan</h3>
                    <p class="text-muted mb-0">Pusat informasi yang memerlukan perhatian pengguna.</p>
                </div>
                <span class="badge badge-primary p-2">{{ $notifications->count() }} pemberitahuan</span>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ti-package text-primary mr-2"></i>
                        <div>
                            <h5 class="card-title mb-0">Status Stok Barang</h5>
                            <small class="text-muted">
                                Peringatan muncul saat stok mencapai limit atau berada maksimal 20% di atas limit.
                            </small>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Isi Notifikasi</th>
                                    <th>Menu Terkait</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($notifications as $notification)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-start">
                                                <span class="badge badge-{{ $notification->severity }} mr-3 mt-1">
                                                    {{ $notification->severity === 'danger' ? 'Penting' : 'Peringatan' }}
                                                </span>
                                                <div>
                                                    <h6 class="mb-1">{{ $notification->title }}</h6>
                                                    <p class="text-muted mb-1">{{ $notification->message }}</p>
                                                    <small class="text-{{ $notification->severity }}">
                                                        Stok saat ini:
                                                        <strong>{{ number_format($notification->current_stock, 2, ',', '.') }} {{ $notification->satuan }}</strong>
                                                        · Limit minimal:
                                                        <strong>{{ number_format($notification->limit_minimal, 2, ',', '.') }} {{ $notification->satuan }}</strong>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $notification->related_menu }}</td>
                                        <td class="text-right">
                                            <a href="{{ route($notification->route_name) }}"
                                                class="btn btn-outline-primary btn-sm">{{ $notification->action_label }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            Semua stok masih berada di atas batas peringatan.
                                        </td>
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
