@extends('layouts.layout')

@section('title', 'Laba Rugi')

@section('content')
	<div class="row">
		<div class="col-md-4 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-3">Filter for Date Period</h4>
					<p class="card-description">Default data 1 bulan terakhir.</p>

					<form method="GET" action="{{ url('/keuangan/laba') }}" class="mt-3">
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
							<a href="{{ url('/keuangan/laba') }}" class="btn btn-light">Reset</a>
						</div>
					</form>
				</div>
			</div>
		</div>

		<div class="col-md-8 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-3">Profit/Loss Status</h4>
					<p class="card-description mb-4">Status laba rugi berdasarkan periode terpilih.</p>

					<div class="row">
						<div class="col-md-4 mb-3 mb-md-0">
							<div class="border rounded p-3 h-100">
								<p class="text-muted mb-1">Total Revenue</p>
								<h4 class="mb-0">Rp {{ number_format($profitLoss['total_revenue'], 2, ',', '.') }}</h4>
							</div>
						</div>
						<div class="col-md-4 mb-3 mb-md-0">
							<div class="border rounded p-3 h-100">
								<p class="text-muted mb-1">Total Expenditures</p>
								<h4 class="mb-0">Rp {{ number_format($profitLoss['total_expenditure'], 2, ',', '.') }}</h4>
							</div>
						</div>
						<div class="col-md-4">
							<div class="border rounded p-3 h-100">
								<p class="text-muted mb-1">Status (Net)</p>
								<h4 class="mb-1">{{ $profitLoss['status'] }}</h4>
								<p class="mb-0">Rp {{ number_format($profitLoss['net'], 2, ',', '.') }}</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">Daily Revenue vs Expenditures</h4>
					<p class="card-description">Per tanggal menampilkan total pemasukan dan pengeluaran.</p>

					<div class="table-responsive">
						<table id="laba-rugi-table" class="display expandable-table" style="width:100%">
							<thead>
								<tr>
									<th>Tanggal</th>
									<th>Total Revenue</th>
									<th>Total Expenditures</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($rows as $row)
									<tr>
										<td>{{ $row->tanggal }}</td>
										<td>Rp {{ number_format((float) $row->total_revenue, 2, ',', '.') }}</td>
										<td>Rp {{ number_format((float) $row->total_expenditure, 2, ',', '.') }}</td>
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
			$('#laba-rugi-table').DataTable({
				...window.dataTableGeneralConfig,
				processing: false,
				serverSide: false,
				order: [
					[0, 'desc']
				],
			});
		});
	</script>
@endpush
