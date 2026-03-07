@extends('layouts.layout')

@section('title', 'Master Item Pembelian')

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
					<h4 class="card-title mb-1">Master Item Pembelian</h4>
					<p class="card-description mb-4">Kelola data item untuk transaksi pembelian barang.</p>

					<form action="{{ route('master.item-pembelian.store') }}" method="POST">
						@csrf
						<div class="form-row">
							<div class="form-group col-md-4">
								<label for="nama_item">Nama Item</label>
								<input type="text" id="nama_item" name="nama_item" class="form-control"
									value="{{ old('nama_item') }}" required>
							</div>
							<div class="form-group col-md-3">
								<label for="kategori">Kategori</label>
								<input type="text" id="kategori" name="kategori" class="form-control"
									value="{{ old('kategori') }}" required>
							</div>
							<div class="form-group col-md-2">
								<label for="satuan">Satuan</label>
								<input type="text" id="satuan" name="satuan" class="form-control"
									value="{{ old('satuan') }}" required>
							</div>
							<div class="form-group col-md-3 d-flex align-items-end">
								<button type="submit" class="btn btn-success w-100">Tambah</button>
							</div>
						</div>
						<div class="form-group mb-0">
							<label for="keterangan">Keterangan</label>
							<textarea id="keterangan" name="keterangan" class="form-control" rows="2">{{ old('keterangan') }}</textarea>
						</div>
					</form>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Daftar Item Pembelian</h5>
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>ID</th>
									<th>Nama Item</th>
									<th>Kategori</th>
									<th>Satuan</th>
									<th>Keterangan</th>
									<th class="text-right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								@forelse ($items as $item)
									<tr>
										<td>#{{ $item->id_item_pembelian }}</td>
										<td>{{ $item->nama_item }}</td>
										<td>{{ $item->kategori }}</td>
										<td>{{ $item->satuan }}</td>
										<td>{{ $item->keterangan ?: '-' }}</td>
										<td class="text-right">
											<button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal"
												data-target="#editItemPembelian{{ $item->id_item_pembelian }}">Edit</button>
											<form action="{{ route('master.item-pembelian.destroy', $item) }}" method="POST"
												class="d-inline"
												onsubmit="return confirm('Hapus item pembelian {{ addslashes($item->nama_item) }}?')">
												@csrf
												@method('DELETE')
												<button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
											</form>
										</td>
									</tr>

									<div class="modal fade" id="editItemPembelian{{ $item->id_item_pembelian }}" tabindex="-1"
										role="dialog" aria-hidden="true">
										<div class="modal-dialog modal-lg" role="document">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title">Edit Master Item Pembelian</h5>
													<button type="button" class="close" data-dismiss="modal" aria-label="Close">
														<span aria-hidden="true">&times;</span>
													</button>
												</div>
												<form action="{{ route('master.item-pembelian.update', $item) }}" method="POST">
													@csrf
													@method('PUT')
													<div class="modal-body">
														<div class="form-row">
															<div class="form-group col-md-5">
																<label>Nama Item</label>
																<input type="text" name="nama_item" class="form-control"
																	value="{{ $item->nama_item }}" required>
															</div>
															<div class="form-group col-md-4">
																<label>Kategori</label>
																<input type="text" name="kategori" class="form-control"
																	value="{{ $item->kategori }}" required>
															</div>
															<div class="form-group col-md-3">
																<label>Satuan</label>
																<input type="text" name="satuan" class="form-control"
																	value="{{ $item->satuan }}" required>
															</div>
														</div>
														<div class="form-group mb-0">
															<label>Keterangan</label>
															<textarea name="keterangan" class="form-control" rows="2">{{ $item->keterangan }}</textarea>
														</div>
													</div>
													<div class="modal-footer">
														<button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
														<button type="submit" class="btn btn-primary">Simpan Perubahan</button>
													</div>
												</form>
											</div>
										</div>
									</div>
								@empty
									<tr>
										<td colspan="6" class="text-center text-muted">Belum ada data item pembelian.</td>
									</tr>
								@endforelse
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection
