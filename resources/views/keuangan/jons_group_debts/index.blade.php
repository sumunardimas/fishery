@extends('layouts.layout')

@section('title', 'Hutang Modal')

@section('content')
    <div x-data="jonsGroupDebtApp()">
        <div x-show="showBayar" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1060;">
            <div
                style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
                        background:#fff; border-radius:8px; padding:24px; max-width:440px; width:90%;">
                <h5 class="mb-1">Bayar Hutang Modal</h5>
                <p class="text-muted small mb-3">
                    <span x-text="bayarTrx.kode"></span> &mdash; <span x-text="bayarTrx.deskripsi"></span>
                </p>

                <div class="alert alert-warning py-2 mb-3">
                    Sisa Hutang: <strong>Rp <span x-text="fmtRp(bayarTrx.sisa_hutang)"></span></strong>
                </div>

                <div x-show="bayarError" class="alert alert-danger py-2 mb-3" x-text="bayarError" style="display:none;">
                </div>

                <div class="form-group">
                    <label>Pembayaran Dari</label>
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
                            Rp <span x-text="fmtRp(sisaHutang)"></span>
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

        <div class="card mb-4">
            <div class="card-body">
                <h4 class="card-title mb-1">Hutang Modal</h4>
                <p class="card-description mb-3">Filter periode data hutang modal.</p>

                <form method="GET" action="{{ url('/keuangan/hutang-modal') }}" class="mb-0">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-4">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="form-control"
                                value="{{ $startDate }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="form-control"
                                value="{{ $endDate }}">
                        </div>
                        <div class="form-group col-md-4 d-flex align-items-center">
                            <button type="submit" class="btn btn-primary mr-2">Terapkan</button>
                            <a href="{{ url('/keuangan/hutang-modal') }}" class="btn btn-light">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card border-left-danger">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Total Sisa Hutang</p>
                        <h4 class="{{ $summary['total_hutang'] > 0 ? 'text-danger' : '' }}">
                            Rp {{ number_format($summary['total_hutang'], 2, ',', '.') }}
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Jumlah Hutang Aktif</p>
                        <h4>{{ $summary['jumlah_transaksi'] }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Total Pinjaman Awal</p>
                        <h4>Rp {{ number_format($summary['total_pinjaman'], 2, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body text-center">
                        <p class="text-muted mb-1">Total Sudah Dibayar</p>
                        <h4 class="text-success">Rp {{ number_format($summary['total_terbayar'], 2, ',', '.') }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Informasi</h4>
                        <p class="text-muted small mb-0">
                            Setiap transaksi kategori Pinjam Modal Bu Uum dan Pinjam Modal Jons Group pada Kas atau
                            Bank otomatis muncul di sini.
                            Pembayaran akan membentuk kredit baru pada akun yang dipilih.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-9 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-1">Daftar Hutang Modal</h4>
                        <p class="card-description mb-3">
                            Daftar pinjaman modal yang masih memiliki sisa hutang pada periode
                            {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} –
                            {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}.
                        </p>

                        <div class="table-responsive">
                            <table id="jons-group-debt-table" class="display expandable-table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Tanggal Pinjam</th>
                                        <th>Akun Masuk</th>
                                        <th>Deskripsi</th>
                                        <th>Pinjaman Awal</th>
                                        <th>Sisa Hutang</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rows as $trx)
                                        @php
                                            $kodeHutang =
                                                'HJG-' . str_pad($trx->id_jons_group_debt, 5, '0', STR_PAD_LEFT);
                                            $deskripsi = $trx->deskripsi ?: 'Pinjaman modal Jons Group';
                                            $akunLabel = strtoupper($trx->akun_penerimaan);
                                        @endphp
                                        <tr id="debt-row-{{ $trx->id_jons_group_debt }}">
                                            <td><span class="badge badge-light">{{ $kodeHutang }}</span></td>
                                            <td>{{ \Carbon\Carbon::parse($trx->tanggal_pinjam)->format('d-m-Y') }}</td>
                                            <td>
                                                <span class="badge badge-info">{{ $akunLabel }}</span>
                                            </td>
                                            <td>{{ $deskripsi }}</td>
                                            <td>Rp {{ number_format((float) $trx->nominal_awal, 2, ',', '.') }}</td>
                                            <td>
                                                <span class="text-danger font-weight-bold">
                                                    Rp {{ number_format((float) $trx->sisa_hutang, 2, ',', '.') }}
                                                </span>
                                            </td>
                                            <td style="min-width: 130px;">
                                                <button type="button" class="btn btn-sm btn-warning d-block w-100"
                                                    @click="openBayar({
                                                        id_jons_group_debt: {{ $trx->id_jons_group_debt }},
                                                        kode: '{{ $kodeHutang }}',
                                                        deskripsi: {{ Js::from($deskripsi) }},
                                                        sisa_hutang: {{ (float) $trx->sisa_hutang }}
                                                    })">
                                                    <i class="ti-money mr-1"></i> Bayar
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                Tidak ada hutang modal aktif pada periode ini.
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
        const BAYAR_JONS_GROUP_URL = '{{ route('keuangan.hutang-modal.bayar') }}';

        function jonsGroupDebtApp() {
            return {
                showBayar: false,
                bayarTrx: {
                    id_jons_group_debt: 0,
                    kode: '',
                    deskripsi: '',
                    sisa_hutang: 0,
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
                get sisaHutang() {
                    return Math.max(0, (this.bayarTrx.sisa_hutang || 0) - this.pembayaran);
                },
                get isLunas() {
                    return this.pembayaran > 0 && this.sisaHutang < 0.01;
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
                    if (this.pembayaran > this.bayarTrx.sisa_hutang + 0.01) {
                        this.bayarError = 'Pembayaran melebihi sisa hutang.';
                        return;
                    }
                    this.bayarLoading = true;
                    this.bayarError = '';
                    try {
                        const res = await fetch(BAYAR_JONS_GROUP_URL, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({
                                id_jons_group_debt: this.bayarTrx.id_jons_group_debt,
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

                        const id = this.bayarTrx.id_jons_group_debt;
                        if (json.status === 'lunas') {
                            window.jonsGroupDebtDT.row('#debt-row-' + id).remove().draw();
                        } else {
                            this.bayarTrx.sisa_hutang = json.new_sisa_hutang;
                            const row = document.getElementById('debt-row-' + id);
                            if (row) {
                                const cells = row.querySelectorAll('td');
                                cells[5].innerHTML =
                                    '<span class="text-danger font-weight-bold">Rp ' + json
                                    .new_sisa_hutang_formatted + '</span>';
                            }
                            window.jonsGroupDebtDT.draw(false);
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
            window.jonsGroupDebtDT = $('#jons-group-debt-table').DataTable({
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
                    emptyTable: 'Tidak ada data hutang.',
                    zeroRecords: 'Tidak ada data yang cocok.',
                },
            });
        });
    </script>
@endpush
