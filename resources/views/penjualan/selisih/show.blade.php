@extends('layouts.layout')

@section('title', 'Rekonsiliasi Selisih Stok')

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
            @endif

            <div class="card mb-4">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-1">Rekonsiliasi Selisih Stok</h4>
                        <p class="card-description mb-0">
                            Invoice INV-{{ str_pad($discrepancy->id_penjualan, 5, '0', STR_PAD_LEFT) }}
                            untuk {{ $discrepancy->nama_customer_display }}.
                        </p>
                    </div>
                    <a href="{{ route('penjualan.selisih.index') }}" class="btn btn-outline-primary">Kembali ke Antrian</a>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Ringkasan Transaksi</h5>
                            <p class="mb-1"><strong>Tanggal:</strong>
                                {{ \Carbon\Carbon::parse($discrepancy->waktu_penjualan)->format('d-m-Y H:i') }}</p>
                            <p class="mb-1"><strong>Customer:</strong> {{ $discrepancy->nama_customer_display }}</p>
                            <p class="mb-3"><strong>Total Tagihan:</strong> Rp
                                {{ number_format($discrepancy->total_harga, 2, ',', '.') }}</p>

                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Ikan</th>
                                            <th>Berat</th>
                                            <th>Harga/kg</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sale->items as $item)
                                            <tr>
                                                <td>{{ $item->ikan?->nama_ikan ?? '—' }}</td>
                                                <td>{{ number_format($item->berat, 2) }} kg</td>
                                                <td>Rp {{ number_format($item->harga_per_kg, 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Ringkasan Selisih</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Ikan</th>
                                            <th>Stok Tersedia</th>
                                            <th>Diminta</th>
                                            <th>Kurang</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($shortageItems as $shortage)
                                            <tr>
                                                <td>{{ $shortage->nama_ikan }}</td>
                                                <td>{{ number_format($shortage->stok_tersedia, 2) }} kg</td>
                                                <td>{{ number_format($shortage->berat_diminta, 2) }} kg</td>
                                                <td class="text-danger font-weight-bold">
                                                    {{ number_format($shortage->berat_selisih, 2) }} kg</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <p class="mb-0"><strong>Catatan kasir:</strong> {{ $discrepancy->catatan_kasir ?: '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Input Rekonsiliasi</h5>
                    <p class="text-muted">Masukkan baris penyesuaian yang diperlukan. Contoh: Tuna -11, Cakalang +11, atau
                        Tuna +2 bila timbangan aktual lebih besar.</p>

                    <form method="POST"
                        action="{{ route('penjualan.selisih.resolve', $discrepancy->id_penjualan_selisih) }}">
                        @csrf
                        <div class="form-group">
                            <label for="catatan_admin">Catatan admin</label>
                            <input type="text" name="catatan_admin" id="catatan_admin" class="form-control"
                                value="{{ old('catatan_admin') }}"
                                placeholder="Contoh: reclass campur Cakalang dari bongkaran kapal A">
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

                        <button type="submit" class="btn btn-primary">Simpan Rekonsiliasi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
