@extends('layouts.layout')

@section('title', 'Master Ikan')

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
					<h4 class="card-title mb-1">Master Ikan</h4>
					<p class="card-description mb-4">Kelola jenis ikan yang digunakan dalam proses bongkaran dan penjualan.</p>

					<form action="{{ route('master.ikan.store') }}" method="POST">
						@csrf
						<div class="form-row">
							<div class="form-group col-md-4">
								<label for="nama_ikan">Nama Ikan</label>
								<input type="text" id="nama_ikan" name="nama_ikan" class="form-control"
									value="{{ old('nama_ikan') }}" required>
							</div>
							<div class="form-group col-md-3">
								<label for="jenis_ikan">Jenis Ikan</label>
								<input type="text" id="jenis_ikan" name="jenis_ikan" class="form-control"
									value="{{ old('jenis_ikan') }}" required>
							</div>
							<div class="form-group col-md-3">
								<label for="harga_default">Harga Default</label>
								<input type="number" step="0.01" min="0" id="harga_default" name="harga_default"
									class="form-control" value="{{ old('harga_default') }}" required>
							</div>
							<div class="form-group col-md-2 d-flex align-items-end">
								<button type="submit" class="btn btn-success w-100">Tambah</button>
							</div>
						</div>
						<div class="form-group mb-0">
							<label for="keterangan">Keterangan</label>
							<textarea id="keterangan" name="keterangan" class="form-control" rows="2" required>{{ old('keterangan') }}</textarea>
						</div>
					</form>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Daftar Ikan</h5>
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>ID</th>
									<th>Nama Ikan</th>
									<th>Jenis Ikan</th>
									<th>Harga Default</th>
									<th>Keterangan</th>
									<th class="text-right">Aksi</th>
								</tr>
							</thead>
							<tbody>
								@forelse ($items as $item)
									<tr>
										<td>#{{ $item->id_ikan }}</td>
										<td>{{ $item->nama_ikan }}</td>
										<td>{{ $item->jenis_ikan }}</td>
										<td>Rp {{ number_format((float) $item->harga_default, 2, ',', '.') }}</td>
										<td>{{ $item->keterangan }}</td>
										<td class="text-right">
											<button type="button" class="btn btn-outline-primary btn-sm"
												data-toggle="modal"
												data-target="#editIkan{{ $item->id_ikan }}">Edit</button>
											<form action="{{ route('master.ikan.destroy', $item) }}" method="POST"
												class="d-inline"
												onsubmit="return confirm('Hapus data ikan {{ addslashes($item->nama_ikan) }}?')">
												@csrf
												@method('DELETE')
												<button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
											</form>
										</td>
									</tr>

									<div class="modal fade" id="editIkan{{ $item->id_ikan }}" tabindex="-1"
										role="dialog" aria-hidden="true">
										<div class="modal-dialog modal-lg" role="document">
											<div class="modal-content">
												<div class="modal-header">
													<h5 class="modal-title">Edit Master Ikan</h5>
													<button type="button" class="close" data-dismiss="modal"
														aria-label="Close">
														<span aria-hidden="true">&times;</span>
													</button>
												</div>
												<form action="{{ route('master.ikan.update', $item) }}" method="POST">
													@csrf
													@method('PUT')
													<div class="modal-body">
														<div class="form-row">
															<div class="form-group col-md-5">
																<label>Nama Ikan</label>
																<input type="text" name="nama_ikan"
																	class="form-control" value="{{ $item->nama_ikan }}"
																	required>
															</div>
															<div class="form-group col-md-4">
																<label>Jenis Ikan</label>
																<input type="text" name="jenis_ikan"
																	class="form-control" value="{{ $item->jenis_ikan }}"
																	required>
															</div>
															<div class="form-group col-md-3">
																<label>Harga Default</label>
																<input type="number" name="harga_default"
																	class="form-control" step="0.01" min="0"
																	value="{{ $item->harga_default }}" required>
															</div>
														</div>
														<div class="form-group mb-0">
															<label>Keterangan</label>
															<textarea name="keterangan" class="form-control" rows="2" required>{{ $item->keterangan }}</textarea>
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
										<td colspan="6" class="text-center text-muted">Belum ada data ikan.</td>
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
