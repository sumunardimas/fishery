@extends('layouts.layout')

@section('title', 'Selisih Stok Penjualan')

@section('content')
    @php
        $oldAdjustments = old('adjustments', [
            ['id_storage' => '', 'id_ikan' => '', 'delta_berat' => '', 'keterangan' => ''],
        ]);
    @endphp

    <div class="row" x-data="{
        adjustments: {{ Js::from($oldAdjustments) }},
        addAdjustment() {
            this.adjustments.push({ id_storage: '', id_ikan: '', delta_berat: '', keterangan: '' });
        },
        removeAdjustment(index) {
            if (this.adjustments.length > 1) this.adjustments.splice(index, 1);
        }
    }">
        <div class="col-12">
            @if ($errors->has('message'))
                <x-alert type="danger" :message="$errors->first('message') ?? null" />
            @elseif ($errors->any())
                <x-alert type="danger" :message="$errors->first()" />
            @elseif (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif

            <div class="card mb-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">Selisih Stok Penjualan</h4>
                        <p class="card-description mb-0">Antrian penjualan yang disimpan cepat saat stok kurang, plus form
                            penyesuaian stok manual untuk admin atau gudang.</p>
                    </div>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('penjualan.riwayat') }}" class="btn btn-outline-primary mr-2">Riwayat</a>
                        <a href="{{ route('penjualan.index') }}" class="btn btn-primary">Kembali ke POS</a>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <small class="text-muted">Selisih Pending</small>
                            <h4>{{ $pendingDiscrepancies->count() }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <small class="text-muted">Penyesuaian Terakhir</small>
                            <h4>{{ $recentAdjustments->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Antrian Rekonsiliasi</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Ringkasan Selisih</th>
                                    <th>Catatan Kasir</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pendingDiscrepancies as $item)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($item->waktu_selisih)->format('d-m-Y H:i') }}</td>
                                        <td>INV-{{ str_pad($item->id_penjualan, 5, '0', STR_PAD_LEFT) }}</td>
                                        <td>{{ $item->nama_customer_display }}</td>
                                        <td>
                                            @foreach ($shortageItems[$item->id_penjualan_selisih] ?? collect() as $shortage)
                                                <div>
                                                    {{ $shortage->nama_ikan }}:
                                                    kurang {{ number_format($shortage->berat_selisih, 2) }} kg
                                                    dari permintaan {{ number_format($shortage->berat_diminta, 2) }} kg
                                                </div>
                                            @endforeach
                                        </td>
                                        <td>{{ $item->catatan_kasir ?: '—' }}</td>
                                        <td>
                                            <a href="{{ route('penjualan.selisih.show', $item->id_penjualan_selisih) }}"
                                                class="btn btn-sm btn-outline-warning">
                                                Tindak Lanjuti
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Tidak ada selisih stok yang masih
                                            pending.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Penyesuaian Stok Manual</h5>
                    <p class="text-muted">Gunakan form ini untuk koreksi gudang yang tidak berasal dari transaksi penjualan
                        tertentu.</p>
                    <form method="POST" action="{{ route('penjualan.selisih.manual') }}">
                        @csrf
                        <div class="form-group">
                            <label for="catatan">Catatan</label>
                            <input type="text" name="catatan" id="catatan" class="form-control"
                                value="{{ old('catatan') }}"
                                placeholder="Contoh: koreksi timbang pagi / salah klasifikasi bongkaran">
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0">Baris Penyesuaian</label>
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="addAdjustment()">
                                <i class="ti-plus mr-1"></i> Tambah Baris
                            </button>
                        </div>

                        <template x-for="(adjustment, index) in adjustments" :key="index">
                            <div class="border rounded p-3 mb-2">
                                <div class="row">
                                    <div class="col-md-3 form-group mb-2">
                                        <label>Storage Kapal</label>
                                        <select class="form-control" :name="'adjustments[' + index + '][id_storage]'"
                                            x-model="adjustment.id_storage" required>
                                            <option value="">Pilih storage</option>
                                            @foreach ($storages as $storage)
                                                <option value="{{ $storage->id_storage }}">{{ $storage->nama_kapal }} -
                                                    {{ $storage->nama_storage }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group mb-2">
                                        <label>Ikan</label>
                                        <select class="form-control" :name="'adjustments[' + index + '][id_ikan]'"
                                            x-model="adjustment.id_ikan" required>
                                            <option value="">Pilih ikan</option>
                                            @foreach ($fishOptions as $fish)
                                                <option value="{{ $fish->id_ikan }}">{{ $fish->nama_ikan }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-group mb-2">
                                        <label>Delta (kg)</label>
                                        <input type="number" step="0.01" class="form-control"
                                            :name="'adjustments[' + index + '][delta_berat]'"
                                            x-model="adjustment.delta_berat" placeholder="+ / -" required>
                                    </div>
                                    <div class="col-md-3 form-group mb-2">
                                        <label>Keterangan</label>
                                        <input type="text" class="form-control"
                                            :name="'adjustments[' + index + '][keterangan]'" x-model="adjustment.keterangan"
                                            placeholder="Opsional">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end form-group mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-danger w-100"
                                            @click="removeAdjustment(index)"
                                            x-show="adjustments.length > 1">&times;</button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <button type="submit" class="btn btn-primary">Simpan Penyesuaian Manual</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Riwayat Penyesuaian Terakhir</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>Sumber</th>
                                    <th>Referensi</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentAdjustments as $adjustment)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($adjustment->created_at)->format('d-m-Y H:i') }}</td>
                                        <td>{{ $adjustment->tipe_sumber === 'manual' ? 'Manual' : 'Rekonsiliasi Penjualan' }}
                                        </td>
                                        <td>
                                            @if ($adjustment->id_penjualan)
                                                INV-{{ str_pad($adjustment->id_penjualan, 5, '0', STR_PAD_LEFT) }}
                                                @if ($adjustment->nama_customer_display)
                                                    - {{ $adjustment->nama_customer_display }}
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $adjustment->catatan ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Belum ada penyesuaian stok yang
                                            tersimpan.</td>
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
