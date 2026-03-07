@extends('layouts.layout')

@section('title', 'POS Penjualan Ikan')

@section('content')
	<div class="row" x-data="{
		createNewCustomer: false,
		ikanMap: {{ Js::from($ikanStock->keyBy('id_ikan')->map(fn($i) => ['harga_default' => $i->harga_default, 'stok_tersedia' => $i->stok_tersedia])) }},
		selectedIkan: '{{ old('id_ikan') }}',
		hargaPerKg: '{{ old('harga_per_kg') }}',
		updateHarga() {
			if (!this.selectedIkan) return;
			if (!this.hargaPerKg || Number(this.hargaPerKg) <= 0) {
				this.hargaPerKg = this.ikanMap[this.selectedIkan]?.harga_default ?? '';
			}
		},
		stokTersedia() {
			if (!this.selectedIkan) return 0;
			return this.ikanMap[this.selectedIkan]?.stok_tersedia ?? 0;
		}
	}" x-init="updateHarga()">
		<div class="col-12">
			@if ($errors->has('message'))
				<x-alert type="danger" :message="$errors->first('message') ?? null" />
			@elseif (session('success'))
				<x-alert type="success" :message="session('success')" />
			@endif

			<div class="card mb-4">
				<div class="card-body d-flex justify-content-between align-items-center">
					<div>
						<h4 class="card-title mb-1">POS Penjualan Ikan</h4>
						<p class="card-description mb-0">Penjualan berdasarkan stok hasil tangkapan yang tersedia.</p>
					</div>
					<a href="{{ route('penjualan.report') }}" class="btn btn-outline-primary">Lihat Laporan Penjualan</a>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-body">
					<h5 class="mb-3">Status Dana Hari Ini ({{ \Carbon\Carbon::parse($today)->format('d M Y') }})</h5>

					@if (!$kasHarian)
						<form action="{{ route('penjualan.open-kas') }}" method="POST" class="row g-2 align-items-end">
							@csrf
							<div class="col-md-6">
								<label for="saldo_awal" class="form-label required-asterisk">Saldo Awal Hari</label>
								<input type="number" step="0.01" min="0" class="form-control" id="saldo_awal"
									name="saldo_awal" value="{{ old('saldo_awal', 0) }}" required>
							</div>
							<div class="col-md-6">
								<button type="submit" class="btn btn-success">Buka Saldo Hari Ini</button>
							</div>
						</form>
					@else
						<div class="row">
							<div class="col-md-3">
								<div class="border rounded p-3">
									<small class="text-muted d-block">Saldo Awal</small>
									<strong>Rp {{ number_format($kasHarian->saldo_awal, 2, ',', '.') }}</strong>
								</div>
							</div>
							<div class="col-md-3">
								<div class="border rounded p-3">
									<small class="text-muted d-block">Total Masuk</small>
									<strong>Rp {{ number_format($kasHarian->total_masuk, 2, ',', '.') }}</strong>
								</div>
							</div>
							<div class="col-md-3">
								<div class="border rounded p-3">
									<small class="text-muted d-block">Total Keluar</small>
									<strong>Rp {{ number_format($kasHarian->total_keluar, 2, ',', '.') }}</strong>
								</div>
							</div>
							<div class="col-md-3">
								<div class="border rounded p-3">
									<small class="text-muted d-block">Saldo Akhir Sementara</small>
									<strong>Rp {{ number_format($kasHarian->saldo_akhir, 2, ',', '.') }}</strong>
								</div>
							</div>
						</div>

						<div class="mt-3">
							@if (!$kasHarian->status_tutup)
								<form action="{{ route('penjualan.close-kas') }}" method="POST"
									onsubmit="return confirm('Tutup kas hari ini?')">
									@csrf
									<button type="submit" class="btn btn-danger">Tutup Kas Hari Ini</button>
								</form>
							@else
								<span class="badge badge-dark">Kas Sudah Ditutup pada {{ $kasHarian->waktu_tutup?->format('d-m-Y H:i') }}</span>
							@endif
						</div>
					@endif
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-body">
					<h5 class="mb-3">Transaksi Penjualan Baru</h5>
					<form action="{{ route('penjualan.store') }}" method="POST">
						@csrf

						<div class="row">
							<div class="col-md-6 form-group">
								<label class="required-asterisk" for="id_ikan">Pilih Ikan</label>
								<select class="form-control @error('id_ikan') is-invalid @enderror" name="id_ikan" id="id_ikan"
									x-model="selectedIkan" @change="updateHarga()" required>
									<option value="">Pilih ikan</option>
									@foreach ($ikanStock as $ikan)
										<option value="{{ $ikan->id_ikan }}" {{ (string) old('id_ikan') === (string) $ikan->id_ikan ? 'selected' : '' }}>
											{{ $ikan->nama_ikan }} - Stok: {{ number_format($ikan->stok_tersedia, 2) }} kg
										</option>
									@endforeach
								</select>
								@error('id_ikan')<div class="invalid-feedback">{{ $message }}</div>@enderror
							</div>

							<div class="col-md-3 form-group">
								<label class="required-asterisk" for="berat">Jumlah (kg)</label>
								<input type="number" min="0.01" step="0.01"
									class="form-control @error('berat') is-invalid @enderror" name="berat" id="berat"
									value="{{ old('berat') }}" required>
								@error('berat')<div class="invalid-feedback">{{ $message }}</div>@enderror
							</div>

							<div class="col-md-3 form-group">
								<label class="required-asterisk" for="harga_per_kg">Harga per kg</label>
								<input type="number" min="1" step="0.01" x-model="hargaPerKg"
									class="form-control @error('harga_per_kg') is-invalid @enderror" name="harga_per_kg" id="harga_per_kg"
									value="{{ old('harga_per_kg') }}" required>
								@error('harga_per_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
							</div>
						</div>

						<div class="mb-3">
							<small class="text-muted">Stok tersedia saat ini: <strong x-text="Number(stokTersedia()).toFixed(2)"></strong> kg</small>
						</div>

						<div class="form-group form-check mb-3" style="padding-left: 1.5rem;">
							<input type="checkbox" class="form-check-input" id="create_new_customer" name="create_new_customer" value="1"
								x-model="createNewCustomer" {{ old('create_new_customer') ? 'checked' : '' }}>
							<label class="form-check-label" for="create_new_customer">Tambah customer baru</label>
						</div>

						<div x-show="!createNewCustomer" class="form-group">
							<label class="required-asterisk" for="id_customer">Customer</label>
							<select class="form-control @error('id_customer') is-invalid @enderror" name="id_customer" id="id_customer"
								:required="!createNewCustomer">
								<option value="">Pilih customer</option>
								@foreach ($customers as $customer)
									<option value="{{ $customer->id_customer }}" {{ (string) old('id_customer') === (string) $customer->id_customer ? 'selected' : '' }}>
										{{ $customer->nama_customer }}
									</option>
								@endforeach
							</select>
							@error('id_customer')<div class="invalid-feedback">{{ $message }}</div>@enderror
						</div>

						<div x-show="createNewCustomer" class="border rounded p-3 mb-3">
							<div class="form-group">
								<label class="required-asterisk" for="nama_customer_baru">Nama Customer</label>
								<input type="text" class="form-control @error('nama_customer_baru') is-invalid @enderror"
									name="nama_customer_baru" id="nama_customer_baru" value="{{ old('nama_customer_baru') }}"
									:required="createNewCustomer">
								@error('nama_customer_baru')<div class="invalid-feedback">{{ $message }}</div>@enderror
							</div>

							<div class="form-group">
								<label for="alamat_customer_baru">Alamat (Opsional)</label>
								<input type="text" class="form-control @error('alamat_customer_baru') is-invalid @enderror"
									name="alamat_customer_baru" id="alamat_customer_baru" value="{{ old('alamat_customer_baru') }}">
								@error('alamat_customer_baru')<div class="invalid-feedback">{{ $message }}</div>@enderror
							</div>

							<div class="form-group mb-0">
								<label for="telepon_customer_baru">Telepon (Opsional)</label>
								<input type="text" class="form-control @error('telepon_customer_baru') is-invalid @enderror"
									name="telepon_customer_baru" id="telepon_customer_baru" value="{{ old('telepon_customer_baru') }}">
								@error('telepon_customer_baru')<div class="invalid-feedback">{{ $message }}</div>@enderror
							</div>
						</div>

						<div class="form-group">
							<label for="keterangan">Keterangan</label>
							<textarea name="keterangan" id="keterangan" rows="2" class="form-control">{{ old('keterangan') }}</textarea>
						</div>

						<button type="submit" class="btn btn-primary" {{ $kasHarian && $kasHarian->status_tutup ? 'disabled' : '' }}>
							Simpan Transaksi
						</button>
					</form>
				</div>
			</div>

			<div class="row mb-4">
				<div class="col-md-4">
					<div class="card"><div class="card-body"><small class="text-muted">Total Transaksi Hari Ini</small><h4>{{ $summaryToday['total_transaksi'] }}</h4></div></div>
				</div>
				<div class="col-md-4">
					<div class="card"><div class="card-body"><small class="text-muted">Total Berat Hari Ini</small><h4>{{ number_format($summaryToday['total_berat'], 2) }} kg</h4></div></div>
				</div>
				<div class="col-md-4">
					<div class="card"><div class="card-body"><small class="text-muted">Total Pendapatan Hari Ini</small><h4>Rp {{ number_format($summaryToday['total_pendapatan'], 2, ',', '.') }}</h4></div></div>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Transaksi Hari Ini</h5>
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>Waktu</th>
									<th>Ikan</th>
									<th>Customer</th>
									<th>Berat</th>
									<th>Harga/kg</th>
									<th>Total</th>
								</tr>
							</thead>
							<tbody>
								@forelse ($todaySales as $trx)
									<tr>
										<td>{{ $trx->created_at?->format('H:i') }}</td>
										<td>{{ $trx->nama_ikan }}</td>
										<td>{{ $trx->nama_customer_display }}</td>
										<td>{{ number_format($trx->berat, 2) }} kg</td>
										<td>Rp {{ number_format($trx->harga_per_kg, 2, ',', '.') }}</td>
										<td>Rp {{ number_format($trx->total_harga, 2, ',', '.') }}</td>
									</tr>
								@empty
									<tr><td colspan="6" class="text-center text-muted">Belum ada transaksi hari ini.</td></tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

