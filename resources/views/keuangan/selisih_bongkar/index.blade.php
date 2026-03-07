@extends('layouts.layout')

@section('title', 'Laporan Selisih Bongkaran')

@section('content')
	<div class="row">
		<div class="col-12">
			@if ($errors->has('message'))
				<x-alert type="danger" :message="$errors->first('message') ?? null" />
			@elseif (session('success'))
				<x-alert type="success" :message="session('success')" />
			@endif
		</div>
	</div>

	<div class="row">
		<div class="col-md-4 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-3">Filter Date Period</h4>
					<p class="card-description">Default 1 bulan terakhir untuk cek selisih bongkaran.</p>

					<form method="GET" action="{{ url('/keuangan/lap-selisih-bongkaran') }}" class="mt-3">
						<div class="form-group">
							<label for="start_date">Start Date</label>
							<input type="date" id="start_date" name="start_date" class="form-control"
								value="{{ $startDate }}">
						</div>
						<div class="form-group">
							<label for="end_date">End Date</label>
							<input type="date" id="end_date" name="end_date" class="form-control"
								value="{{ $endDate }}">
						</div>

						<div class="d-flex align-items-center">
							<button type="submit" class="btn btn-primary mr-2">Terapkan</button>
							<a href="{{ url('/keuangan/lap-selisih-bongkaran') }}" class="btn btn-light">Reset</a>
						</div>
					</form>
				</div>
			</div>
		</div>

		<div class="col-md-8 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-3">Input Berat Lelang Ikan</h4>
					<p class="card-description">Isi total berat versi pasar lelang ikan per tanggal.</p>

					<form method="POST" action="{{ route('keuangan.lap-selisih-bongkaran.store') }}" class="mt-3">
						@csrf
						<input type="hidden" name="start_date" value="{{ $startDate }}">
						<input type="hidden" name="end_date" value="{{ $endDate }}">

						<div class="form-row">
							<div class="form-group col-md-4">
								<label for="tanggal">Tanggal</label>
								<input type="date" id="tanggal" name="tanggal" class="form-control"
									value="{{ old('tanggal', $today->toDateString()) }}" required>
							</div>
							<div class="form-group col-md-4">
								<label for="berat_lelang">Berat Lelang (Kg)</label>
								<input type="number" step="0.01" min="0" id="berat_lelang" name="berat_lelang"
									class="form-control" value="{{ old('berat_lelang') }}" required>
							</div>
							<div class="form-group col-md-4">
								<label for="keterangan">Keterangan</label>
								<input type="text" id="keterangan" name="keterangan" class="form-control"
									value="{{ old('keterangan') }}" placeholder="Opsional">
							</div>
						</div>

						<button type="submit" class="btn btn-success">Simpan Berat Lelang</button>
					</form>

					<hr>
					<p class="mb-1"><strong>Total Berat Penjualan:</strong> {{ number_format($summary['total_penjualan'], 2, ',', '.') }} kg</p>
					<p class="mb-1"><strong>Total Berat Lelang:</strong> {{ number_format($summary['total_lelang'], 2, ',', '.') }} kg</p>
					<p class="mb-0"><strong>Total Selisih:</strong> {{ number_format($summary['total_selisih'], 2, ',', '.') }} kg</p>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-3">Chart Berat Penjualan vs Berat Lelang</h4>
					<div style="height: 340px;">
						<canvas id="selisihBongkarChart"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">Datatable Selisih Bongkaran</h4>
					<p class="card-description">Per tanggal: berat penjualan vs berat lelang dan selisihnya.</p>

					<div class="table-responsive">
						<table id="selisih-bongkar-table" class="display expandable-table" style="width:100%">
							<thead>
								<tr>
									<th>Tanggal</th>
									<th>Berat Penjualan (Kg)</th>
									<th>Berat Lelang (Kg)</th>
									<th>Selisih (Kg)</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($rows as $row)
									<tr>
										<td>{{ $row->tanggal }}</td>
										<td>{{ number_format((float) $row->berat_penjualan, 2, ',', '.') }}</td>
										<td>{{ number_format((float) $row->berat_lelang, 2, ',', '.') }}</td>
										<td>{{ number_format((float) $row->selisih, 2, ',', '.') }}</td>
									</tr>
								@endforeach
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
			$('#selisih-bongkar-table').DataTable({
				...window.dataTableGeneralConfig,
				processing: false,
				serverSide: false,
				order: [
					[0, 'desc']
				],
			});

			const labels = @json($chartLabels);
			const salesData = @json($chartSales);
			const auctionData = @json($chartAuction);

			const ctx = document.getElementById('selisihBongkarChart');
			if (!ctx) {
				return;
			}

			new Chart(ctx, {
				type: 'line',
				data: {
					labels: labels,
					datasets: [{
						label: 'Berat Penjualan (Kg)',
						data: salesData,
						borderColor: 'rgba(54, 162, 235, 1)',
						backgroundColor: 'rgba(54, 162, 235, 0.2)',
						tension: 0.25,
						fill: false,
					},
					{
						label: 'Berat Lelang (Kg)',
						data: auctionData,
						borderColor: 'rgba(255, 159, 64, 1)',
						backgroundColor: 'rgba(255, 159, 64, 0.2)',
						tension: 0.25,
						fill: false,
					}],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						y: {
							beginAtZero: true,
							title: {
								display: true,
								text: 'Kg'
							}
						}
					}
				}
			});
		});
	</script>
@endpush
