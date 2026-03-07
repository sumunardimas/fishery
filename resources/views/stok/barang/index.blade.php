@extends('layouts.layout')

@section('title', 'Stok Barang')

@section('content')
	<div class="row">
		<div class="col-md-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
					<p class="card-title">Stok Barang Aktual</p>

					<div class="table-responsive">
						<table id="stok-barang-table" class="display expandable-table" style="width:100%">
							<thead>
								<tr>
									<th>ID</th>
									<th>Nama Item</th>
									<th>Kategori</th>
									<th>Satuan</th>
									<th>Stok Aktual</th>
								</tr>
							</thead>
							<tbody>
								@foreach ($items as $item)
									<tr>
										<td>{{ $item->id_item_pembelian }}</td>
										<td>{{ $item->nama_item }}</td>
										<td>{{ $item->kategori }}</td>
										<td>{{ $item->satuan }}</td>
										<td>{{ number_format((float) $item->stok_aktual, 2, ',', '.') }}</td>
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
			$('#stok-barang-table').DataTable({
				...window.dataTableGeneralConfig,
				processing: false,
				serverSide: false,
			});
		});
	</script>
@endpush
