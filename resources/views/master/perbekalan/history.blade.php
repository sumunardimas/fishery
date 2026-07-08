@extends('layouts.layout')

@section('title', 'Riwayat IN/OUT Perbekalan')

@section('content')
    <div class="row">
        <div class="col-12">
            @if ($errors->has('message'))
                <x-alert type="danger" :message="$errors->first('message') ?? null" />
            @elseif (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            @if ($errors->any() && !$errors->has('message'))
                <x-alert type="danger" :message="$errors->first()" />
            @endif

            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title mb-1">Riwayat IN/OUT Perbekalan</h4>
                            <p class="card-description mb-0">Lihat histori transaksi masuk dan keluar per item perbekalan.
                            </p>
                        </div>
                        <a href="{{ route('master.perbekalan.index') }}" class="btn btn-outline-primary">Kembali ke
                            Master</a>
                    </div>

                    <form method="GET" action="{{ route('master.perbekalan.history') }}" class="form-row align-items-end">
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-3">
                                <label for="start_date">Tanggal Mulai</label>
                                <input type="date" id="start_date" name="start_date" class="form-control"
                                    value="{{ $startDate }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label for="end_date">Tanggal Akhir</label>
                                <input type="date" id="end_date" name="end_date" class="form-control"
                                    value="{{ $endDate }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label for="show_item">Filter Perbekalan</label>
                                <select id="show_item" name="show_item" class="form-control">
                                    <option value="">Semua Perbekalan</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id_barang }}" @selected((int) $selectedItemId === (int) $item->id_barang)>
                                            {{ $item->nama_barang }} ({{ $item->satuan }}) - Stok
                                            {{ number_format((float) $item->stok_aktual, 2, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2 d-flex">
                                <button type="submit" class="btn btn-primary mr-1 flex-fill">Terapkan</button>
                                <a href="{{ route('master.perbekalan.history') }}" class="btn btn-light">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        Riwayat IN/OUT:
                        @if ($selectedItem)
                            {{ $selectedItem->nama_barang }}
                        @else
                            Semua Perbekalan
                        @endif
                        <small
                            class="text-muted font-weight-normal">({{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }}
                            &ndash; {{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }})</small>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Barang</th>
                                    <th>Jenis</th>
                                    <th>Akun Bayar</th>
                                    <th>Sisa Hutang</th>
                                    <th>Jumlah</th>
                                    <th>Harga Satuan</th>
                                    <th>Total</th>
                                    <th>Sumber/Tujuan</th>
                                    <th>Keterangan</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $trx)
                                    <tr>
                                        @php
                                            $totalHutang = (float) ($trx->total_harga ?? 0);
                                            $terbayarHutang = (float) ($trx->nominal_terbayar_hutang ?? 0);
                                            $sisaHutang = max(0, $totalHutang - $terbayarHutang);
                                            $isHutang =
                                                $trx->jenis_transaksi === 'in' &&
                                                $trx->akun_pembayaran === 'hutang' &&
                                                $totalHutang > 0;
                                        @endphp
                                        <td>{{ \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('d-m-Y') }}</td>
                                        <td>{{ $trx->nama_barang }} <small
                                                class="text-muted">({{ $trx->satuan }})</small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ $trx->jenis_transaksi === 'in' ? 'badge-success' : 'badge-danger' }}">
                                                {{ strtoupper($trx->jenis_transaksi) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($trx->akun_pembayaran)
                                                <span
                                                    class="badge badge-info">{{ strtoupper($trx->akun_pembayaran) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isHutang)
                                                <strong class="{{ $sisaHutang > 0 ? 'text-danger' : 'text-success' }}">
                                                    Rp {{ number_format($sisaHutang, 2, ',', '.') }}
                                                </strong>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format((float) $trx->jumlah, 2, ',', '.') }}</td>
                                        <td>
                                            @if ($trx->harga_satuan !== null)
                                                Rp {{ number_format((float) $trx->harga_satuan, 2, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>Rp {{ number_format((float) $trx->total_harga, 2, ',', '.') }}</td>
                                        <td>{{ $trx->sumber_tujuan ?: '-' }}</td>
                                        <td>{{ $trx->keterangan ?: '-' }}</td>
                                        <td class="text-right">
                                            @if ($isHutang && $sisaHutang > 0)
                                                <form
                                                    action="{{ route('master.perbekalan.transactions.pay-debt', $trx->id_transaction) }}"
                                                    method="POST"
                                                    class="d-flex align-items-center justify-content-end mb-2"
                                                    onsubmit="return confirm('Simpan pembayaran hutang transaksi ini?')">
                                                    @csrf
                                                    @if ($selectedItemId)
                                                        <input type="hidden" name="show_item"
                                                            value="{{ $selectedItemId }}">
                                                    @endif
                                                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                                                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                                                    <select name="akun_pembayaran" class="form-control form-control-sm mr-1"
                                                        style="max-width: 95px;" required>
                                                        <option value="kas">Kas</option>
                                                        <option value="bank">Bank</option>
                                                    </select>
                                                    <input type="number" step="0.01" min="0.01"
                                                        max="{{ number_format($sisaHutang, 2, '.', '') }}" name="nominal"
                                                        class="form-control form-control-sm mr-1" style="max-width: 130px;"
                                                        placeholder="Nominal" required>
                                                    <button type="submit"
                                                        class="btn btn-outline-success btn-sm">Bayar</button>
                                                </form>
                                            @endif
                                            <form
                                                action="{{ route('master.perbekalan.transactions.destroy', $trx->id_transaction) }}"
                                                method="POST"
                                                onsubmit="return confirm('Hapus transaksi ini? Stok akan disesuaikan otomatis.')">
                                                @csrf
                                                @method('DELETE')
                                                @if ($selectedItemId)
                                                    <input type="hidden" name="show_item" value="{{ $selectedItemId }}">
                                                @endif
                                                <input type="hidden" name="start_date" value="{{ $startDate }}">
                                                <input type="hidden" name="end_date" value="{{ $endDate }}">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted">
                                            @if ($selectedItem)
                                                Belum ada transaksi untuk perbekalan ini pada periode yang dipilih.
                                            @else
                                                Belum ada transaksi perbekalan pada periode yang dipilih.
                                            @endif
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
