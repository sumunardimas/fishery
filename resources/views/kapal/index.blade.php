@extends('layouts.layout')

@section('title', 'Daftar Kapal')

@section('content')
	<div class="row" x-data="{ search: '' }">
		<div class="col-12">
			@if ($errors->has('message'))
				<x-alert type="danger" :message="$errors->first('message') ?? null" />
			@elseif (session('success'))
				<x-alert type="success" :message="session('success')" />
			@endif

			<div class="card mb-4">
				<div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
					<div>
						<h4 class="card-title mb-1">Daftar Kapal</h4>
						<p class="card-description mb-0">Kelola data kapal, mulai dari tambah, ubah, hingga hapus.</p>
					</div>
					<div class="d-flex gap-2 align-items-center w-100 w-md-auto">
						<input type="text" class="form-control" x-model="search" placeholder="Cari nama kapal...">
						<a href="{{ route('kapal.create') }}" class="btn btn-success btn-icon-text text-nowrap">
							<i class="ti-plus btn-icon-prepend"></i>
							Add New Kapal
						</a>
					</div>
				</div>
			</div>

			<div class="row">
				@forelse ($kapals as $kapal)
					<div class="col-xl-4 col-lg-6 col-md-6 mb-4"
						x-show="@js(strtolower($kapal->nama_kapal)).includes(search.toLowerCase())"
						x-transition>
						<div class="card h-100 shadow-sm border-0">
							<div class="card-body d-flex flex-column">
								<div class="d-flex justify-content-between align-items-start mb-3">
									<div>
										<h5 class="mb-1">{{ $kapal->nama_kapal }}</h5>
										<small class="text-muted">ID Kapal: #{{ $kapal->id_kapal }}</small>
									</div>
									<span class="badge badge-primary">{{ $kapal->tahun_dibangun }}</span>
								</div>

								<div class="mb-3">
									<div class="d-flex justify-content-between py-1 border-bottom">
										<span>Gross Tonnage</span>
										<strong>{{ number_format($kapal->gross_tonnage, 2) }}</strong>
									</div>
									<div class="d-flex justify-content-between py-1 border-bottom">
										<span>Deadweight Tonnage</span>
										<strong>{{ number_format($kapal->deadweight_tonnage, 2) }}</strong>
									</div>
									<div class="d-flex justify-content-between py-1 border-bottom">
										<span>Panjang (m)</span>
										<strong>{{ number_format($kapal->panjang_meter, 2) }}</strong>
									</div>
									<div class="d-flex justify-content-between py-1">
										<span>Lebar (m)</span>
										<strong>{{ number_format($kapal->lebar_meter, 2) }}</strong>
									</div>
								</div>

								<div class="d-flex gap-2 mt-auto">
									<a href="{{ route('kapal.edit', $kapal) }}" class="btn btn-outline-primary btn-sm w-100">
										<i class="ti-pencil-alt"></i> Edit
									</a>

									<form action="{{ route('kapal.destroy', $kapal) }}" method="POST" class="w-100"
										x-on:submit.prevent="if(confirm(@js('Hapus kapal '.$kapal->nama_kapal.'?'))) { $event.target.submit(); }">
										@csrf
										@method('DELETE')
										<button type="submit" class="btn btn-outline-danger btn-sm w-100">
											<i class="ti-trash"></i> Hapus
										</button>
									</form>
								</div>
							</div>
						</div>
					</div>
				@empty
					<div class="col-12">
						<div class="card">
							<div class="card-body text-center py-5">
								<h5 class="mb-2">Belum ada data kapal</h5>
								<p class="text-muted mb-3">Silakan tambahkan data kapal pertama Anda.</p>
								<a href="{{ route('kapal.create') }}" class="btn btn-success btn-icon-text">
									<i class="ti-plus btn-icon-prepend"></i>
									Add New Kapal
								</a>
							</div>
						</div>
					</div>
				@endforelse
			</div>
		</div>
	</div>
@endsection

