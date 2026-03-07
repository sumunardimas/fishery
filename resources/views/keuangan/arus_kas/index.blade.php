@extends('layouts.layout')

@section('title', 'Arus Kas')

@section('content')
	<div class="row">
		<div class="col-md-4 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-3">Filter</h4>
					<p class="card-description">Default menampilkan arus kas 1 bulan terakhir.</p>

					<form method="GET" action="{{ url('/keuangan/arus-kas') }}" class="mt-3">
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
							<a href="{{ url('/keuangan/arus-kas') }}" class="btn btn-light">Reset</a>
						</div>
					</form>

					<hr>
					<p class="mb-1"><strong>Total Masuk:</strong> Rp {{ number_format($summary['total_masuk'], 2, ',', '.') }}</p>
					<p class="mb-1"><strong>Total Keluar:</strong> Rp {{ number_format($summary['total_keluar'], 2, ',', '.') }}</p>
					<p class="mb-0"><strong>Net Periode:</strong> Rp {{ number_format($summary['net'], 2, ',', '.') }}</p>
				</div>
			</div>
		</div>

		<div class="col-md-8 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-3">Current Balance</h4>
					<p class="card-description mb-4">Saldo kas terbaru berdasarkan transaksi terakhir.</p>

					<div class="border rounded p-4 text-center">
						<p class="text-muted mb-2">Saldo Terkini</p>
						<h2 class="mb-0">Rp {{ number_format($currentBalance, 2, ',', '.') }}</h2>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title">Datatable Arus Kas (In/Out)</h4>
					<p class="card-description">Daftar transaksi arus kas pada periode terpilih.</p>

					<div class="table-responsive">
						<table id="arus-kas-table" class="display expandable-table" style="width:100%">
							<thead>
								<tr>
									<th>ID</th>
									<th>Tanggal</th>
									<th>Jenis</th>
									<th>Kategori</th>
									<th>Deskripsi</th>
									<th>Uang Masuk</th>
									<th>Uang Keluar</th>
									<th>Saldo</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($rows as $row)
									<tr>
										<td>{{ $row->id_kas }}</td>
										<td>{{ $row->tanggal }}</td>
										<td>{{ $row->jenis_transaksi }}</td>
										<td>{{ $row->kategori }}</td>
										<td>{{ $row->deskripsi }}</td>
										<td>Rp {{ number_format((float) $row->uang_masuk, 2, ',', '.') }}</td>
										<td>Rp {{ number_format((float) $row->uang_keluar, 2, ',', '.') }}</td>
										<td>Rp {{ number_format((float) $row->saldo, 2, ',', '.') }}</td>
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
			$('#arus-kas-table').DataTable({
				...window.dataTableGeneralConfig,
				processing: false,
				serverSide: false,
				order: [
					[1, 'desc'],
					[0, 'desc']
				],
			});
		});
	</script>
@endpush
