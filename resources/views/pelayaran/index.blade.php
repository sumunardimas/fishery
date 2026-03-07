@extends('layouts.layout')

@section('title', 'Pelayaran')

@section('content')
	<div class="row" x-data="{ selectedKapal: 'all', search: '' }">
		<div class="col-12">
			@if ($errors->has('message'))
				<x-alert type="danger" :message="$errors->first('message') ?? null" />
			@elseif (session('success'))
				<x-alert type="success" :message="session('success')" />
			@endif

			<div class="card mb-4">
				<div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
					<div>
						<h4 class="card-title mb-1">Pelayaran Aktif</h4>
						<p class="card-description mb-0">Monitoring kapal yang sedang berlayar hari ini.</p>
					</div>

					<div class="d-flex flex-column flex-md-row gap-2 w-100 w-md-auto">
						<select class="form-control" x-model="selectedKapal">
							<option value="all">Semua Kapal</option>
							@foreach ($kapals as $kapal)
								<option value="{{ $kapal->id_kapal }}">{{ $kapal->nama_kapal }}</option>
							@endforeach
						</select>
						<input type="text" class="form-control" x-model="search"
							placeholder="Cari pelabuhan asal / tujuan...">
						<a href="{{ route('pelayaran.create') }}" class="btn btn-success btn-icon-text text-nowrap">
							<i class="ti-plus btn-icon-prepend"></i>
							Add New Pelayaran
						</a>
					</div>
				</div>
			</div>

			<div class="row">
				@forelse ($activePelayaran as $item)
					<div class="col-xl-4 col-lg-6 mb-4"
						x-show="(selectedKapal === 'all' || selectedKapal === @js((string) $item->id_kapal))
						&& (@js(strtolower($item->pelabuhan_asal . ' ' . $item->pelabuhan_tujuan)).includes(search.toLowerCase()))"
						x-transition>
						<div class="card border-0 shadow-sm h-100">
							<div class="card-body d-flex flex-column">
								<div class="d-flex justify-content-between align-items-start mb-3">
									<div>
										<h5 class="mb-1">{{ $item->kapal->nama_kapal ?? '-' }}</h5>
										<small class="text-muted">ID Pelayaran: #{{ $item->id_pelayaran }}</small>
									</div>
									<span class="badge badge-success">Aktif</span>
								</div>

								<div class="mb-3">
									<div class="d-flex justify-content-between py-1 border-bottom">
										<span>Berangkat</span>
										<strong>{{ $item->tanggal_berangkat?->format('d M Y') }}</strong>
									</div>
									<div class="d-flex justify-content-between py-1 border-bottom">
										<span>Tiba</span>
										<strong>{{ $item->tanggal_tiba?->format('d M Y') }}</strong>
									</div>
									<div class="d-flex justify-content-between py-1 border-bottom">
										<span>Pelabuhan Asal</span>
										<strong>{{ $item->pelabuhan_asal }}</strong>
									</div>
									<div class="d-flex justify-content-between py-1 border-bottom">
										<span>Pelabuhan Tujuan</span>
										<strong>{{ $item->pelabuhan_tujuan }}</strong>
									</div>
									<div class="d-flex justify-content-between py-1">
										<span>Trip ke-</span>
										<strong>{{ $item->jumlah_trip }}</strong>
									</div>
								</div>

								<p class="text-muted small mb-3">{{ $item->keterangan }}</p>

								<div class="d-flex gap-2 mt-auto">
									<a href="{{ route('pelayaran.sisa.index', ['pelayaran_id' => $item->id_pelayaran]) }}"
										class="btn btn-outline-success btn-sm w-100">
										<i class="ti-check-box"></i> Selesaikan Trip
									</a>
									<a href="{{ route('pelayaran.edit', $item) }}" class="btn btn-outline-primary btn-sm w-100">
										<i class="ti-pencil-alt"></i> Edit
									</a>

									<form action="{{ route('pelayaran.destroy', $item) }}" method="POST" class="w-100"
										x-on:submit.prevent="if(confirm(@js('Hapus data pelayaran #'.$item->id_pelayaran.'?'))) { $event.target.submit(); }">
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
					<div class="col-12 mb-4">
						<div class="card">
							<div class="card-body text-center py-5">
								<h5 class="mb-2">Tidak ada pelayaran aktif saat ini</h5>
								<p class="text-muted mb-3">Buat rencana pelayaran baru untuk memulai operasional.</p>
								<a href="{{ route('pelayaran.create') }}" class="btn btn-success btn-icon-text">
									<i class="ti-plus btn-icon-prepend"></i>
									Add New Pelayaran
								</a>
							</div>
						</div>
					</div>
				@endforelse
			</div>

			<div class="card mb-4">
				<div class="card-body">
					<h4 class="card-title mb-1">Pelayaran Nonaktif / Selesai</h4>
					<p class="card-description mb-3">Trip yang sudah ditutup melalui form Sisa Trip.</p>

					<div class="row">
						@forelse ($inactivePelayaran as $item)
							<div class="col-xl-4 col-lg-6 mb-3">
								<div class="card border h-100">
									<div class="card-body">
										<div class="d-flex justify-content-between align-items-start mb-2">
											<h5 class="mb-0">{{ $item->kapal->nama_kapal ?? '-' }}</h5>
											<span class="badge badge-secondary">Selesai</span>
										</div>
										<p class="text-muted mb-1">ID: #{{ $item->id_pelayaran }}</p>
										<p class="mb-1">{{ $item->pelabuhan_asal }} -> {{ $item->pelabuhan_tujuan }}</p>
										<p class="mb-1">Berangkat: {{ $item->tanggal_berangkat?->format('d M Y') }}</p>
										<p class="mb-0">Selesai: {{ $item->tanggal_selesai?->format('d M Y') ?? '-' }}</p>
									</div>
								</div>
							</div>
						@empty
							<div class="col-12">
								<p class="text-muted mb-0">Belum ada pelayaran nonaktif.</p>
							</div>
						@endforelse
					</div>
				</div>
			</div>

			<div class="card">
				<div class="card-body">
					<h5 class="card-title">Semua Rencana Pelayaran</h5>
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>ID</th>
									<th>Kapal</th>
									<th>Berangkat</th>
									<th>Tiba</th>
									<th>Asal</th>
									<th>Tujuan</th>
									<th>Trip</th>
									<th>Aksi</th>
								</tr>
							</thead>
							<tbody>
								@forelse ($pelayaran as $item)
									<tr>
										<td>#{{ $item->id_pelayaran }}</td>
										<td>{{ $item->kapal->nama_kapal ?? '-' }}</td>
										<td>{{ $item->tanggal_berangkat?->format('d-m-Y') }}</td>
										<td>{{ $item->tanggal_tiba?->format('d-m-Y') }}</td>
										<td>{{ $item->pelabuhan_asal }}</td>
										<td>{{ $item->pelabuhan_tujuan }}</td>
										<td>{{ $item->jumlah_trip }}</td>
										<td class="d-flex gap-2">
											<a href="{{ route('pelayaran.edit', $item) }}"
												class="btn btn-outline-primary btn-sm">Edit</a>
											<form action="{{ route('pelayaran.destroy', $item) }}" method="POST"
												x-on:submit.prevent="if(confirm(@js('Hapus data pelayaran #'.$item->id_pelayaran.'?'))) { $event.target.submit(); }">
												@csrf
												@method('DELETE')
												<button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
											</form>
										</td>
									</tr>
								@empty
									<tr>
										<td colspan="8" class="text-center text-muted">Belum ada data pelayaran.</td>
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

