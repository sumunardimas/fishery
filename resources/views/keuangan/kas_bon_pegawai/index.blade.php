@extends('layouts.layout')

@section('title', 'Kas Bon Pegawai')

@section('content')
    <div x-data="kasBonPegawaiApp()">
        <div x-show="showBayar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1060;">
            <div
                style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
                        background:#fff; border-radius:8px; padding:24px; max-width:440px; width:90%;">
                <h5 class="mb-1">Bayar Kas Bon Pegawai</h5>
                <p class="text-muted small mb-3">
                    <span x-text="bayarTrx.kode"></span> &mdash; <span x-text="bayarTrx.nama_pegawai"></span>
                </p>

                <div class="alert alert-warning py-2 mb-3">
                    Sisa Piutang: <strong>Rp <span x-text="fmtRp(bayarTrx.sisa_piutang)"></span></strong>
                </div>

                <div x-show="bayarError" class="alert alert-danger py-2 mb-3" x-text="bayarError" style="display:none;">
                </div>

                <div class="form-group">
                    <label>Pembayaran Ke</label>
                    <select x-model="akunPembayaran" class="form-control">
                        <option value="kas">Kas</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nominal Pembayaran</label>
                    <input type="number" x-model="nominalBayar" class="form-control" min="0" step="1000"
                        placeholder="0">
                </div>

                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Nominal Dibayar:</span>
                        <strong>Rp <span x-text="fmtRp(pembayaran)"></span></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Sisa Setelah Bayar:</span>
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

        <div class="row mb-4">
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card border-left-danger">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Total Sisa Kas Bon</p>
                        <h4 class="{{ $summary['total_piutang'] > 0 ? 'text-danger' : '' }}">
                            Rp {{ number_format($summary['total_piutang'], 2, ',', '.') }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Jumlah Kas Bon Aktif</p>
                        <h4>{{ $summary['jumlah_transaksi'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Total Pinjaman</p>
                        <h4>Rp {{ number_format($summary['total_pinjaman'], 2, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Total Sudah Dibayar</p>
                        <h4 class="text-success">Rp {{ number_format($summary['total_dibayar'], 2, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Filter</h4>

                        <form method="GET" action="{{ url('/keuangan/kas-bon-pegawai') }}">
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
                            <div class="d-flex align-items-center">
                                <button type="submit" class="btn btn-primary mr-2">Terapkan</button>
                                <a href="{{ url('/keuangan/kas-bon-pegawai') }}" class="btn btn-light">Reset</a>
                            </div>
                        </form>

                        @if ($byPegawai->isNotEmpty())
                            <hr>
                            <h6 class="mb-2">Rekap per Pegawai</h6>
                            @foreach ($byPegawai as $pegawai)
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-truncate mr-2" style="max-width:140px;"
                                        title="{{ $pegawai->nama }}">{{ $pegawai->nama }}</span>
                                    <span
                                        class="{{ $pegawai->total_piutang > 0 ? 'text-danger' : 'text-muted' }} font-weight-bold small">
                                        Rp {{ number_format($pegawai->total_piutang, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-9 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-1">Daftar Kas Bon Pegawai</h4>
                        <p class="card-description mb-3">
                            Daftar pinjaman pegawai yang masih memiliki sisa piutang pada periode
                            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} –
                            {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}.
                        </p>

                        <div class="table-responsive">
                            <table id="kas-bon-pegawai-table" class="display expandable-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Tanggal Kas Bon</th>
                                        <th>Pegawai</th>
                                        <th>Akun Keluar</th>
                                        <th>Pinjaman Awal</th>
                                        <th>Sisa Piutang</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $trx)
                                        @php
                                            $kodeKasBon =
                                                'KBP-' . str_pad($trx->id_kas_bon_pegawai, 5, '0', STR_PAD_LEFT);
                                            $akunLabel = strtoupper($trx->akun_pengeluaran);
                                        @endphp
                                        <tr id="kas-bon-row-{{ $trx->id_kas_bon_pegawai }}">
                                            <td><span class="badge badge-light">{{ $kodeKasBon }}</span></td>
                                            <td>{{ \Carbon\Carbon::parse($trx->tanggal_pinjam)->format('d-m-Y') }}</td>
                                            <td>{{ $trx->nama_pegawai }}</td>
                                            <td><span class="badge badge-info">{{ $akunLabel }}</span></td>
                                            <td>Rp {{ number_format((float) $trx->nominal_awal, 2, ',', '.') }}</td>
                                            <td>
                                                <span class="text-danger font-weight-bold">
                                                    Rp {{ number_format((float) $trx->sisa_piutang, 2, ',', '.') }}
                                                </span>
                                            </td>
                                            <td style="min-width: 130px;">
                                                <button type="button" class="btn btn-sm btn-warning d-block w-100"
                                                    @click="openBayar({
                                                        id_kas_bon_pegawai: {{ $trx->id_kas_bon_pegawai }},
                                                        kode: '{{ $kodeKasBon }}',
                                                        nama_pegawai: {{ Js::from($trx->nama_pegawai) }},
                                                        sisa_piutang: {{ (float) $trx->sisa_piutang }}
                                                    })">
                                                    <i class="ti-money mr-1"></i> Bayar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                Tidak ada kas bon pegawai aktif pada periode ini.
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
        const BAYAR_KAS_BON_PEGAWAI_URL = '{{ route('keuangan.kas-bon-pegawai.bayar') }}';

        function kasBonPegawaiApp() {
            return {
                showBayar: false,
                bayarTrx: {
                    id_kas_bon_pegawai: 0,
                    kode: '',
                    nama_pegawai: '',
                    sisa_piutang: 0,
                },
                akunPembayaran: 'kas',
                nominalBayar: '',
                bayarLoading: false,
                bayarError: '',
                openBayar(trx) {
                    this.bayarTrx = trx;
                    this.akunPembayaran = 'kas';
                    this.nominalBayar = '';
                    this.bayarError = '';
                    this.showBayar = true;
                },
                get pembayaran() {
                    return parseFloat(this.nominalBayar) || 0;
                },
                get sisaPiutang() {
                    return Math.max(0, (this.bayarTrx.sisa_piutang || 0) - this.pembayaran);
                },
                get isLunas() {
                    return this.pembayaran > 0 && this.sisaPiutang < 0.01;
                },
                fmtRp(val) {
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2
                    }).format(val || 0);
                },
                async submitBayar() {
                    if (this.pembayaran <= 0) {
                        this.bayarError = 'Masukkan nominal pembayaran.';
                        return;
                    }
                    if (this.pembayaran > this.bayarTrx.sisa_piutang + 0.01) {
                        this.bayarError = 'Pembayaran melebihi sisa kas bon pegawai.';
                        return;
                    }
                    this.bayarLoading = true;
                    this.bayarError = '';
                    try {
                        const res = await fetch(BAYAR_KAS_BON_PEGAWAI_URL, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({
                                id_kas_bon_pegawai: this.bayarTrx.id_kas_bon_pegawai,
                                akun_pembayaran: this.akunPembayaran,
                                nominal: this.pembayaran,
                            }),
                        });
                        const json = await res.json();
                        if (!res.ok) {
                            this.bayarError = json.message || Object.values(json.errors || {}).flat()[0] ||
                                'Gagal menyimpan pembayaran.';
                            return;
                        }

                        const id = this.bayarTrx.id_kas_bon_pegawai;
                        if (json.status === 'lunas') {
                            window.kasBonPegawaiDT.row('#kas-bon-row-' + id).remove().draw();
                        } else {
                            this.bayarTrx.sisa_piutang = json.new_sisa_piutang;
                            const row = document.getElementById('kas-bon-row-' + id);
                            if (row) {
                                const cells = row.querySelectorAll('td');
                                cells[5].innerHTML =
                                    '<span class="text-danger font-weight-bold">Rp ' + json
                                    .new_sisa_piutang_formatted + '</span>';
                            }
                            window.kasBonPegawaiDT.draw(false);
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
            window.kasBonPegawaiDT = $('#kas-bon-pegawai-table').DataTable({
                pageLength: 25,
                order: [
                    [1, 'desc']
                ],
                columnDefs: [{
                    targets: [6],
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
                    emptyTable: 'Tidak ada data kas bon pegawai.',
                    zeroRecords: 'Tidak ada data yang cocok.',
                },
            });
        });
    </script>
@endpush
