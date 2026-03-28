@extends('layouts.layout')

@section('title', 'Riwayat Transaksi Penjualan')

@section('content')
    <div x-data="{
        previewUrl: '',
        showPreview: false,
        waMsg: '',
        waUrl: '',
        showWa: false,
        copied: false,
        openPreview(url) {
            this.previewUrl = url;
            this.showPreview = true;
        },
        openWa(msg) {
            this.waMsg = msg;
            this.waUrl = 'https://wa.me/?text=' + encodeURIComponent(msg);
            this.copied = false;
            this.showWa = true;
        },
        copyMsg() {
            navigator.clipboard.writeText(this.waMsg);
            this.copied = true;
            setTimeout(() => this.copied = false, 2500);
        }
    }">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-1">Riwayat Transaksi Penjualan</h4>
                            <p class="card-description mb-0">Daftar transaksi penjualan berdasarkan tanggal.</p>
                        </div>
                        <a href="{{ route('penjualan.index') }}" class="btn btn-primary">
                            <i class="ti-write mr-1"></i> POS Transaksi Baru
                        </a>
                    </div>
                </div>

                {{-- Date filter --}}
                <div class="card mb-4">
                    <div class="card-body py-3">
                        <form method="GET" action="{{ route('penjualan.riwayat') }}" class="form-inline">
                            <label for="date" class="mr-2">Tanggal:</label>
                            <input type="date" name="date" id="date" class="form-control mr-2"
                                value="{{ $date }}">
                            <button type="submit" class="btn btn-outline-primary">Tampilkan</button>
                        </form>
                    </div>
                </div>

                {{-- Summary cards --}}
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <small class="text-muted">Total Transaksi</small>
                                <h4>{{ $summary['total_transaksi'] }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <small class="text-muted">Total Berat</small>
                                <h4>{{ number_format($summary['total_berat'], 2) }} kg</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <small class="text-muted">Total Pendapatan</small>
                                <h4>Rp {{ number_format($summary['total_pendapatan'], 2, ',', '.') }}</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <small class="text-muted">Total Piutang</small>
                                <h4 class="{{ $summary['total_piutang'] > 0 ? 'text-danger' : '' }}">
                                    Rp {{ number_format($summary['total_piutang'], 2, ',', '.') }}
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Transactions table --}}
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            Transaksi — {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Customer</th>
                                        <th>Ikan</th>
                                        <th>Total Tagihan</th>
                                        <th>Piutang</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($sales as $trx)
                                        @php
                                            $waText =
                                                "Yth. {$trx->nama_customer_display},\n\n" .
                                                'Anda memiliki tagihan (piutang) sebesar *Rp ' .
                                                number_format($trx->piutang, 2, ',', '.') .
                                                '* untuk transaksi nomor *INV-' .
                                                str_pad($trx->id_penjualan, 5, '0', STR_PAD_LEFT) .
                                                '* tanggal ' .
                                                \Carbon\Carbon::parse($trx->tanggal_penjualan)->translatedFormat(
                                                    'd F Y',
                                                ) .
                                                ".\n\nMohon segera diselesaikan. Terima kasih 🙏\n\n_TPI Sadeng_";
                                        @endphp
                                        <tr>
                                            <td>{{ $trx->created_at?->format('H:i') }}</td>
                                            <td>{{ $trx->nama_customer_display }}</td>
                                            <td>{{ $trx->nama_ikan }}</td>
                                            <td>Rp {{ number_format($trx->total_harga, 2, ',', '.') }}</td>
                                            <td>
                                                @if (($trx->piutang ?? 0) > 0)
                                                    <span class="text-danger font-weight-bold">
                                                        Rp {{ number_format($trx->piutang, 2, ',', '.') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if (($trx->status_pembayaran ?? 'lunas') === 'lunas')
                                                    <span class="badge badge-success">Lunas</span>
                                                @else
                                                    <span class="badge badge-warning">Piutang</span>
                                                @endif
                                            </td>
                                            <td style="min-width: 160px;">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary d-block w-100 mb-1"
                                                    @click="openPreview('{{ route('penjualan.invoice.preview', $trx->id_penjualan) }}')">
                                                    <i class="ti-eye mr-1"></i> Tampilkan Transaksi
                                                </button>
                                                <a href="{{ route('penjualan.invoice', $trx->id_penjualan) }}"
                                                    class="btn btn-sm btn-outline-secondary d-block w-100 mb-1"
                                                    target="_blank">
                                                    <i class="ti-download mr-1"></i> Invoice
                                                </a>
                                                @if (($trx->piutang ?? 0) > 0)
                                                    <button type="button" class="btn btn-sm btn-success d-block w-100"
                                                        @click="openWa({{ Js::from($waText) }})">
                                                        <i class="ti-comment-alt mr-1"></i> WA Piutang
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                Tidak ada transaksi pada tanggal ini.
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

        {{-- ── Invoice Preview Overlay ── --}}
        <div x-show="showPreview" x-cloak style="position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,0.65);"
            @keydown.escape.window="showPreview = false">
            <div
                style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:8px;width:92%;max-width:680px;height:90vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,0.3);">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <strong>Detail Transaksi</strong>
                    <button type="button" class="close" @click="showPreview = false">&times;</button>
                </div>
                <iframe :src="previewUrl" style="flex:1;border:none;border-radius:0 0 8px 8px;"></iframe>
            </div>
        </div>

        {{-- ── WhatsApp Piutang Overlay ── --}}
        <div x-show="showWa" x-cloak style="position:fixed;inset:0;z-index:1050;background:rgba(0,0,0,0.65);"
            @keydown.escape.window="showWa = false">
            <div
                style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:8px;width:92%;max-width:480px;box-shadow:0 8px 32px rgba(0,0,0,0.3);">
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <strong><i class="ti-comment-alt mr-1 text-success"></i> Pesan WhatsApp Piutang</strong>
                    <button type="button" class="close" @click="showWa = false">&times;</button>
                </div>
                <div class="p-3">
                    <p class="text-muted small mb-2">Salin pesan berikut lalu kirim ke pelanggan melalui WhatsApp:</p>
                    <textarea class="form-control mb-3" rows="8" readonly :value="waMsg" style="font-size:13px;resize:none;"></textarea>
                    <div class="d-flex">
                        <button type="button" class="btn btn-outline-secondary mr-2" @click="copyMsg()">
                            <span x-show="!copied"><i class="ti-files mr-1"></i> Salin Pesan</span>
                            <span x-show="copied" x-cloak><i class="ti-check mr-1"></i> Tersalin!</span>
                        </button>
                        <a :href="waUrl" target="_blank" class="btn btn-success">
                            <i class="ti-comment-alt mr-1"></i> Buka WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
