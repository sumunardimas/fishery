@extends('layouts.layout')

@section('title', 'Sisa Trip Pelayaran')

@section('content')
	<div class="row">
		<div class="col-12">
			@if ($errors->has('message'))
				<x-alert type="danger" :message="$errors->first('message') ?? null" />
			@elseif (session('success'))
				<x-alert type="success" :message="session('success')" />
			@endif

			<div class="card mb-4">
				<div class="card-body">
					<h4 class="card-title mb-1">Sisa Trip</h4>
					<p class="card-description mb-0">Lengkapi sisa perbekalan dan hasil tangkapan untuk menutup pelayaran.</p>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-body">
					<form method="GET" action="{{ route('pelayaran.sisa.index') }}" class="row g-2 align-items-end">
						<div class="col-md-8">
							<label for="pelayaran_id" class="form-label required-asterisk">Pilih Pelayaran Aktif</label>
							<select name="pelayaran_id" id="pelayaran_id" class="form-control" required>
								@forelse ($activePelayaran as $trip)
									<option value="{{ $trip->id_pelayaran }}"
										{{ (string) ($selectedPelayaran->id_pelayaran ?? '') === (string) $trip->id_pelayaran ? 'selected' : '' }}>
										#{{ $trip->id_pelayaran }} - {{ $trip->kapal->nama_kapal ?? '-' }} ({{ $trip->pelabuhan_asal }} -> {{ $trip->pelabuhan_tujuan }})
									</option>
								@empty
									<option value="">Tidak ada pelayaran aktif</option>
								@endforelse
							</select>
						</div>
						<div class="col-md-4">
							<button type="submit" class="btn btn-primary w-100">Muat Form</button>
						</div>
					</form>
				</div>
			</div>

			@if ($selectedPelayaran)
				<form action="{{ route('pelayaran.sisa.store') }}" method="POST">
					@csrf
					<input type="hidden" name="id_pelayaran" value="{{ $selectedPelayaran->id_pelayaran }}">

					<div class="card mb-4">
						<div class="card-body">
							<div class="d-flex justify-content-between align-items-center mb-3">
								<div>
									<h5 class="mb-1">Card Sisa Perbekalan</h5>
									<p class="text-muted mb-0">Data awal diambil dari perencanaan `perbekalan_pelayaran`.</p>
								</div>
								<span class="badge badge-info">Trip #{{ $selectedPelayaran->id_pelayaran }}</span>
							</div>

							<div class="table-responsive">
								<table class="table table-bordered">
									<thead>
										<tr>
											<th>Nama Barang</th>
											<th>Kategori</th>
											<th>Satuan</th>
											<th>Jumlah Awal</th>
											<th>Jumlah Sisa</th>
										</tr>
									</thead>
									<tbody>
										@forelse ($perbekalanRows as $row)
											@php
												$defaultSisa = $existingSisa[$row->id_barang] ?? null;
											@endphp
											<tr>
												<td>{{ $row->nama_barang }}</td>
												<td>{{ $row->kategori }}</td>
												<td>{{ $row->satuan }}</td>
												<td>{{ number_format($row->jumlah_awal, 2) }}</td>
												<td>
													<input
														type="number"
														class="form-control"
														name="sisa_qty[{{ $row->id_barang }}]"
														min="0"
														max="{{ $row->jumlah_awal }}"
														step="0.01"
														placeholder="0"
														value="{{ old('sisa_qty.' . $row->id_barang, $defaultSisa) }}">
												</td>
											</tr>
										@empty
											<tr>
												<td colspan="5" class="text-center text-muted">Belum ada perbekalan terencana untuk pelayaran ini.</td>
											</tr>
										@endforelse
									</tbody>
								</table>
							</div>

							<div class="form-group mt-3">
								<label for="catatan_sisa">Catatan Sisa Trip</label>
								<textarea name="catatan_sisa" id="catatan_sisa" rows="3" class="form-control"
									placeholder="Opsional: catatan kondisi sisa perbekalan.">{{ old('catatan_sisa') }}</textarea>
							</div>
						</div>
					</div>

					<div class="card mb-4">
						<div class="card-body">
							<h5 class="mb-1">Card Hasil Tangkapan Ikan</h5>
							<p class="text-muted mb-3">Isi berat hasil tangkapan per jenis ikan (kg). Hanya nilai > 0 yang disimpan.</p>

							<div class="table-responsive">
								<table class="table table-bordered">
									<thead>
										<tr>
											<th>Nama Ikan</th>
											<th>Jenis Ikan</th>
											<th>Berat Tangkapan (kg)</th>
										</tr>
									</thead>
									<tbody>
										@forelse ($masterIkan as $ikan)
											@php
												$defaultHasil = $existingHasilIkan[$ikan->id_ikan] ?? null;
											@endphp
											<tr>
												<td>{{ $ikan->nama_ikan }}</td>
												<td>{{ $ikan->jenis_ikan }}</td>
												<td>
													<input
														type="number"
														class="form-control"
														name="hasil_ikan[{{ $ikan->id_ikan }}]"
														min="0"
														step="0.01"
														placeholder="0"
														value="{{ old('hasil_ikan.' . $ikan->id_ikan, $defaultHasil) }}">
												</td>
											</tr>
										@empty
											<tr>
												<td colspan="3" class="text-center text-muted">Master ikan belum tersedia.</td>
											</tr>
										@endforelse
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="d-flex gap-2">
						<button type="submit" class="btn btn-success">Simpan Dan Tutup Pelayaran</button>
						<a href="{{ route('pelayaran.index') }}" class="btn btn-light">Batal</a>
					</div>
				</form>
			@else
				<div class="card">
					<div class="card-body text-center py-5">
						<h5 class="mb-2">Tidak ada pelayaran aktif untuk diproses</h5>
						<p class="text-muted mb-0">Buat rencana pelayaran baru terlebih dahulu.</p>
					</div>
				</div>
			@endif
		</div>
	</div>
@endsection

