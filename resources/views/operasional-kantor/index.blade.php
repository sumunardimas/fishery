@extends('layouts.layout')

@section('title', 'Operasional Kantor')

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

			<div class="card mb-4">
				<div class="card-body">
					<h4 class="card-title mb-1">Form Operasional Kantor</h4>
					<p class="card-description mb-4">
						Isi item biaya operasional kantor. Total per baris dan grand total dihitung otomatis.
					</p>

					<form action="{{ route('operasional-kantor.store') }}" method="POST" id="operasional-kantor-form">
						@csrf

						<div class="form-group">
							<label for="tanggal">Tanggal</label>
							<input
								type="date"
								class="form-control"
								id="tanggal"
								name="tanggal"
								value="{{ old('tanggal', now()->toDateString()) }}"
								required>
						</div>

						@php
							$oldRows = old('rows', [[
								'id_master_operasional_kantor' => '',
								'harga_satuan' => '',
								'qty' => '',
								'keterangan' => '',
							]]);
						@endphp

						<div class="table-responsive mt-4">
							<table class="table table-bordered" id="operasional-kantor-table">
								<thead>
									<tr>
										<th style="min-width: 240px;">Item</th>
										<th style="min-width: 160px;">Kategori</th>
										<th style="min-width: 170px;">Harga Satuan</th>
										<th style="min-width: 120px;">Qty</th>
										<th style="min-width: 170px;">Total</th>
										<th style="min-width: 220px;">Keterangan</th>
										<th style="width: 90px;">Aksi</th>
									</tr>
								</thead>
								<tbody id="operasional-kantor-rows">
									@foreach ($oldRows as $index => $row)
										@php
											$selectedMaster = $masterItems->firstWhere('id_master_operasional_kantor', (int) ($row['id_master_operasional_kantor'] ?? 0));
											$kategori = $selectedMaster->kategori ?? '';
											$hargaSatuan = (float) ($row['harga_satuan'] ?? 0);
											$qty = (float) ($row['qty'] ?? 0);
											$total = $hargaSatuan * $qty;
										@endphp
										<tr class="operasional-row">
											<td>
												<select name="rows[{{ $index }}][id_master_operasional_kantor]" class="form-control js-item" required>
													<option value="">Pilih item</option>
													@foreach ($masterItems as $master)
														<option
															value="{{ $master->id_master_operasional_kantor }}"
															data-category="{{ $master->kategori }}"
															@selected((int) ($row['id_master_operasional_kantor'] ?? 0) === (int) $master->id_master_operasional_kantor)>
															{{ $master->item }}
														</option>
													@endforeach
												</select>
											</td>
											<td>
												<input type="text" class="form-control js-category" value="{{ $kategori }}" readonly>
											</td>
											<td>
												<input
													type="number"
													step="0.01"
													min="0"
													name="rows[{{ $index }}][harga_satuan]"
													class="form-control js-unit-cost"
													value="{{ $row['harga_satuan'] ?? '' }}"
													required>
											</td>
											<td>
												<input
													type="number"
													step="0.01"
													min="0.01"
													name="rows[{{ $index }}][qty]"
													class="form-control js-qty"
													value="{{ $row['qty'] ?? '' }}"
													required>
											</td>
											<td>
												<input type="text" class="form-control js-line-total" value="Rp {{ number_format($total, 2, ',', '.') }}" readonly>
											</td>
											<td>
												<input type="text" name="rows[{{ $index }}][keterangan]" class="form-control" value="{{ $row['keterangan'] ?? '' }}" placeholder="Opsional">
											</td>
											<td>
												<button type="button" class="btn btn-outline-danger btn-sm js-remove-row">Hapus</button>
											</td>
										</tr>
									@endforeach
								</tbody>
								<tfoot>
									<tr>
										<th colspan="4" class="text-right">Grand Total</th>
										<th>
											<input type="text" class="form-control" id="grand-total" value="Rp 0,00" readonly>
										</th>
										<th colspan="2"></th>
									</tr>
								</tfoot>
							</table>
						</div>

						<div class="d-flex align-items-center mt-3">
							<button type="button" class="btn btn-outline-primary mr-2" id="add-row">Tambah Baris</button>
							<button type="submit" class="btn btn-primary">Simpan</button>
						</div>
					</form>
				</div>
			</div>

			<div class="card mb-4">
				<div class="card-body">
					<h4 class="card-title mb-1">Ringkasan Harian Operasional Kantor</h4>
					<p class="card-description mb-4">Filter periode tanggal. Default menampilkan 5 hari terakhir.</p>

					<form method="GET" action="{{ route('operasional-kantor.index') }}" class="mb-4">
						<div class="row">
							<div class="col-md-3">
								<div class="form-group">
									<label for="start_date">Start Date</label>
									<input type="date" id="start_date" name="start_date" class="form-control" value="{{ $startDate }}">
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label for="end_date">End Date</label>
									<input type="date" id="end_date" name="end_date" class="form-control" value="{{ $endDate }}">
								</div>
							</div>
							<div class="col-md-6 d-flex align-items-end mb-3">
								<button type="submit" class="btn btn-primary mr-2">Terapkan</button>
								<a href="{{ route('operasional-kantor.index') }}" class="btn btn-light">Reset</a>
							</div>
						</div>
					</form>

					<p class="mb-3"><strong>Grand Total Periode:</strong> Rp {{ number_format($summaryGrandTotal, 2, ',', '.') }}</p>

					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>Tanggal</th>
									<th>Total Item</th>
									<th>Total Biaya</th>
									<th class="text-right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								@forelse ($dailySummary as $row)
									<tr>
										<td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d-m-Y') }}</td>
										<td>{{ $row->total_item }}</td>
										<td>Rp {{ number_format((float) $row->grand_total, 2, ',', '.') }}</td>
										<td class="text-right">
											<a
												href="{{ route('operasional-kantor.index', ['start_date' => $startDate, 'end_date' => $endDate, 'detail_date' => $row->tanggal]) }}"
												class="btn btn-outline-info btn-sm">
												Lihat Detail
											</a>
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="4" class="text-center text-muted">Belum ada data operasional kantor pada periode ini.</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<h4 class="card-title mb-1">Detail Operasional Kantor</h4>
					@if ($detailDate)
						<p class="card-description mb-4">
							Detail tanggal {{ \Carbon\Carbon::parse($detailDate)->format('d-m-Y') }}.
							Grand total: <strong>Rp {{ number_format($detailGrandTotal, 2, ',', '.') }}</strong>
						</p>

						<div class="table-responsive">
							<table class="table table-bordered">
								<thead>
									<tr>
										<th>Kategori</th>
										<th>Item</th>
										<th>Harga Satuan</th>
										<th>Qty</th>
										<th>Total</th>
										<th>Keterangan</th>
									</tr>
								</thead>
								<tbody>
									@forelse ($detailRows as $detail)
										<tr>
											<td>{{ $detail->kategori ?: $detail->jenis_biaya }}</td>
											<td>{{ $detail->item ?: $detail->deskripsi }}</td>
											<td>Rp {{ number_format((float) ($detail->harga_satuan ?? 0), 2, ',', '.') }}</td>
											<td>{{ number_format((float) ($detail->qty ?? 0), 2, ',', '.') }}</td>
											<td>Rp {{ number_format((float) ($detail->total_biaya ?? $detail->jumlah ?? 0), 2, ',', '.') }}</td>
											<td>{{ $detail->keterangan ?: '-' }}</td>
										</tr>
									@empty
										<tr>
											<td colspan="6" class="text-center text-muted">Tidak ada detail pada tanggal ini.</td>
										</tr>
									@endforelse
								</tbody>
							</table>
						</div>
					@else
						<p class="card-description mb-0">
							Klik tombol <strong>Lihat Detail</strong> pada ringkasan harian untuk menampilkan daftar detail.
						</p>
					@endif
				</div>
			</div>
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		(function() {
			const rowsContainer = document.getElementById('operasional-kantor-rows');
			const addRowButton = document.getElementById('add-row');
			const grandTotalInput = document.getElementById('grand-total');

			if (!rowsContainer || !addRowButton || !grandTotalInput) {
				return;
			}

			let rowIndex = rowsContainer.querySelectorAll('tr').length;

			const itemOptions = `
				<option value="">Pilih item</option>
				@foreach ($masterItems as $master)
					<option value="{{ $master->id_master_operasional_kantor }}" data-category="{{ $master->kategori }}">{{ $master->item }}</option>
				@endforeach
			`;

			const formatCurrency = (value) => {
				const amount = Number(value || 0);
				return 'Rp ' + amount.toLocaleString('id-ID', {
					minimumFractionDigits: 2,
					maximumFractionDigits: 2,
				});
			};

			const recalculateRow = (row) => {
				const unitCost = Number(row.querySelector('.js-unit-cost')?.value || 0);
				const qty = Number(row.querySelector('.js-qty')?.value || 0);
				const total = unitCost * qty;
				const totalInput = row.querySelector('.js-line-total');

				if (totalInput) {
					totalInput.value = formatCurrency(total);
				}
			};

			const recalculateGrandTotal = () => {
				let grandTotal = 0;

				rowsContainer.querySelectorAll('tr').forEach((row) => {
					const unitCost = Number(row.querySelector('.js-unit-cost')?.value || 0);
					const qty = Number(row.querySelector('.js-qty')?.value || 0);
					grandTotal += unitCost * qty;
				});

				grandTotalInput.value = formatCurrency(grandTotal);
			};

			const refreshRemoveButtons = () => {
				const rows = rowsContainer.querySelectorAll('tr');
				rows.forEach((row) => {
					const button = row.querySelector('.js-remove-row');
					if (button) {
						button.disabled = rows.length === 1;
					}
				});
			};

			const updateCategoryFromSelect = (selectEl) => {
				const row = selectEl.closest('tr');
				if (!row) {
					return;
				}

				const selectedOption = selectEl.options[selectEl.selectedIndex];
				const category = selectedOption?.dataset?.category || '';
				const categoryInput = row.querySelector('.js-category');
				if (categoryInput) {
					categoryInput.value = category;
				}
			};

			const buildRow = (index) => {
				const tr = document.createElement('tr');
				tr.className = 'operasional-row';
				tr.innerHTML = `
					<td>
						<select name="rows[${index}][id_master_operasional_kantor]" class="form-control js-item" required>
							${itemOptions}
						</select>
					</td>
					<td>
						<input type="text" class="form-control js-category" value="" readonly>
					</td>
					<td>
						<input type="number" step="0.01" min="0" name="rows[${index}][harga_satuan]" class="form-control js-unit-cost" value="" required>
					</td>
					<td>
						<input type="number" step="0.01" min="0.01" name="rows[${index}][qty]" class="form-control js-qty" value="" required>
					</td>
					<td>
						<input type="text" class="form-control js-line-total" value="${formatCurrency(0)}" readonly>
					</td>
					<td>
						<input type="text" name="rows[${index}][keterangan]" class="form-control" value="" placeholder="Opsional">
					</td>
					<td>
						<button type="button" class="btn btn-outline-danger btn-sm js-remove-row">Hapus</button>
					</td>
				`;

				return tr;
			};

			addRowButton.addEventListener('click', () => {
				rowsContainer.appendChild(buildRow(rowIndex));
				rowIndex += 1;
				refreshRemoveButtons();
				recalculateGrandTotal();
			});

			rowsContainer.addEventListener('input', (event) => {
				const target = event.target;
				if (!target.closest('tr')) {
					return;
				}

				if (target.classList.contains('js-unit-cost') || target.classList.contains('js-qty')) {
					const row = target.closest('tr');
					recalculateRow(row);
					recalculateGrandTotal();
				}
			});

			rowsContainer.addEventListener('change', (event) => {
				const target = event.target;
				if (target.classList.contains('js-item')) {
					updateCategoryFromSelect(target);
				}
			});

			rowsContainer.addEventListener('click', (event) => {
				const target = event.target;
				if (!target.classList.contains('js-remove-row')) {
					return;
				}

				const row = target.closest('tr');
				if (!row) {
					return;
				}

				if (rowsContainer.querySelectorAll('tr').length > 1) {
					row.remove();
					refreshRemoveButtons();
					recalculateGrandTotal();
				}
			});

			rowsContainer.querySelectorAll('.js-item').forEach((select) => updateCategoryFromSelect(select));
			rowsContainer.querySelectorAll('tr').forEach((row) => recalculateRow(row));
			refreshRemoveButtons();
			recalculateGrandTotal();
		})();
	</script>
@endpush
