@extends('layouts.layout')

@section('title', 'Pembelian Barang Kantor')

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

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Daftar Item dan Sisa Stok</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item</th>
                                    <th>Kategori</th>
                                    <th>Satuan</th>
                                    <th>Sisa Stok</th>
                                    <th>Keterangan</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    <tr>
                                        <td>#{{ $item->id_item_pembelian }}</td>
                                        <td>{{ $item->nama_item }}</td>
                                        <td>{{ $item->kategori }}</td>
                                        <td>{{ $item->satuan }}</td>
                                        <td>{{ number_format((float) $item->total_stok, 2, ',', '.') }}</td>
                                        <td>{{ $item->keterangan ?: '-' }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('pembelian.riwayat', ['show_item' => $item->id_item_pembelian]) }}"
                                                class="btn btn-outline-info btn-sm">Riwayat Transaksi</a>

                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                data-toggle="modal"
                                                data-target="#editItem{{ $item->id_item_pembelian }}">Edit</button>

                                            <form action="{{ route('pembelian.items.destroy', $item->id_item_pembelian) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Hapus master item {{ addslashes($item->nama_item) }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editItem{{ $item->id_item_pembelian }}" tabindex="-1"
                                        role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Master Item</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form
                                                    action="{{ route('pembelian.items.update', $item->id_item_pembelian) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Nama Item</label>
                                                            <input type="text" name="nama_item" class="form-control"
                                                                value="{{ $item->nama_item }}" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Kategori</label>
                                                            <input type="text" name="kategori" class="form-control"
                                                                value="{{ $item->kategori }}" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Satuan</label>
                                                            <input type="text" name="satuan" class="form-control"
                                                                value="{{ $item->satuan }}" required>
                                                        </div>
                                                        <div class="form-group mb-0">
                                                            <label>Keterangan</label>
                                                            <input type="text" name="keterangan" class="form-control"
                                                                value="{{ $item->keterangan }}">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light"
                                                            data-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Simpan
                                                            Perubahan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">Belum ada master item pembelian.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($selectedItem)
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Riwayat Transaksi: {{ $selectedItem->nama_item }}</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>Akun Bayar</th>
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
                                                    action="{{ route('pembelian.transactions.destroy', $trx->id_transaction) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Hapus transaksi ini? Stok akan disesuaikan otomatis.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="btn btn-outline-danger btn-sm">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">Belum ada transaksi untuk item
                                                ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
