@extends('layouts.layout')

@section('title', 'Operasional Kantor')

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

            <div class="card mb-4" id="history-operasional-kantor">
                <div class="card-body">
                    <h4 class="card-title mb-1">Ringkasan Harian Operasional Kantor</h4>
                    <p class="card-description mb-4">Filter periode tanggal. Default menampilkan 30 hari terakhir.</p>

                    <form method="GET" action="{{ route('operasional-kantor.history') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date">Tanggal Mulai</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control"
                                        value="{{ $startDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date">Tanggal Akhir</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control"
                                        value="{{ $endDate }}">
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center mb-3">
                                <button type="submit" class="btn btn-primary mr-2">Terapkan</button>
                                <a href="{{ route('operasional-kantor.history') }}" class="btn btn-light">Reset</a>
                            </div>
                        </div>
                    </form>

                    <p class="mb-3"><strong>Grand Total Periode:</strong> Rp
                        {{ number_format($summaryGrandTotal, 2, ',', '.') }}</p>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Total Item</th>
                                    <th>Total Biaya</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dailySummary as $row)
                                    @php
                                        $detailDateParam = \Carbon\Carbon::parse($row->tanggal)->toDateString();
                                    @endphp
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</td>
                                        <td>{{ $row->total_item }}</td>
                                        <td>Rp {{ number_format((float) $row->grand_total, 2, ',', '.') }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('operasional-kantor.history', ['start_date' => $startDate, 'end_date' => $endDate, 'detail_date' => $detailDateParam]) }}#detail-operasional-kantor"
                                                class="btn btn-outline-info btn-sm">
                                                Lihat Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada data operasional
                                            kantor pada periode ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card" id="detail-operasional-kantor">
                <div class="card-body">
                    <h4 class="card-title mb-1">Detail Operasional Kantor</h4>
                    @if ($detailDate)
                        <p class="card-description mb-4">
                            Detail tanggal {{ \Carbon\Carbon::parse($detailDate)->format('d-m-Y') }}.
                            Grand total: <strong>Rp {{ number_format($detailGrandTotal, 2, ',', '.') }}</strong>
                        </p>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Kategori</th>
                                        <th>Item</th>
                                        <th>Akun Bayar</th>
                                        <th>Harga Satuan</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                        <th>Keterangan</th>
                                        <th class="text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($detailRows as $detail)
                                        <tr>
                                            <td>{{ $detail->kategori ?: $detail->jenis_biaya }}</td>
                                            <td>{{ $detail->item ?: $detail->deskripsi }}</td>
                                            <td>
                                                @if (!empty($detail->akun_pembayaran))
                                                    <span
                                                        class="badge badge-info">{{ strtoupper($detail->akun_pembayaran) }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>Rp {{ number_format((float) ($detail->harga_satuan ?? 0), 2, ',', '.') }}
                                            </td>
                                            <td>{{ number_format((float) ($detail->qty ?? 0), 2, ',', '.') }}</td>
                                            <td>Rp
                                                {{ number_format((float) ($detail->total_biaya ?? ($detail->jumlah ?? 0)), 2, ',', '.') }}
                                            </td>
                                            <td>{{ $detail->keterangan ?: '-' }}</td>
                                            <td class="text-right">
                                                <form
                                                    action="{{ route('operasional-kantor.transactions.destroy', $detail->id_operasional_kantor) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Hapus transaksi ini? Saldo akun akan dikembalikan otomatis.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="start_date" value="{{ $startDate }}">
                                                    <input type="hidden" name="end_date" value="{{ $endDate }}">
                                                    <input type="hidden" name="detail_date" value="{{ $detailDate }}">
                                                    <button type="submit"
                                                        class="btn btn-outline-danger btn-sm">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">Tidak ada detail pada
                                                tanggal ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="card-description mb-0">
                            Klik tombol <strong>Lihat Detail</strong> pada ringkasan harian untuk menampilkan daftar
                            detail.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
