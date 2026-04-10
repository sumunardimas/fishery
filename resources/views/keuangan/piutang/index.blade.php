@extends('layouts.layout')

@section('title', 'Laporan Piutang')

@section('content')
    <div x-data="piutangApp()">

        {{-- Payment overlay --}}
        <div x-show="showBayar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1060;">
            <div
                style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
                        background:#fff; border-radius:8px; padding:24px; max-width:440px; width:90%;">
                <h5 class="mb-1">Bayar Piutang</h5>
                <p class="text-muted small mb-3">
                    <span x-text="bayarTrx.invNo"></span> &mdash; <span x-text="bayarTrx.customer"></span>
                </p>

                <div class="alert alert-warning py-2 mb-3">
                    Sisa Piutang: <strong>Rp <span x-text="fmtRp(bayarTrx.piutang)"></span></strong>
                </div>

                <div x-show="bayarError" class="alert alert-danger py-2 mb-3" x-text="bayarError" style="display:none;">
                </div>

                <div class="form-group">
                    <label>Kas (Tunai)</label>
                    <input type="number" x-model="bayarKas" class="form-control" min="0" step="1000"
                        placeholder="0">
                </div>
                <div class="form-group">
                    <label>Transfer</label>
                    <input type="number" x-model="bayarTransfer" class="form-control" min="0" step="1000"
                        placeholder="0">
                </div>

                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Total Pembayaran:</span>
                        <strong>Rp <span x-text="fmtRp(pembayaran)"></span></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Sisa Piutang Setelah Bayar:</span>
                        <strong :class="isLunas ? 'text-success' : 'text-danger'">
                            Rp <span x-text="fmtRp(sisaPiutang)"></span>
                        </strong>
                    </div>
                    <div class="mt-2 text-center" x-show="isLunas" style="display:none;">
                        <span class="badge badge-success">Lunas ✓</span>
                    </div>
                </div>

                <div class="d-flex">
                    <button type="button" class="btn btn-primary mr-2" @click="submitBayar()"
                        :disabled="bayarLoading || pembayaran <= 0">
                        <span x-show="!bayarLoading">Simpan Pembayaran</span>
                        <span x-show="bayarLoading" style="display:none;">Menyimpan...</span>
                    </button>
                    <button type="button" class="btn btn-light" @click="showBayar = false"
                        :disabled="bayarLoading">Batal</button>
                </div>
            </div>
        </div>

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

        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-1">Laporan Piutang</h4>
                <p class="card-description mb-3">Filter periode dan status piutang.</p>

                <form method="GET" action="{{ url('/keuangan/piutang') }}" class="mb-0">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-3">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="form-control"
                                value="{{ $startDate }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="form-control"
                                value="{{ $endDate }}">
                        </div>
                        <div class="form-group col-md-3">
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
                        <div class="form-group col-md-3 d-flex align-items-center">
                            <button type="submit" class="btn btn-primary mr-2">Terapkan</button>
                            <a href="{{ url('/keuangan/piutang') }}" class="btn btn-light">Reset</a>
                        </div>
                    </div>
                </form>
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
            {{-- Rekap panel --}}
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Rekap per Customer</h4>

                        @if ($byCustomer->isNotEmpty())
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
                        @else
                            <p class="text-muted mb-0">Belum ada rekap customer pada periode ini.</p>
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
                                            $piutangVal = (float) ($trx->piutang ?? 0);
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
                                        <tr id="row-{{ $trx->id_penjualan }}">
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
                                                @if ($piutangVal > 0)
                                                    <button type="button"
                                                        class="btn btn-sm btn-warning d-block w-100 mb-1"
                                                        @click="openBayar({ id_penjualan: {{ $trx->id_penjualan }}, invNo: '{{ $invNo }}', customer: {{ Js::from($trx->nama_customer_display) }}, piutang: {{ $piutangVal }} })">
                                                        <i class="ti-money mr-1"></i> Bayar Piutang
                                                    </button>
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
        const BAYAR_URL = '{{ route('keuangan.piutang.bayar') }}';

        function piutangApp() {
            return {
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

                showBayar: false,
                bayarTrx: {
                    id_penjualan: 0,
                    invNo: '',
                    customer: '',
                    piutang: 0
                },
                bayarKas: '',
                bayarTransfer: '',
                bayarLoading: false,
                bayarError: '',
                openBayar(trx) {
                    this.bayarTrx = trx;
                    this.bayarKas = '';
                    this.bayarTransfer = '';
                    this.bayarError = '';
                    this.showBayar = true;
                },
                get pembayaran() {
                    return (parseFloat(this.bayarKas) || 0) + (parseFloat(this.bayarTransfer) || 0);
                },
                get sisaPiutang() {
                    return Math.max(0, (this.bayarTrx.piutang || 0) - this.pembayaran);
                },
                get isLunas() {
                    return this.pembayaran > 0 && this.sisaPiutang < 0.01;
                },
                fmtRp(val) {
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2
                    }).format(val);
                },
                async submitBayar() {
                    if (this.pembayaran <= 0) {
                        this.bayarError = 'Masukkan jumlah pembayaran.';
                        return;
                    }
                    if (this.pembayaran > this.bayarTrx.piutang + 0.01) {
                        this.bayarError = 'Pembayaran melebihi sisa piutang.';
                        return;
                    }
                    this.bayarLoading = true;
                    this.bayarError = '';
                    try {
                        const res = await fetch(BAYAR_URL, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({
                                id_penjualan: this.bayarTrx.id_penjualan,
                                bayar_tunai: parseFloat(this.bayarKas) || 0,
                                bayar_transfer: parseFloat(this.bayarTransfer) || 0,
                            }),
                        });
                        const json = await res.json();
                        if (!res.ok) {
                            this.bayarError = json.message || 'Gagal menyimpan.';
                            return;
                        }
                        const id = this.bayarTrx.id_penjualan;
                        if (json.status_pembayaran === 'lunas') {
                            window.piutangDT.row('#row-' + id).remove().draw();
                        } else {
                            this.bayarTrx.piutang = json.new_piutang;
                            const row = document.getElementById('row-' + id);
                            if (row) {
                                const cells = row.querySelectorAll('td');
                                cells[4].innerHTML = 'Rp ' + json.new_diterima_formatted;
                                cells[5].innerHTML = '<span class="text-danger font-weight-bold">Rp ' + json
                                    .new_piutang_formatted + '</span>';
                            }
                            window.piutangDT.draw(false);
                        }
                        this.showBayar = false;
                    } catch (e) {
                        this.bayarError = 'Terjadi kesalahan jaringan.';
                    } finally {
                        this.bayarLoading = false;
                    }
                },
            };
        }

        $(document).ready(function() {
            window.piutangDT = $('#piutang-table').DataTable({
                pageLength: 25,
                order: [
                    [1, 'desc']
                ],
                columnDefs: [{
                    targets: [7],
                    orderable: false
                }],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    paginate: {
                        previous: 'Sebelumnya',
                        next: 'Berikutnya'
                    },
                    emptyTable: 'Tidak ada data piutang.',
                    zeroRecords: 'Tidak ada data yang cocok.',
                },
            });
        });
    </script>
@endpush
