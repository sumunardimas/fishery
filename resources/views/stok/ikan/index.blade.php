@extends('layouts.layout')

@section('title', 'Stok Ikan')

@section('content')
	<div class="row">
		<div class="col-md-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<p class="card-title">Stok Ikan Aktual</p>

					<div class="table-responsive">
						<table id="stok-ikan-table" class="display expandable-table" style="width:100%">
							<thead>
								<tr>
									<th>ID</th>
									<th>Nama Ikan</th>
									<th>Jenis Ikan</th>
									<th>Harga Default</th>
									<th>Stok Aktual (Kg)</th>
									<th>Periode Terakhir</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($items as $item)
									<tr>
										<td>{{ $item->id_ikan }}</td>
										<td>{{ $item->nama_ikan }}</td>
										<td>{{ $item->jenis_ikan }}</td>
										<td>{{ number_format((float) $item->harga_default, 2, ',', '.') }}</td>
										<td>{{ number_format((float) $item->stok_aktual, 2, ',', '.') }}</td>
										<td>{{ $item->periode_terakhir }}</td>
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
			$('#stok-ikan-table').DataTable({
				...window.dataTableGeneralConfig,
				processing: false,
				serverSide: false,
			});
		});
	</script>
@endpush
