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
                        <div class="form-group col-md-8">
                            <label for="show_item">Filter Perbekalan</label>
                            <select id="show_item" name="show_item" class="form-control">
                                <option value="">Semua perbekalan (default 3 hari terakhir)</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id_barang }}" @selected((int) $selectedItemId === (int) $item->id_barang)>
                                        {{ $item->nama_barang }} ({{ $item->satuan }}) - Stok
                                        {{ number_format((float) $item->stok_aktual, 2, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Tampilkan Riwayat</button>
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
                            Semua Perbekalan (3 Hari Terakhir)
                        @endif
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Barang</th>
                                    <th>Jenis</th>
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
                                        <td>{{ \Carbon\Carbon::parse($trx->tanggal_transaksi)->format('d-m-Y') }}</td>
                                        <td>{{ $trx->nama_barang }} <small class="text-muted">({{ $trx->satuan }})</small>
                                        </td>
                                        <td>
                                            <span
                                                class="badge {{ $trx->jenis_transaksi === 'in' ? 'badge-success' : 'badge-danger' }}">
                                                {{ strtoupper($trx->jenis_transaksi) }}
                                            </span>
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
                                            <form
                                                action="{{ route('master.perbekalan.transactions.destroy', $trx->id_transaction) }}"
                                                method="POST"
                                                onsubmit="return confirm('Hapus transaksi ini? Stok akan disesuaikan otomatis.')">
                                                @csrf
                                                @method('DELETE')
                                                @if ($selectedItemId)
                                                    <input type="hidden" name="show_item" value="{{ $selectedItemId }}">
                                                @endif
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">
                                            @if ($selectedItem)
                                                Belum ada transaksi untuk perbekalan ini dalam 3 hari terakhir.
                                            @else
                                                Belum ada transaksi perbekalan dalam 3 hari terakhir.
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
