@extends('layouts.layout')

@section('title', 'Sisa Trip Pelayaran')

@section('content')
    <style>
        .trip-tabs {
            --tab-count: 1;
            width: 100%;
            display: flex;
            flex-wrap: nowrap;
            border-bottom: 1px solid #dee2e6;
        }

        .trip-tabs .nav-item {
            flex: 0 0 calc(100% / var(--tab-count));
            max-width: calc(100% / var(--tab-count));
        }

        .trip-tabs .nav-link {
            width: 100%;
            text-align: center;
            border: 0;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            color: #6c757d;
            font-weight: 600;
            transition: color 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
        }

        .trip-tabs .nav-link:hover {
            color: #0d6efd;
            border-bottom-color: #9ec5fe;
            background-color: #f8f9fa;
        }

        .trip-tabs .nav-link.active {
            color: #b54708;
            border-bottom-color: #f79009;
            background-color: #fff4e5;
        }

        @media (max-width: 767.98px) {
            .trip-tabs {
                flex-wrap: wrap;
            }

            .trip-tabs .nav-item {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
    </style>

    @php
        $tabPerbekalan = 'perbekalan';
        $tabTangkapan = 'tangkapan';
        $tabOperasional = 'operasional';
        $tabRekap = 'rekap';
    @endphp

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
                    <h4 class="card-title mb-1">Pelayaran Selesai</h4>
                    <p class="card-description mb-0">Lengkapi Perbekalan, Tangkapan, dan Operasional Trip sebelum menutup
                        pelayaran.
                    </p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('pelayaran.sisa.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-8">
                            <label for="pelayaran_id" class="form-label required-asterisk">Pilih Pelayaran Aktif</label>
                            <select name="pelayaran_id" id="pelayaran_id" class="form-control" required>
                                @forelse ($activePelayaran as $trip)
                                    <option value="{{ $trip->id_pelayaran }}"
                                        {{ (string) ($selectedPelayaran->id_pelayaran ?? '') === (string) $trip->id_pelayaran ? 'selected' : '' }}>
                                        #{{ $trip->id_pelayaran }} - {{ $trip->kapal->nama_kapal ?? '-' }}
                                    </option>
                                @empty
                                    <option value="">Tidak ada pelayaran aktif</option>
                                @endforelse
                            </select>
                            <input type="hidden" name="tab" id="active_tab_selector" value="{{ $activeTab }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Muat Form</button>
                        </div>
                    </form>
                </div>
            </div>

            @if ($selectedPelayaran)
                @php
                    $stepStatus = [
                        'Perbekalan' => (bool) $completionStatus['perbekalan'],
                        'Tangkapan' => (bool) $completionStatus['tangkapan'],
                        'Operasional Trip' => (bool) $completionStatus['operasional'],
                    ];
                    $totalSteps = count($stepStatus);
                    $completedSteps = collect($stepStatus)->filter()->count();
                    $progressPercent = $totalSteps > 0 ? (int) round(($completedSteps / $totalSteps) * 100) : 0;
                @endphp

                <div class="card mb-4">
                    <div class="card-body pb-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                            <h5 class="mb-2">Trip #{{ $selectedPelayaran->id_pelayaran }}</h5>
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <span
                                    class="badge {{ $completionStatus['perbekalan'] ? 'badge-success' : 'badge-secondary' }}">
                                    Perbekalan {{ $completionStatus['perbekalan'] ? 'Terisi' : 'Belum' }}
                                </span>
                                <span
                                    class="badge {{ $completionStatus['tangkapan'] ? 'badge-success' : 'badge-secondary' }}">
                                    Tangkapan {{ $completionStatus['tangkapan'] ? 'Terisi' : 'Belum' }}
                                </span>
                                <span
                                    class="badge {{ $completionStatus['operasional'] ? 'badge-success' : 'badge-secondary' }}">
                                    Operasional {{ $completionStatus['operasional'] ? 'Terisi' : 'Belum' }}
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Progress Penutupan Trip</small>
                                <small class="font-weight-bold">{{ $completedSteps }}/{{ $totalSteps }} langkah
                                    ({{ $progressPercent }}%)</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $canClose ? 'bg-success' : 'bg-info' }}" role="progressbar"
                                    style="width: {{ $progressPercent }}%;" aria-valuenow="{{ $progressPercent }}"
                                    aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @foreach ($stepStatus as $label => $isDone)
                                    <span class="badge {{ $isDone ? 'badge-success' : 'badge-light' }} border">
                                        {{ $isDone ? 'Selesai' : 'Menunggu' }} - {{ $label }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <ul class="nav nav-tabs trip-tabs js-trip-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link js-trip-tab {{ $activeTab === $tabPerbekalan ? 'active' : '' }}"
                                    href="#" data-tab="{{ $tabPerbekalan }}">
                                    Perbekalan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link js-trip-tab {{ $activeTab === $tabTangkapan ? 'active' : '' }}"
                                    href="#" data-tab="{{ $tabTangkapan }}">
                                    Tangkapan
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link js-trip-tab {{ $activeTab === $tabOperasional ? 'active' : '' }}"
                                    href="#" data-tab="{{ $tabOperasional }}">
                                    Operasional Trip
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link js-trip-tab {{ $activeTab === $tabRekap ? 'active' : '' }}"
                                    href="#" data-tab="{{ $tabRekap }}">
                                    Rekap Trip
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="js-trip-pane {{ $activeTab === $tabPerbekalan ? '' : 'd-none' }}"
                    data-pane="{{ $tabPerbekalan }}">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-1">Card Sisa Perbekalan</h5>
                                    <p class="text-muted mb-0">Data awal diambil dari perencanaan perbekalan pelayaran.</p>
                                </div>
                                <span class="badge badge-info">Trip #{{ $selectedPelayaran->id_pelayaran }}</span>
                            </div>

                            <form action="{{ route('pelayaran.sisa.perbekalan.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="id_pelayaran" value="{{ $selectedPelayaran->id_pelayaran }}">
                                <input type="hidden" name="tab" class="js-active-tab-input"
                                    value="{{ $activeTab }}">

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Nama Barang</th>
                                                <th>Satuan</th>
                                                <th>Jumlah Awal</th>
                                                <th>Jumlah Sisa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($perbekalanRows as $row)
                                                @php
                                                    $defaultSisa = $existingSisa[$row->id_barang] ?? null;
                                                @endphp
                                                <tr>
                                                    <td>{{ $row->nama_barang }}</td>
                                                    <td>{{ $row->satuan }}</td>
                                                    <td>{{ number_format($row->jumlah_awal, 2) }}</td>
                                                    <td>
                                                        <input type="number" class="form-control"
                                                            name="sisa_qty[{{ $row->id_barang }}]" min="0"
                                                            max="{{ $row->jumlah_awal }}" step="0.01" placeholder="0"
                                                            value="{{ old('sisa_qty.' . $row->id_barang, $defaultSisa) }}">
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Belum ada perbekalan
                                                        terencana untuk pelayaran ini.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="form-group mt-3">
                                    <label for="catatan_sisa">Catatan Sisa Trip</label>
                                    <textarea name="catatan_sisa" id="catatan_sisa" rows="3" class="form-control"
                                        placeholder="Opsional: catatan kondisi sisa perbekalan.">{{ old('catatan_sisa') }}</textarea>
                                </div>

                                <div class="mt-2">
                                    <button type="submit" class="btn btn-primary">Simpan Card Sisa Perbekalan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="js-trip-pane {{ $activeTab === $tabTangkapan ? '' : 'd-none' }}"
                    data-pane="{{ $tabTangkapan }}">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-1">Card Hasil Tangkapan Ikan</h5>
                            <p class="text-muted mb-3">Isi berat hasil tangkapan per jenis ikan (kg). Hanya nilai > 0 yang
                                disimpan.</p>

                            <form action="{{ route('pelayaran.sisa.tangkapan.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="id_pelayaran"
                                    value="{{ $selectedPelayaran->id_pelayaran }}">
                                <input type="hidden" name="tab" class="js-active-tab-input"
                                    value="{{ $activeTab }}">

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Nama Ikan</th>
                                                <th>Berat Tangkapan (kg)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($masterIkan as $ikan)
                                                @php
                                                    $defaultHasil = $existingHasilIkan[$ikan->id_ikan] ?? null;
                                                @endphp
                                                <tr>
                                                    <td>{{ $ikan->nama_ikan }}</td>
                                                    <td>
                                                        <input type="number" class="form-control"
                                                            name="hasil_ikan[{{ $ikan->id_ikan }}]" min="0"
                                                            step="0.01" placeholder="0"
                                                            value="{{ old('hasil_ikan.' . $ikan->id_ikan, $defaultHasil) }}">
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted">Master ikan belum
                                                        tersedia.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-2">
                                    <button type="submit" class="btn btn-primary">Simpan Card Hasil Tangkapan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="js-trip-pane {{ $activeTab === $tabOperasional ? '' : 'd-none' }}"
                    data-pane="{{ $tabOperasional }}">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-1">Operasional Trip</h5>
                            <p class="text-muted mb-3">Isi biaya operasional untuk trip ini. Data sebelumnya akan diganti
                                saat disimpan ulang.</p>

                            <form action="{{ route('pelayaran.sisa.operasional.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="id_pelayaran"
                                    value="{{ $selectedPelayaran->id_pelayaran }}">
                                <input type="hidden" name="tab" class="js-active-tab-input"
                                    value="{{ $activeTab }}">

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Jenis Operasional</th>
                                                <th>Deskripsi Master</th>
                                                <th>Tanggal Biaya</th>
                                                <th>Jumlah</th>
                                                <th>Catatan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($masterOperasional as $master)
                                                <tr>
                                                    <td>{{ $master->nama_operasional }}</td>
                                                    <td>{{ $master->deskripsi ?: '-' }}</td>
                                                    <td style="min-width: 170px;">
                                                        <input type="date"
                                                            name="tanggal[{{ $master->id_master_operasional }}]"
                                                            class="form-control"
                                                            value="{{ old('tanggal.' . $master->id_master_operasional, now()->toDateString()) }}">
                                                    </td>
                                                    <td style="min-width: 170px;">
                                                        <input type="number"
                                                            name="jumlah[{{ $master->id_master_operasional }}]"
                                                            class="form-control" step="0.01" min="0"
                                                            value="{{ old('jumlah.' . $master->id_master_operasional, $existingOperasional[$master->id_master_operasional] ?? null) }}"
                                                            placeholder="Isi jika ada biaya">
                                                    </td>
                                                    <td style="min-width: 260px;">
                                                        <input type="text"
                                                            name="deskripsi[{{ $master->id_master_operasional }}]"
                                                            class="form-control"
                                                            value="{{ old('deskripsi.' . $master->id_master_operasional) }}"
                                                            placeholder="Catatan tambahan">
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">Master operasional
                                                        belum tersedia.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-2">
                                    <button type="submit" class="btn btn-primary">Simpan Operasional Trip</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="js-trip-pane {{ $activeTab === $tabRekap ? '' : 'd-none' }}"
                    data-pane="{{ $tabRekap }}">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Rekap Trip</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <div class="text-muted">Total Item Biaya</div>
                                        <div class="h4 mb-0">{{ $rekapOperasional['total_item_biaya'] }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <div class="text-muted">Total Biaya Operasional</div>
                                        <div class="h4 mb-0">Rp
                                            {{ number_format((float) $rekapOperasional['total_biaya'], 2, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Jenis Operasional</th>
                                            <th>Jumlah</th>
                                            <th>Deskripsi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($rekapOperasional['detail'] as $detail)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($detail->tanggal)->format('d-m-Y') }}</td>
                                                <td>{{ $detail->nama_operasional ?: $detail->jenis_biaya }}</td>
                                                <td>Rp {{ number_format((float) $detail->jumlah, 2, ',', '.') }}</td>
                                                <td>{{ $detail->deskripsi ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Belum ada biaya
                                                    operasional untuk trip ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h6 class="mb-1">Finalisasi Pelayaran</h6>
                            <p class="text-muted mb-0">Tombol hanya aktif jika Perbekalan, Tangkapan, dan Operasional Trip
                                sudah diisi.</p>
                        </div>

                        <div class="d-flex gap-2">
                            <form action="{{ route('pelayaran.sisa.close') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="id_pelayaran"
                                    value="{{ $selectedPelayaran->id_pelayaran }}">
                                <input type="hidden" name="tab" class="js-active-tab-input"
                                    value="{{ $activeTab }}">
                                <button type="submit" class="btn btn-success" {{ $canClose ? '' : 'disabled' }}>
                                    Simpan Dan Tutup Pelayaran
                                </button>
                            </form>
                            <a href="{{ route('pelayaran.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h5 class="mb-2">Tidak ada pelayaran aktif untuk diproses</h5>
                        <p class="text-muted mb-0">Buat rencana pelayaran baru terlebih dahulu.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($selectedPelayaran)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var tabs = document.querySelectorAll('.js-trip-tab');
                var panes = document.querySelectorAll('.js-trip-pane');
                var activeTabInputs = document.querySelectorAll('.js-active-tab-input');
                var selectorInput = document.getElementById('active_tab_selector');
                var tabContainer = document.querySelector('.js-trip-tabs');
                var selectedPelayaranId = '{{ $selectedPelayaran->id_pelayaran }}';

                var updateTabCount = function() {
                    if (!tabContainer) {
                        return;
                    }

                    var visibleTabs = Array.from(tabs).filter(function(tab) {
                        return tab.offsetParent !== null;
                    }).length;

                    tabContainer.style.setProperty('--tab-count', String(Math.max(visibleTabs, 1)));
                };

                var setActiveTab = function(tabName) {
                    tabs.forEach(function(tab) {
                        tab.classList.toggle('active', tab.getAttribute('data-tab') === tabName);
                    });

                    panes.forEach(function(pane) {
                        var isActive = pane.getAttribute('data-pane') === tabName;
                        pane.classList.toggle('d-none', !isActive);
                    });

                    activeTabInputs.forEach(function(input) {
                        input.value = tabName;
                    });

                    if (selectorInput) {
                        selectorInput.value = tabName;
                    }

                    var url = new URL(window.location.href);
                    url.searchParams.set('pelayaran_id', selectedPelayaranId);
                    url.searchParams.set('tab', tabName);
                    window.history.replaceState({}, '', url.toString());
                };

                tabs.forEach(function(tab) {
                    tab.addEventListener('click', function(event) {
                        event.preventDefault();
                        setActiveTab(tab.getAttribute('data-tab'));
                    });
                });

                updateTabCount();
                window.addEventListener('resize', updateTabCount);
            });
        </script>
    @endif
@endsection
