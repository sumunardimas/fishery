@extends('layouts.layout')

@section('title', 'Laporan Piutang')

@section('content')
    <div x-data="{
        waMsg: '',
        waUrl: '',
        showWa: false,
        openWa(msg) {
            this.waMsg = msg;
            this.waUrl = 'https://wa.me/?text=' + encodeURIComponent(msg);
            this.showWa = true;
        },
        copyWa() {
            navigator.clipboard.writeText(this.waMsg);
        },
    }">

        {{-- WA overlay --}}
        <div x-show="showWa" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1050;">
            <div
                style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
                        background:#fff; border-radius:8px; padding:24px; max-width:480px; width:90%;">
                <h5 class="mb-3">Pesan WA Piutang</h5>
                <textarea class="form-control mb-3" rows="8" x-text="waMsg" readonly></textarea>
                <div class="d-flex gap-2">
                    <a :href="waUrl" target="_blank" class="btn btn-success mr-2">
                        <i class="ti-comment-alt mr-1"></i> Buka WA
                    </a>
                    <button type="button" class="btn btn-outline-secondary mr-2" @click="copyWa()">
                        <i class="ti-clipboard mr-1"></i> Salin
                    </button>
                    <button type="button" class="btn btn-light" @click="showWa = false">Tutup</button>
                </div>
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="row mb-4">
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card border-left-danger">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Total Piutang</p>
                        <h4 class="{{ $summary['total_piutang'] > 0 ? 'text-danger' : '' }}">
                            Rp {{ number_format($summary['total_piutang'], 2, ',', '.') }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Jumlah Transaksi</p>
                        <h4>{{ $summary['jumlah_transaksi'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Total Tagihan</p>
                        <h4>Rp {{ number_format($summary['total_tagihan'], 2, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Total Diterima</p>
                        <h4 class="text-success">Rp {{ number_format($summary['total_diterima'], 2, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Filter panel --}}
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Filter</h4>

                        <form method="GET" action="{{ url('/keuangan/piutang') }}">
                            <div class="form-group">
                                <label for="start_date">Dari Tanggal</label>
                                <input type="date" id="start_date" name="start_date" class="form-control"
                                    value="{{ $startDate }}">
                            </div>
                            <div class="form-group">
                                <label for="end_date">Sampai Tanggal</label>
                                <input type="date" id="end_date" name="end_date" class="form-control"
                                    value="{{ $endDate }}">
                            </div>
                            <div class="form-group">
                                <label for="status">Tampilkan</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="piutang" {{ $status === 'piutang' ? 'selected' : '' }}>
                                        Piutang Belum Lunas
                                    </option>
                                    <option value="semua" {{ $status === 'semua' ? 'selected' : '' }}>
                                        Semua Transaksi
                                    </option>
                                </select>
                            </div>
                            <div class="d-flex align-items-center">
                                <button type="submit" class="btn btn-primary mr-2">Terapkan</button>
                                <a href="{{ url('/keuangan/piutang') }}" class="btn btn-light">Reset</a>
                            </div>
                        </form>

                        @if ($byCustomer->isNotEmpty())
                            <hr>
                            <h6 class="mb-2">Rekap per Customer</h6>
                            @foreach ($byCustomer as $c)
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-truncate mr-2" style="max-width:140px;"
                                        title="{{ $c->nama }}">{{ $c->nama }}</span>
                                    <span
                                        class="{{ $c->total_piutang > 0 ? 'text-danger' : 'text-muted' }} font-weight-bold small">
                                        Rp {{ number_format($c->total_piutang, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="col-md-9 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-1">Laporan Piutang Penjualan</h4>
                        <p class="card-description mb-3">
                            Daftar transaksi dengan sisa piutang pada periode
                            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} –
                            {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}.
                        </p>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table id="piutang-table" class="display expandable-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No. Invoice</th>
                                        <th>Tanggal</th>
                                        <th>Customer</th>
                                        <th>Total Tagihan</th>
                                        <th>Diterima</th>
                                        <th>Sisa Piutang</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $trx)
                                        @php
                                            $invNo = 'INV-' . str_pad($trx->id_penjualan, 5, '0', STR_PAD_LEFT);
                                            $waText =
                                                "Yth. {$trx->nama_customer_display},\n\n" .
                                                'Anda memiliki tagihan (piutang) sebesar *Rp ' .
                                                number_format($trx->piutang, 2, ',', '.') .
                                                '* untuk transaksi *' .
                                                $invNo .
                                                '* tanggal ' .
                                                \Carbon\Carbon::parse($trx->tanggal_penjualan)->translatedFormat(
                                                    'd F Y',
                                                ) .
                                                ".\n\nMohon segera diselesaikan. Terima kasih 🙏\n\n_TPI Sadeng_";
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="badge badge-light">{{ $invNo }}</span>
                                            </td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($trx->tanggal_penjualan)->format('d-m-Y') }}
                                            </td>
                                            <td>{{ $trx->nama_customer_display }}</td>
                                            <td>Rp {{ number_format($trx->total_harga, 2, ',', '.') }}</td>
                                            <td>Rp {{ number_format($trx->total_diterima, 2, ',', '.') }}</td>
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
                                            <td style="min-width: 120px;">
                                                <a href="{{ route('penjualan.invoice', $trx->id_penjualan) }}"
                                                    class="btn btn-sm btn-outline-secondary d-block w-100 mb-1"
                                                    target="_blank">
                                                    <i class="ti-download mr-1"></i> Invoice
                                                </a>
                                                @if (($trx->piutang ?? 0) > 0)
                                                    <button type="button" class="btn btn-sm btn-success d-block w-100"
                                                        @click="openWa({{ Js::from($waText) }})">
                                                        <i class="ti-comment-alt mr-1"></i> WA Tagih
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                Tidak ada data piutang pada periode ini.
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
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#piutang-table').DataTable({
                pageLength: 25,
                order: [
                    [1, 'desc']
                ],
                columnDefs: [{
                    targets: [7],
                    orderable: false,
                }],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    paginate: {
                        previous: 'Sebelumnya',
                        next: 'Berikutnya',
                    },
                    emptyTable: 'Tidak ada data piutang.',
                    zeroRecords: 'Tidak ada data yang cocok.',
                },
            });
        });
    </script>
@endpush
