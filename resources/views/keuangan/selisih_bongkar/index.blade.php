@extends('layouts.layout')

@section('title', 'Laporan Selisih Bongkaran')

@section('content')
	<div class="row">
		<div class="col-md-4 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<p class="card-title mb-1">Total Konsumsi Lawuhan</p>
					<h3 class="mb-0">{{ number_format($totalLawuhan, 2, ',', '.') }} Kg</h3>
					<small class="text-muted">Dari stok lot pelayaran yang tercatat</small>
				</div>
			</div>
		</div>
	</div>

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
		<div class="col-md-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-3">Chart Selisih Timbangan - 30 Pelayaran Terakhir</h4>
					<div style="height: 400px;">
						<canvas id="selisihTimbangan-chart"></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-3">Selisih Bongkaran dan TPI</h4>
					<p class="card-description">Daftar pelayaran yang selesai dengan berat tangkapan dan input berat dari TPI.</p>

					<div class="table-responsive">
						<table id="selisih-bongkar-table" class="display expandable-table" style="width:100%">
							<thead>
								<tr>
									<th>Kapal</th>
									<th>Tanggal Berangkat</th>
									<th>Tanggal Selesai</th>
									<th>Berat Tangkapan (Kg)</th>
									<th>Berat TPI (Kg)</th>
									<th>Total Berat Penjualan (Kg)</th>
									<th>Lawuhan (Kg)</th>
									<th>Selisih (Kg)</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								@forelse ($rows as $row)
									<tr>
										<td><strong>{{ $row->nama_kapal }}</strong></td>
										<td>{{ \Carbon\Carbon::parse($row->tanggal_berangkat)->format('d/m/Y') }}</td>
										<td>{{ \Carbon\Carbon::parse($row->tanggal_selesai)->format('d/m/Y') }}</td>
										<td>{{ number_format((float) $row->berat_timbangan, 2, ',', '.') }}</td>
										<td>
											@if ($row->berat_catatan > 0)
												{{ number_format((float) $row->berat_catatan, 2, ',', '.') }}
											@else
												<span class="badge badge-warning">Belum diisi</span>
											@endif
										</td>
										<td>{{ number_format((float) $row->total_berat_penjualan, 2, ',', '.') }}</td>
										<td>{{ number_format((float) $row->total_lawuhan, 2, ',', '.') }}</td>
										<td>
											@if ($row->berat_catatan > 0)
												<span class="badge {{ (float)$row->selisih > 0 ? 'badge-danger' : 'badge-success' }}">
													{{ number_format((float) $row->selisih, 2, ',', '.') }}
												</span>
											@else
												<span class="text-muted">-</span>
											@endif
										</td>
										<td>
											<button type="button" class="btn btn-sm btn-primary" data-toggle="modal" 
												data-target="#modal-input-berat" 
												data-id-pelayaran="{{ $row->id_pelayaran }}"
												data-nama-kapal="{{ $row->nama_kapal }}"
												data-berat-timbangan="{{ $row->berat_timbangan }}"
												data-berat-catatan="{{ $row->berat_catatan }}">
												✏️ Isi Berat TPI
											</button>
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="9" class="text-center text-muted py-4">
											Tidak ada pelayaran yang selesai
										</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal for Input Berat -->
	<div class="modal fade" id="modal-input-berat" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="modalLabel">Input Berat dari TPI</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<form method="POST" action="{{ route('keuangan.lap-selisih-bongkaran.store') }}">
					@csrf
					<div class="modal-body">
						<div class="form-group">
							<label for="kapal-display">Kapal</label>
							<input type="text" class="form-control" id="kapal-display" disabled>
						</div>
						
						<div class="form-group">
							<label for="berat-timbangan-display">Berat Tangkapan (Kg)</label>
							<input type="text" class="form-control" id="berat-timbangan-display" disabled>
						</div>

						<input type="hidden" id="id-pelayaran" name="id_pelayaran">

						<div class="form-group">
							<label for="berat-catatan">Berat TPI (Kg) <span class="text-danger">*</span></label>
							<input type="number" step="0.01" min="0" class="form-control" id="berat-catatan" name="berat_catatan" required>
							<small class="form-text text-muted">Masukkan total berat dari TPI</small>
						</div>

						<div class="form-group">
							<label>Selisih akan dihitung otomatis</label>
							<div class="alert alert-info" id="selisih-display">
								Selisih: <strong id="selisih-value">0.00</strong> Kg
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
						<button type="submit" class="btn btn-primary">Simpan</button>
					</div>
				</form>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		$(document).ready(function() {
			// Initialize Chart
			const labels = @json($chartLabels);
			const selisihData = @json($chartSelisih);
			const beratTimbanganData = @json($chartBeratTimbangan);
			const beratCatatanData = @json($chartBeratCatatan);

			const ctx = document.getElementById('selisihTimbangan-chart');
			if (ctx) {
				new Chart(ctx, {
					type: 'line',
					data: {
						labels: labels,
						datasets: [
							{
								label: 'Berat Tangkapan (Kg)',
								data: beratTimbanganData,
								borderColor: 'rgba(54, 162, 235, 1)',
								backgroundColor: 'rgba(54, 162, 235, 0.1)',
								lineTension: 0.3,
								fill: true,
								pointRadius: 4,
								pointBackgroundColor: 'rgba(54, 162, 235, 1)',
								yAxisID: 'y-left',
							},
							{
								label: 'Berat TPI (Kg)',
								data: beratCatatanData,
								borderColor: 'rgba(75, 192, 75, 1)',
								backgroundColor: 'rgba(75, 192, 75, 0.1)',
								lineTension: 0.3,
								fill: true,
								pointRadius: 4,
								pointBackgroundColor: 'rgba(75, 192, 75, 1)',
								yAxisID: 'y-left',
							},
							{
								label: 'Selisih (Kg)',
								data: selisihData,
								borderColor: 'rgba(255, 99, 132, 1)',
								backgroundColor: 'rgba(255, 99, 132, 0.1)',
								lineTension: 0.3,
								fill: true,
								pointRadius: 4,
								pointBackgroundColor: 'rgba(255, 99, 132, 1)',
								yAxisID: 'y-right',
							}
						],
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						tooltips: {
							mode: 'index',
							intersect: false,
						},
						scales: {
							yAxes: [
								{
									id: 'y-left',
									type: 'linear',
									position: 'left',
									ticks: { beginAtZero: true },
									scaleLabel: {
										display: true,
										labelString: 'Berat (Kg)',
									},
								},
								{
									id: 'y-right',
									type: 'linear',
									position: 'right',
									ticks: { beginAtZero: true },
									scaleLabel: {
										display: true,
										labelString: 'Selisih (Kg)',
									},
									gridLines: { drawOnChartArea: false },
								}
							],
							xAxes: [{ ticks: { autoSkip: true, maxTicksLimit: 15 } }]
						},
						legend: {
							display: true,
							position: 'top',
						}
					}
				});
			}

			$('#selisih-bongkar-table').DataTable({
				...window.dataTableGeneralConfig,
				processing: false,
				serverSide: false,
				pageLength: 30,
				order: [
					[2, 'desc']  // Order by date selesai descending
				],
			});

			// Handle modal input
			$('#modal-input-berat').on('show.bs.modal', function(event) {
				const button = $(event.relatedTarget);
				const idPelayaran = button.data('id-pelayaran');
				const namaKapal = button.data('nama-kapal');
				const beratTimbangan = parseFloat(button.data('berat-timbangan'));
				const beratCatatan = parseFloat(button.data('berat-catatan'));

				$('#id-pelayaran').val(idPelayaran);
				$('#kapal-display').val(namaKapal);
				$('#berat-timbangan-display').val(beratTimbangan.toFixed(2));
				$('#berat-catatan').val(beratCatatan > 0 ? beratCatatan.toFixed(2) : '');
				
				// Calculate selisih
				if (beratCatatan > 0) {
					const selisih = beratTimbangan - beratCatatan;
					$('#selisih-value').text(selisih.toFixed(2));
				}
			});

			// Calculate selisih on input change
			$('#berat-catatan').on('input', function() {
				const beratTimbangan = parseFloat($('#berat-timbangan-display').val());
				const beratCatatan = parseFloat($(this).val()) || 0;
				const selisih = beratTimbangan - beratCatatan;
				$('#selisih-value').text(selisih.toFixed(2));
			});
		});
	</script>
@endpush
