@extends('layouts.layout')

@section('title', 'Master Customer')

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
					<h4 class="card-title mb-1">Master Customer</h4>
					<p class="card-description mb-4">Kelola data customer untuk transaksi penjualan ikan.</p>

					<form action="{{ route('master.customer.store') }}" method="POST">
						@csrf
						<div class="form-row">
							<div class="form-group col-md-4">
								<label for="nama_customer">Nama Customer</label>
								<input type="text" id="nama_customer" name="nama_customer" class="form-control"
									value="{{ old('nama_customer') }}" required>
							</div>
							<div class="form-group col-md-5">
								<label for="alamat">Alamat</label>
								<input type="text" id="alamat" name="alamat" class="form-control"
									value="{{ old('alamat') }}">
							</div>
							<div class="form-group col-md-2">
								<label for="telepon">Telepon</label>
								<input type="text" id="telepon" name="telepon" class="form-control"
									value="{{ old('telepon') }}">
							</div>
							<div class="form-group col-md-1 d-flex align-items-end">
								<button type="submit" class="btn btn-success w-100">Tambah</button>
							</div>
						</div>
					</form>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Daftar Customer</h5>
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>ID</th>
									<th>Nama Customer</th>
									<th>Alamat</th>
									<th>Telepon</th>
									<th class="text-right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								@forelse ($items as $item)
									<tr>
										<td>#{{ $item->id_customer }}</td>
										<td>{{ $item->nama_customer }}</td>
										<td>{{ $item->alamat ?: '-' }}</td>
										<td>{{ $item->telepon ?: '-' }}</td>
										<td class="text-right">
											<button type="button" class="btn btn-outline-primary btn-sm"
												data-toggle="modal"
												data-target="#editCustomer{{ $item->id_customer }}">Edit</button>
											<form action="{{ route('master.customer.destroy', $item) }}" method="POST"
												class="d-inline"
												onsubmit="return confirm('Hapus customer {{ addslashes($item->nama_customer) }}?')">
												@csrf
												@method('DELETE')
												<button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
											</form>
										</td>
									</tr>

									<div class="modal fade" id="editCustomer{{ $item->id_customer }}" tabindex="-1"
										role="dialog" aria-hidden="true">
										<div class="modal-dialog" role="document">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title">Edit Master Customer</h5>
													<button type="button" class="close" data-dismiss="modal"
														aria-label="Close">
														<span aria-hidden="true">&times;</span>
													</button>
												</div>
												<form action="{{ route('master.customer.update', $item) }}"
													method="POST">
													@csrf
													@method('PUT')
													<div class="modal-body">
														<div class="form-group">
															<label>Nama Customer</label>
															<input type="text" name="nama_customer"
																class="form-control"
																value="{{ $item->nama_customer }}" required>
														</div>
														<div class="form-group">
															<label>Alamat</label>
															<input type="text" name="alamat" class="form-control"
																value="{{ $item->alamat }}">
														</div>
														<div class="form-group mb-0">
															<label>Telepon</label>
															<input type="text" name="telepon" class="form-control"
																value="{{ $item->telepon }}">
														</div>
													</div>
													<div class="modal-footer">
														<button type="button" class="btn btn-light"
															data-dismiss="modal">Batal</button>
														<button type="submit" class="btn btn-primary">Simpan
															Perubahan</button>
													</div>
												</form>
											</div>
										</div>
									</div>
								@empty
									<tr>
										<td colspan="5" class="text-center text-muted">Belum ada data customer.</td>
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
