@extends('layouts.layout')

@section('title', 'Ringkasan Penjualan')

@section('content')
	<div class="row">
		<div class="col-md-4 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-3">Filter</h4>
					<p class="card-description">Pilih periode tanggal untuk merangkum penjualan dari POS.</p>

					<form method="GET" action="{{ route('keuangan.lap-penjualan.index') }}" class="mt-3">
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
							<a href="{{ route('keuangan.lap-penjualan.index') }}" class="btn btn-light">Reset</a>
						</div>
					</form>

					<hr>

					<p class="mb-1 text-muted">Ringkasan periode terpilih</p>
					<p class="mb-1"><strong>Transaksi:</strong> {{ number_format($filteredSummary['total_transaksi']) }}</p>
					<p class="mb-1"><strong>Total Berat:</strong> {{ number_format($filteredSummary['total_berat'], 2, ',', '.') }} kg</p>
					<p class="mb-0"><strong>Total Penjualan:</strong> Rp {{ number_format($filteredSummary['total_pendapatan'], 2, ',', '.') }}</p>
				</div>
			</div>
		</div>

		<div class="col-md-8 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-2">Chart Trends</h4>
					<p class="card-description mb-4">
						Tren penjualan harian.
						@if ($chartMeta['custom'])
							Data mengikuti periode filter ({{ $chartMeta['start'] }} s/d {{ $chartMeta['end'] }}).
						@else
							Default 1 bulan terakhir ({{ $chartMeta['start'] }} s/d {{ $chartMeta['end'] }}).
						@endif
					</p>

					<div style="height: 320px;">
						<canvas id="salesTrendChart"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-2">Default Sale Summary (Active Date)</h4>
					<p class="card-description mb-4">Ringkasan otomatis untuk tanggal aktif saat ini: {{ $today }}.</p>

					<div class="row">
						<div class="col-md-4 mb-3 mb-md-0">
							<div class="border rounded p-3 h-100">
								<p class="text-muted mb-1">Total Transaksi</p>
								<h3 class="mb-0">{{ number_format($summaryToday['total_transaksi']) }}</h3>
							</div>
						</div>
						<div class="col-md-4 mb-3 mb-md-0">
							<div class="border rounded p-3 h-100">
								<p class="text-muted mb-1">Total Berat</p>
								<h3 class="mb-0">{{ number_format($summaryToday['total_berat'], 2, ',', '.') }} kg</h3>
							</div>
						</div>
						<div class="col-md-4">
							<div class="border rounded p-3 h-100">
								<p class="text-muted mb-1">Total Penjualan</p>
								<h3 class="mb-0">Rp {{ number_format($summaryToday['total_pendapatan'], 2, ',', '.') }}</h3>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		$(function() {
			const labels = @json($chartLabels);
			const values = @json($chartValues);

			const ctx = document.getElementById('salesTrendChart');
			if (!ctx) {
				return;
			}

			new Chart(ctx, {
				type: 'bar',
				data: {
					labels: labels,
					datasets: [{
						label: 'Penjualan (Rp)',
						data: values,
						backgroundColor: 'rgba(54, 162, 235, 0.7)',
						borderColor: 'rgba(54, 162, 235, 1)',
						borderWidth: 1,
						barPercentage: 0.65,
						categoryPercentage: 0.75,
					}],
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false,
						},
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								callback: function(value) {
									return 'Rp ' + Number(value).toLocaleString('id-ID');
								}
							}
						}
					}
				}
			});
		});
	</script>
@endpush
