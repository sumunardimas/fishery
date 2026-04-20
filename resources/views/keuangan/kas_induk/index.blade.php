@extends('layouts.layout')

@section('title', 'Setoran Kas Induk')

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title mb-1">Setoran Kas Induk</h4>
            <p class="card-description mb-3">
                Catat pemindahan saldo berlebih dari kas kantor atau bank ke kas induk. Dana yang disetor tidak lagi
                dihitung sebagai saldo kantor aktif.
            </p>

            <form method="GET" action="{{ url('/keuangan/kas-induk') }}" class="mb-0">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label for="start_date">Tanggal Mulai</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                            value="{{ $startDate }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="end_date">Tanggal Akhir</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                            value="{{ $endDate }}">
                    </div>
                    <div class="form-group col-md-4 d-flex align-items-center">
                        <button type="submit" class="btn btn-primary mr-2">Terapkan</button>
                        <a href="{{ url('/keuangan/kas-induk') }}" class="btn btn-light">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card border-left-primary">
                <div class="card-body text-center">
                    <p class="text-muted mb-1">Total Setoran</p>
                    <h4 class="text-primary">Rp {{ number_format($summary['total_setoran'], 2, ',', '.') }}</h4>
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
                    <p class="text-muted mb-1">Disetor Dari Kas</p>
                    <h4>Rp {{ number_format($summary['total_dari_kas'], 2, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-1">Disetor Dari Bank</p>
                    <h4>Rp {{ number_format($summary['total_dari_bank'], 2, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Input Setoran</h4>
                    <p class="card-description">Pilih akun sumber yang akan dikurangi lalu catat nominal setoran ke kas
                        induk.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 pl-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('keuangan.kas-induk.store') }}">
                        @csrf

                        <div class="form-group">
                            <label class="required-asterisk" for="tanggal">Tanggal</label>
                            <input type="date" id="tanggal" name="tanggal" class="form-control"
                                value="{{ old('tanggal', now()->toDateString()) }}" required>
                        </div>

                        <div class="form-group">
                            <label class="required-asterisk" for="akun_sumber">Akun Sumber</label>
                            <select id="akun_sumber" name="akun_sumber" class="form-control" required>
                                <option value="kas" {{ old('akun_sumber', 'kas') === 'kas' ? 'selected' : '' }}>Kas
                                </option>
                                <option value="bank" {{ old('akun_sumber') === 'bank' ? 'selected' : '' }}>Bank</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="required-asterisk" for="nominal">Nominal Setoran</label>
                            <input type="text" id="nominal" name="nominal" class="form-control" data-rupiah-input
                                value="{{ old('nominal') }}" placeholder="0,00" required>
                        </div>

                        <div class="form-group">
                            <label for="deskripsi">Deskripsi</label>
                            <input type="text" id="deskripsi" name="deskripsi" class="form-control"
                                value="{{ old('deskripsi') }}" maxlength="255"
                                placeholder="Contoh: Setoran saldo berlebih akhir minggu">
                            <small class="form-text text-muted">
                                Keterangan ini juga akan muncul sebagai kredit pada mutasi kas atau bank sumber.
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Simpan Setoran</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-1">Riwayat Setoran Kas Induk</h4>
                    <p class="card-description mb-3">
                        Riwayat dana yang sudah dipindahkan keluar dari saldo kantor pada periode
                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} –
                        {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}.
                    </p>

                    <div class="table-responsive">
                        <table id="kas-induk-table" class="display expandable-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th>Akun Sumber</th>
                                    <th>Deskripsi</th>
                                    <th>Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    @php
                                        $kodeSetoran =
                                            'KSI-' . str_pad($row->id_kas_induk_transfer, 5, '0', STR_PAD_LEFT);
                                    @endphp
                                    <tr>
                                        <td><span class="badge badge-light">{{ $kodeSetoran }}</span></td>
                                        <td>{{ \Carbon\Carbon::parse($row->tanggal_setor)->format('d-m-Y') }}</td>
                                        <td><span class="badge badge-info">{{ strtoupper($row->akun_sumber) }}</span></td>
                                        <td>{{ $row->deskripsi ?: '-' }}</td>
                                        <td>Rp {{ number_format((float) $row->nominal, 2, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            Belum ada setoran kas induk pada periode ini.
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

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#kas-induk-table').DataTable({
                ...window.dataTableGeneralConfig,
                processing: false,
                serverSide: false,
                order: [
                    [1, 'desc'],
                    [0, 'desc']
                ],
            });
        });
    </script>
@endpush
