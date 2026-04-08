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
        $tabTangkapanPribadi = 'tangkapan-pribadi';
        $tabTangkapanBersama = 'tangkapan-bersama';
        $tabTangkapanJaringan = 'tangkapan-jaringan';
        $tabOperasional = 'operasional';
        $tabRekap = 'rekap';

        $tangkapanTabs = [
            $tabTangkapanPribadi => ['key' => 'pancingan_pribadi', 'label' => 'Pancingan Pribadi'],
            $tabTangkapanBersama => ['key' => 'pancingan_bersama', 'label' => 'Pancingan Bersama'],
            $tabTangkapanJaringan => ['key' => 'jaringan', 'label' => 'Jaringan'],
        ];
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
                                    Semua Kategori Tangkapan {{ $completionStatus['tangkapan'] ? 'Terisi' : 'Belum' }}
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
                                <a class="nav-link js-trip-tab {{ $activeTab === $tabTangkapanPribadi ? 'active' : '' }}"
                                    href="#" data-tab="{{ $tabTangkapanPribadi }}">
                                    Pancingan Pribadi
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link js-trip-tab {{ $activeTab === $tabTangkapanBersama ? 'active' : '' }}"
                                    href="#" data-tab="{{ $tabTangkapanBersama }}">
                                    Pancingan Bersama
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link js-trip-tab {{ $activeTab === $tabTangkapanJaringan ? 'active' : '' }}"
                                    href="#" data-tab="{{ $tabTangkapanJaringan }}">
                                    Jaringan
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

                @foreach ($tangkapanTabs as $tabKey => $meta)
                    <div class="js-trip-pane {{ $activeTab === $tabKey ? '' : 'd-none' }}"
                        data-pane="{{ $tabKey }}">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-1">Card Hasil Tangkapan Ikan - {{ $meta['label'] }}</h5>
                                <p class="text-muted mb-3">Isi berat tangkapan (kg) dan harga per kg untuk kategori
                                    {{ $meta['label'] }}. Hanya berat > 0 yang disimpan.</p>

                                <div class="row mb-3 js-kategori-subtotal" data-kategori-tab="{{ $tabKey }}">
                                    <div class="col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <small class="text-muted d-block">Subtotal Berat {{ $meta['label'] }}</small>
                                            <div class="h6 mb-0 js-subtotal-berat">0,00 kg</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-2 h-100">
                                            <small class="text-muted d-block">Subtotal Nilai {{ $meta['label'] }}</small>
                                            <div class="h6 mb-0 js-subtotal-nilai">Rp 0,00</div>
                                        </div>
                                    </div>
                                </div>

                                <form action="{{ route('pelayaran.sisa.tangkapan.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id_pelayaran"
                                        value="{{ $selectedPelayaran->id_pelayaran }}">
                                    <input type="hidden" name="kategori_tangkapan" value="{{ $meta['key'] }}">
                                    <input type="hidden" name="tab" class="js-active-tab-input"
                                        value="{{ $activeTab }}">

                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Nama Ikan Tangkapan</th>
                                                    <th>Berat Tangkapan (kg)</th>
                                                    <th>Harga per Kg (Rp)</th>
                                                    <th>Estimasi Nilai (Rp)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($masterIkanTangkapan as $ikanTangkapan)
                                                    @php
                                                        $existingByCategory =
                                                            $existingHasilIkanByKategori[$meta['key']][
                                                                $ikanTangkapan->id_ikan_tangkapan
                                                            ] ?? null;
                                                        $defaultHasil = $existingByCategory['berat_hasil'] ?? null;
                                                        $defaultHarga = $existingByCategory['harga_per_kg'] ?? null;
                                                        $relasiPenjualan = $ikanTangkapan->masterIkan
                                                            ->pluck('nama_ikan')
                                                            ->filter()
                                                            ->values();
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <div>{{ $ikanTangkapan->nama_ikan_tangkapan }}</div>
                                                            @if ($relasiPenjualan->isNotEmpty())
                                                                <small class="text-muted d-block">Grade penjualan:
                                                                    {{ $relasiPenjualan->join(', ') }}</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control js-berat-input"
                                                                name="hasil_ikan[{{ $ikanTangkapan->id_ikan_tangkapan }}]"
                                                                min="0" step="0.01" placeholder="0"
                                                                value="{{ old('hasil_ikan.' . $ikanTangkapan->id_ikan_tangkapan, $defaultHasil) }}"
                                                                data-target="#nilai_{{ $meta['key'] }}_{{ $ikanTangkapan->id_ikan_tangkapan }}"
                                                                data-role="berat">
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control js-harga-input"
                                                                name="harga_ikan[{{ $ikanTangkapan->id_ikan_tangkapan }}]"
                                                                min="0" step="0.01" placeholder="0"
                                                                value="{{ old('harga_ikan.' . $ikanTangkapan->id_ikan_tangkapan, $defaultHarga) }}"
                                                                data-target="#nilai_{{ $meta['key'] }}_{{ $ikanTangkapan->id_ikan_tangkapan }}"
                                                                data-role="harga">
                                                        </td>
                                                        <td>
                                                            <div class="font-weight-bold text-muted js-nilai-output"
                                                                id="nilai_{{ $meta['key'] }}_{{ $ikanTangkapan->id_ikan_tangkapan }}">
                                                                Rp 0
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">Master ikan
                                                            tangkapan
                                                            belum tersedia.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary">Simpan
                                            {{ $meta['label'] }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach

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
                                @foreach ($kategoriTangkapanMap as $kategoriKey => $kategoriLabel)
                                    @php
                                        $rekapRow = $rekapTangkapan[$kategoriKey] ?? null;
                                        $totalBeratKategori = (float) ($rekapRow->total_berat ?? 0);
                                        $totalNilaiKategori = (float) ($rekapRow->total_nilai ?? 0);
                                    @endphp
                                    <div class="col-md-4 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted mb-1">{{ $kategoriLabel }}</div>
                                            <div class="small">Total Berat:
                                                <strong>{{ number_format($totalBeratKategori, 2, ',', '.') }} kg</strong>
                                            </div>
                                            <div class="small">Total Nilai: <strong>Rp
                                                    {{ number_format($totalNilaiKategori, 2, ',', '.') }}</strong></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

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

                var updateEstimatedValue = function(targetSelector) {
                    var output = document.querySelector(targetSelector);
                    if (!output) {
                        return;
                    }

                    var row = output.closest('tr');
                    if (!row) {
                        return;
                    }

                    var beratInput = row.querySelector('[data-role="berat"]');
                    var hargaInput = row.querySelector('[data-role="harga"]');
                    var berat = parseFloat((beratInput && beratInput.value) || '0');
                    var harga = parseFloat((hargaInput && hargaInput.value) || '0');
                    var total = (isNaN(berat) ? 0 : berat) * (isNaN(harga) ? 0 : harga);
                    output.textContent = 'Rp ' + total.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                var updateCategorySubtotal = function(container) {
                    if (!container) {
                        return;
                    }

                    var totalBerat = 0;
                    var totalNilai = 0;
                    var rows = container.querySelectorAll('tbody tr');

                    rows.forEach(function(row) {
                        var beratInput = row.querySelector('[data-role="berat"]');
                        var hargaInput = row.querySelector('[data-role="harga"]');

                        if (!beratInput || !hargaInput) {
                            return;
                        }

                        var berat = parseFloat(beratInput.value || '0');
                        var harga = parseFloat(hargaInput.value || '0');
                        berat = isNaN(berat) ? 0 : berat;
                        harga = isNaN(harga) ? 0 : harga;

                        totalBerat += berat;
                        totalNilai += berat * harga;
                    });

                    var beratOutput = container.querySelector('.js-subtotal-berat');
                    var nilaiOutput = container.querySelector('.js-subtotal-nilai');

                    if (beratOutput) {
                        beratOutput.textContent = totalBerat.toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }) + ' kg';
                    }

                    if (nilaiOutput) {
                        nilaiOutput.textContent = 'Rp ' + totalNilai.toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                };

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

                document.querySelectorAll('.js-berat-input, .js-harga-input').forEach(function(input) {
                    var targetSelector = input.getAttribute('data-target');
                    if (targetSelector) {
                        updateEstimatedValue(targetSelector);
                    }

                    var pane = input.closest('.js-trip-pane');
                    if (pane) {
                        updateCategorySubtotal(pane);
                    }

                    input.addEventListener('input', function() {
                        if (targetSelector) {
                            updateEstimatedValue(targetSelector);
                        }

                        var currentPane = input.closest('.js-trip-pane');
                        if (currentPane) {
                            updateCategorySubtotal(currentPane);
                        }
                    });
                });

                document.querySelectorAll('.js-trip-pane').forEach(function(pane) {
                    updateCategorySubtotal(pane);
                });
            });
        </script>
    @endif
@endsection
