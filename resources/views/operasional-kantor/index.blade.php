@extends('layouts.layout')

@section('title', 'Master Operasional')

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
					<h4 class="card-title mb-1">Master Operasional</h4>
					<p class="card-description mb-4">Kelola daftar jenis biaya operasional pelayaran.</p>

					<form action="{{ route('master.operasional.store') }}" method="POST">
						@csrf
						<div class="form-row">
							<div class="form-group col-md-5">
								<label for="nama_operasional">Nama Operasional</label>
								<input type="text" id="nama_operasional" name="nama_operasional" class="form-control"
									value="{{ old('nama_operasional') }}" required>
							</div>
							<div class="form-group col-md-6">
								<label for="deskripsi">Deskripsi</label>
								<input type="text" id="deskripsi" name="deskripsi" class="form-control"
									value="{{ old('deskripsi') }}">
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
					<h5 class="card-title">Daftar Operasional</h5>
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>ID</th>
									<th>Nama Operasional</th>
									<th>Deskripsi</th>
									<th class="text-right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								@forelse ($items as $item)
									<tr>
										<td>#{{ $item->id_master_operasional }}</td>
										<td>{{ $item->nama_operasional }}</td>
										<td>{{ $item->deskripsi ?: '-' }}</td>
										<td class="text-right">
											<button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal"
												data-target="#editOperasional{{ $item->id_master_operasional }}">Edit</button>
											<form action="{{ route('master.operasional.destroy', $item) }}" method="POST"
												class="d-inline"
												onsubmit="return confirm('Hapus master operasional {{ addslashes($item->nama_operasional) }}?')">
												@csrf
												@method('DELETE')
												<button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
											</form>
										</td>
									</tr>

									<div class="modal fade" id="editOperasional{{ $item->id_master_operasional }}"
										tabindex="-1" role="dialog" aria-hidden="true">
										<div class="modal-dialog" role="document">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title">Edit Master Operasional</h5>
													<button type="button" class="close" data-dismiss="modal"
														aria-label="Close">
														<span aria-hidden="true">&times;</span>
													</button>
												</div>
												<form action="{{ route('master.operasional.update', $item) }}" method="POST">
													@csrf
													@method('PUT')
													<div class="modal-body">
														<div class="form-group">
															<label>Nama Operasional</label>
															<input type="text" name="nama_operasional" class="form-control"
																value="{{ $item->nama_operasional }}" required>
														</div>
														<div class="form-group mb-0">
															<label>Deskripsi</label>
															<textarea name="deskripsi" class="form-control" rows="2">{{ $item->deskripsi }}</textarea>
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
										<td colspan="4" class="text-center text-muted">Belum ada data master operasional.</td>
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
