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

        .trip-table-dynamic {
            width: 100%;
            table-layout: auto;
        }

        .trip-table-dynamic th,
        .trip-table-dynamic td {
            white-space: normal;
            word-break: break-word;
            vertical-align: top;
        }

        .trip-table-cell-wrap {
            min-width: 220px;
            line-height: 1.4;
        }

        .trip-close-modal .modal-dialog {
            display: flex;
            align-items: center;
            min-height: calc(100vh - 1rem);
        }

        .trip-close-modal .modal-content {
            width: 100%;
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
        $tabTangkapan3Ton = 'tangkapan-3-ton';
        $tabOperasional = 'operasional';
        $tabRekap = 'rekap';

        $tangkapanTabs = [
            $tabTangkapanPribadi => ['key' => 'pancingan_pribadi', 'label' => 'Pancingan Pribadi'],
            $tabTangkapanBersama => ['key' => 'pancingan_bersama', 'label' => 'Pancingan Bersama'],
            $tabTangkapanJaringan => ['key' => 'jaringan', 'label' => 'Jaringan'],
            $tabTangkapan3Ton => ['key' => 'tangkapan_3_ton', 'label' => 'Tangkapan 3 Ton'],
        ];

        $isReadOnly = $isReadOnly ?? false;
        $pageHeading = $isReadOnly ? 'Detail Riwayat Pelayaran' : 'Pelayaran Selesai';
        $pageDescription = $isReadOnly
            ? 'Lihat detail card penutupan trip dalam mode baca saja.'
            : 'Lengkapi Perbekalan, Tangkapan, dan Operasional Trip sebelum menutup pelayaran.';
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
                    <h4 class="card-title mb-1">{{ $pageHeading }}</h4>
                    <p class="card-description mb-0">{{ $pageDescription }}</p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    @if (!$isReadOnly)
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
                    @else
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="mb-1">Mode Lihat Detail</h5>
                                <p class="text-muted mb-0">Trip ini sudah ditutup dan hanya ditampilkan dalam mode baca
                                    saja.</p>
                            </div>
                            <a href="{{ route('pelayaran.sisa.history') }}" class="btn btn-outline-primary">Kembali ke
                                Riwayat</a>
                        </div>
                    @endif
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
                            <div class="mb-2">
                                <h5 class="mb-1">Trip #{{ $selectedPelayaran->id_pelayaran }}</h5>
                                <small class="text-muted">Kapal: {{ $selectedPelayaran->kapal->nama_kapal ?? '-' }}</small>
                            </div>
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
                                @if ($isReadOnly)
                                    <span class="badge badge-warning">Mode Lihat Detail</span>
                                @endif
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
                                <a class="nav-link js-trip-tab {{ $activeTab === $tabTangkapan3Ton ? 'active' : '' }}"
                                    href="#" data-tab="{{ $tabTangkapan3Ton }}">
                                    Tangkapan 3 Ton
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

                                <fieldset {{ $isReadOnly ? 'disabled' : '' }}>
                                    <div class="table-responsive">
                                        <table class="table table-bordered trip-table-dynamic">
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
                                                                max="{{ $row->jumlah_awal }}" step="0.01"
                                                                placeholder="0"
                                                                value="{{ old('sisa_qty.' . $row->id_barang, $defaultSisa) }}">
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">Belum ada
                                                            perbekalan
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
                                        @if (!$isReadOnly)
                                            <button type="submit" class="btn btn-primary">Simpan Card Sisa
                                                Perbekalan</button>
                                        @else
                                            <span class="badge badge-light border">Card ini hanya untuk dilihat.</span>
                                        @endif
                                    </div>
                                </fieldset>
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
                                @if ($meta['key'] === 'tangkapan_3_ton')
                                    <div class="alert alert-warning py-2">
                                        Total berat maksimal untuk tab ini adalah <strong>3000 kg</strong> (akumulasi semua
                                        ikan).
                                        Nilai kategori ini dicatat, tetapi tidak dikreditkan ke Arus Kas saat penutupan.
                                    </div>
                                    <div class="alert alert-danger py-2 d-none js-3ton-live-guard-message"
                                        data-guard-tab="{{ $tabKey }}">
                                        Total berat melebihi 3000 kg. Kurangi input sebelum menyimpan.
                                    </div>
                                @endif

                                @if ($meta['key'] !== 'pancingan_pribadi')
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
                                @endif

                                <form action="{{ route('pelayaran.sisa.tangkapan.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id_pelayaran"
                                        value="{{ $selectedPelayaran->id_pelayaran }}">
                                    <input type="hidden" name="kategori_tangkapan" value="{{ $meta['key'] }}">
                                    <input type="hidden" name="tab" class="js-active-tab-input"
                                        value="{{ $activeTab }}">

                                    <fieldset {{ $isReadOnly ? 'disabled' : '' }}>
                                        @if ($meta['key'] === 'pancingan_pribadi')
                                            @php
                                                $oldAnglers = old('anglers');
                                                $initialAnglers = is_array($oldAnglers)
                                                    ? $oldAnglers
                                                    : ($existingPersonalAnglers ?? []);
                                                if (empty($initialAnglers)) {
                                                    $initialAnglers = [
                                                        [
                                                            'name' => '',
                                                            'items' => [
                                                                [
                                                                    'id_ikan_tangkapan' => '',
                                                                    'berat' => '',
                                                                    'harga_per_kg' => '',
                                                                ],
                                                            ],
                                                        ],
                                                    ];
                                                }
                                            @endphp

                                            @if ($errors->has('anglers'))
                                                <div class="alert alert-danger py-2">{{ $errors->first('anglers') }}</div>
                                            @endif

                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="mb-0 font-weight-bold required-asterisk">Daftar Penangkap</label>
                                                <button type="button" class="btn btn-sm btn-outline-secondary js-add-angler"
                                                    data-tab="{{ $tabKey }}">Tambah Nama</button>
                                            </div>

                                            <div class="js-angler-list" data-tab="{{ $tabKey }}">
                                                @foreach ($initialAnglers as $anglerIndex => $angler)
                                                    @php
                                                        $anglerName = trim((string) ($angler['name'] ?? ''));
                                                        $anglerItems = is_array($angler['items'] ?? null)
                                                            ? $angler['items']
                                                            : [];
                                                        if (empty($anglerItems)) {
                                                            $anglerItems = [
                                                                [
                                                                    'id_ikan_tangkapan' => '',
                                                                    'berat' => '',
                                                                    'harga_per_kg' => '',
                                                                ],
                                                            ];
                                                        }
                                                    @endphp
                                                    <div class="border rounded p-3 mb-3 js-angler-card"
                                                        data-angler-index="{{ $anglerIndex }}">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div class="form-group mb-0 flex-grow-1 mr-2">
                                                                <label class="required-asterisk mb-1">Nama Penangkap</label>
                                                                <input type="text" class="form-control js-angler-name"
                                                                    name="anglers[{{ $anglerIndex }}][name]"
                                                                    value="{{ $anglerName }}" placeholder="Contoh: Budi"
                                                                    maxlength="120">
                                                            </div>
                                                            <button type="button" class="btn btn-sm btn-outline-danger js-remove-angler"
                                                                {{ count($initialAnglers) > 1 ? '' : 'style=display:none;' }}>
                                                                Hapus Nama
                                                            </button>
                                                        </div>

                                                        <div class="table-responsive">
                                                            <table class="table table-bordered table-sm mb-2">
                                                                <thead>
                                                                    <tr>
                                                                        <th class="required-asterisk">Ikan Tangkapan</th>
                                                                        <th class="required-asterisk">Berat (kg)</th>
                                                                        <th>Harga per Kg (Rp)</th>
                                                                        <th>Estimasi Nilai</th>
                                                                        <th style="width: 56px;">Aksi</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="js-angler-item-list">
                                                                    @foreach ($anglerItems as $itemIndex => $anglerItem)
                                                                        @php
                                                                            $selectedIkanTangkapan = (string) ($anglerItem[
                                                                                'id_ikan_tangkapan'
                                                                            ] ?? '');
                                                                            $beratValue = $anglerItem['berat'] ?? '';
                                                                            $hargaValue = $anglerItem['harga_per_kg'] ?? '';
                                                                        @endphp
                                                                        <tr class="js-angler-item-row"
                                                                            data-item-index="{{ $itemIndex }}">
                                                                            <td>
                                                                                <select class="form-control js-angler-fish-select"
                                                                                    name="anglers[{{ $anglerIndex }}][items][{{ $itemIndex }}][id_ikan_tangkapan]">
                                                                                    <option value="">Pilih ikan tangkapan</option>
                                                                                    @foreach ($masterIkanTangkapan as $ikanTangkapan)
                                                                                        <option
                                                                                            value="{{ $ikanTangkapan->id_ikan_tangkapan }}"
                                                                                            {{ $selectedIkanTangkapan === (string) $ikanTangkapan->id_ikan_tangkapan ? 'selected' : '' }}>
                                                                                            {{ $ikanTangkapan->nama_ikan_tangkapan }}
                                                                                        </option>
                                                                                    @endforeach
                                                                                </select>
                                                                            </td>
                                                                            <td>
                                                                                <input type="number"
                                                                                    class="form-control js-berat-input"
                                                                                    name="anglers[{{ $anglerIndex }}][items][{{ $itemIndex }}][berat]"
                                                                                    min="0" step="0.01" placeholder="0"
                                                                                    value="{{ $beratValue }}"
                                                                                    data-target="#nilai_{{ $tabKey }}_{{ $anglerIndex }}_{{ $itemIndex }}"
                                                                                    data-role="berat">
                                                                            </td>
                                                                            <td>
                                                                                <input type="text"
                                                                                    class="form-control js-harga-input"
                                                                                    name="anglers[{{ $anglerIndex }}][items][{{ $itemIndex }}][harga_per_kg]"
                                                                                    inputmode="decimal"
                                                                                    placeholder="0,00"
                                                                                    value="{{ $hargaValue }}"
                                                                                    data-target="#nilai_{{ $tabKey }}_{{ $anglerIndex }}_{{ $itemIndex }}"
                                                                                    data-role="harga">
                                                                            </td>
                                                                            <td>
                                                                                <div
                                                                                    class="font-weight-bold text-muted js-nilai-output"
                                                                                    id="nilai_{{ $tabKey }}_{{ $anglerIndex }}_{{ $itemIndex }}">
                                                                                    Rp 0
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <button type="button"
                                                                                    class="btn btn-sm btn-outline-danger js-remove-angler-item"
                                                                                    title="Hapus ikan">&times;</button>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary js-add-angler-item">Tambah
                                                            Ikan</button>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <template id="js-angler-template-{{ $tabKey }}">
                                                <div class="border rounded p-3 mb-3 js-angler-card" data-angler-index="__A__">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div class="form-group mb-0 flex-grow-1 mr-2">
                                                            <label class="required-asterisk mb-1">Nama Penangkap</label>
                                                            <input type="text" class="form-control js-angler-name"
                                                                name="anglers[__A__][name]" value=""
                                                                placeholder="Contoh: Budi" maxlength="120">
                                                        </div>
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-danger js-remove-angler">Hapus
                                                            Nama</button>
                                                    </div>

                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-sm mb-2">
                                                            <thead>
                                                                <tr>
                                                                    <th class="required-asterisk">Ikan Tangkapan</th>
                                                                    <th class="required-asterisk">Berat (kg)</th>
                                                                    <th>Harga per Kg (Rp)</th>
                                                                    <th>Estimasi Nilai</th>
                                                                    <th style="width: 56px;">Aksi</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="js-angler-item-list">
                                                                <tr class="js-angler-item-row" data-item-index="0">
                                                                    <td>
                                                                        <select class="form-control js-angler-fish-select"
                                                                            name="anglers[__A__][items][0][id_ikan_tangkapan]">
                                                                            <option value="">Pilih ikan tangkapan</option>
                                                                            @foreach ($masterIkanTangkapan as $ikanTangkapan)
                                                                                <option
                                                                                    value="{{ $ikanTangkapan->id_ikan_tangkapan }}">
                                                                                    {{ $ikanTangkapan->nama_ikan_tangkapan }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                    </td>
                                                                    <td>
                                                                        <input type="number"
                                                                            class="form-control js-berat-input"
                                                                            name="anglers[__A__][items][0][berat]"
                                                                            min="0" step="0.01" placeholder="0"
                                                                            value=""
                                                                            data-target="#nilai_{{ $tabKey }}___A___0"
                                                                            data-role="berat">
                                                                    </td>
                                                                    <td>
                                                                        <input type="text"
                                                                            class="form-control js-harga-input"
                                                                            name="anglers[__A__][items][0][harga_per_kg]"
                                                                            inputmode="decimal"
                                                                            placeholder="0,00"
                                                                            value=""
                                                                            data-target="#nilai_{{ $tabKey }}___A___0"
                                                                            data-role="harga">
                                                                    </td>
                                                                    <td>
                                                                        <div class="font-weight-bold text-muted js-nilai-output"
                                                                            id="nilai_{{ $tabKey }}___A___0">Rp 0
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-outline-danger js-remove-angler-item"
                                                                            title="Hapus ikan">&times;</button>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary js-add-angler-item">Tambah
                                                        Ikan</button>
                                                </div>
                                            </template>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-bordered trip-table-dynamic">
                                                    <thead>
                                                        <tr>
                                                            <th>Nama Ikan Tangkapan</th>
                                                            <th class="required-asterisk">Berat Tangkapan (kg)</th>
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
                                                            @endphp
                                                            <tr>
                                                                <td class="trip-table-cell-wrap">
                                                                    <div class="font-weight-bold">
                                                                        {{ $ikanTangkapan->nama_ikan_tangkapan }}
                                                                    </div>
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
                                                                    <input type="text" class="form-control js-harga-input"
                                                                        name="harga_ikan[{{ $ikanTangkapan->id_ikan_tangkapan }}]"
                                                                        inputmode="decimal" placeholder="0,00"
                                                                        value="{{ old('harga_ikan.' . $ikanTangkapan->id_ikan_tangkapan, $defaultHarga) }}"
                                                                        data-target="#nilai_{{ $meta['key'] }}_{{ $ikanTangkapan->id_ikan_tangkapan }}"
                                                                        data-role="harga">
                                                                </td>
                                                                <td>
                                                                    <div
                                                                        class="font-weight-bold text-muted js-nilai-output"
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
                                        @endif

                                        <div class="mt-2">
                                            @if (!$isReadOnly)
                                                <button type="submit" class="btn btn-primary">Simpan
                                                    {{ $meta['label'] }}</button>
                                            @else
                                                <span class="badge badge-light border">Card ini hanya untuk dilihat.</span>
                                            @endif
                                        </div>
                                    </fieldset>
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

                                <fieldset {{ $isReadOnly ? 'disabled' : '' }}>
                                    <div class="table-responsive">
                                        <table class="table table-bordered trip-table-dynamic">
                                            <thead>
                                                <tr>
                                                    <th>Jenis Operasional</th>
                                                    <th>Deskripsi Master</th>
                                                    <th>Tanggal Biaya</th>
                                                    <th class="required-asterisk">Jumlah</th>
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
                                                            <input type="text"
                                                                name="jumlah[{{ $master->id_master_operasional }}]"
                                                                class="form-control" data-rupiah-input inputmode="decimal"
                                                                value="{{ old('jumlah.' . $master->id_master_operasional, $existingOperasional[$master->id_master_operasional] ?? null) }}"
                                                                placeholder="0,00">
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
                                                        <td colspan="5" class="text-center text-muted">Master
                                                            operasional
                                                            belum tersedia.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-2">
                                        @if (!$isReadOnly)
                                            <button type="submit" class="btn btn-primary">Simpan Operasional
                                                Trip</button>
                                        @else
                                            <span class="badge badge-light border">Card ini hanya untuk dilihat.</span>
                                        @endif
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="js-trip-pane {{ $activeTab === $tabRekap ? '' : 'd-none' }}"
                    data-pane="{{ $tabRekap }}">
                    @php
                        $jaringanCreditFactor = (float) ($rekapGrandTotals['jaringan_credit_factor'] ?? 0.5);
                        $jaringanCreditPercent = (int) round($jaringanCreditFactor * 100);
                        $jaringanProfitSharingPercent = max(0, 100 - $jaringanCreditPercent);
                    @endphp
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Rekap Trip</h5>
                            <div class="row mb-3">
                                @foreach ($kategoriTangkapanMap as $kategoriKey => $kategoriLabel)
                                    @php
                                        $rekapRow = $rekapTangkapan[$kategoriKey] ?? null;
                                        $totalBeratKategori = (float) ($rekapRow->total_berat ?? 0);
                                        $totalNilaiKategori = (float) ($rekapRow->total_nilai ?? 0);
                                        $isJaringanCategory = $kategoriKey === 'jaringan';
                                        $isTangkapan3TonCategory = $kategoriKey === 'tangkapan_3_ton';
                                        $totalNilaiKategoriDikreditkan = $isJaringanCategory
                                            ? $totalNilaiKategori * $jaringanCreditFactor
                                            : ($isTangkapan3TonCategory
                                                ? 0
                                                : $totalNilaiKategori);
                                    @endphp
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="text-muted mb-1">
                                                {{ $kategoriLabel }}
                                                @if ($isJaringanCategory)
                                                    (Dikreditkan {{ $jaringanCreditPercent }}%)
                                                @elseif ($isTangkapan3TonCategory)
                                                    (Tidak Dikreditkan)
                                                @endif
                                            </div>
                                            <div class="small">Total Berat:
                                                <strong>{{ number_format($totalBeratKategori, 2, ',', '.') }} kg</strong>
                                            </div>
                                            <div class="small">Total Nilai: <strong>Rp
                                                    {{ number_format($totalNilaiKategoriDikreditkan, 2, ',', '.') }}</strong>
                                            </div>
                                            @if ($isTangkapan3TonCategory)
                                                <div class="small text-muted mt-1">Nilai Tercatat:
                                                    Rp {{ number_format($totalNilaiKategori, 2, ',', '.') }}
                                                </div>
                                            @endif
                                            @if ($isJaringanCategory)
                                                <div class="small text-muted mt-1">Bagi Hasil
                                                    ({{ $jaringanProfitSharingPercent }}%):
                                                    Rp
                                                    {{ number_format((float) ($rekapGrandTotals['total_jaringan_bagi_hasil'] ?? 0), 2, ',', '.') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach

                                <div class="col-lg-4 col-md-6 mb-3">
                                    <div class="border rounded p-3 h-100 bg-light">
                                        <div class="text-muted mb-1">Perbekalan Terpakai</div>
                                        <div class="small">Item Terpakai:
                                            <strong>{{ $rekapGrandTotals['item_perbekalan_terpakai'] }} item</strong>
                                        </div>
                                        <div class="small">Total Biaya:
                                            <strong>Rp
                                                {{ number_format((float) $rekapGrandTotals['total_perbekalan_terpakai'], 2, ',', '.') }}</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 col-md-6 mb-3">
                                    <div class="border rounded p-3 h-100 bg-light">
                                        <div class="text-muted mb-1">Operasional Trip</div>
                                        <div class="small">Total Item:
                                            <strong>{{ $rekapOperasional['total_item_biaya'] }}</strong>
                                        </div>
                                        <div class="small">Total Biaya:
                                            <strong>Rp
                                                {{ number_format((float) $rekapOperasional['total_biaya'], 2, ',', '.') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-lg-6 mb-3">
                                    <div class="border rounded p-3 h-100 border-warning">
                                        <div class="text-muted">Grand Total Semua Komponen</div>
                                        <div class="h4 mb-1">Rp
                                            {{ number_format((float) $rekapGrandTotals['grand_total_semua_komponen'], 2, ',', '.') }}
                                        </div>
                                        <small class="text-muted">Nominal ini akan dipost sebagai kredit ke menu Arus Kas
                                            saat trip ditutup.</small>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <div
                                        class="border rounded p-3 h-100 {{ (float) $rekapGrandTotals['estimasi_selisih_bersih'] >= 0 ? 'border-success' : 'border-danger' }}">
                                        <div class="text-muted">Estimasi Selisih Bersih</div>
                                        <div
                                            class="h4 mb-1 {{ (float) $rekapGrandTotals['estimasi_selisih_bersih'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            Rp
                                            {{ number_format((float) $rekapGrandTotals['estimasi_selisih_bersih'], 2, ',', '.') }}
                                        </div>
                                        <small class="text-muted">Total nilai tangkapan dikurangi biaya perbekalan dan
                                            operasional.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-sm trip-table-dynamic">
                                    <thead>
                                        <tr>
                                            <th>Komponen Rekap</th>
                                            <th class="text-right">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($kategoriTangkapanMap as $kategoriKey => $kategoriLabel)
                                            @php
                                                $rekapRow = $rekapTangkapan[$kategoriKey] ?? null;
                                                $nilaiKategori = (float) ($rekapRow->total_nilai ?? 0);
                                                $isJaringanCategory = $kategoriKey === 'jaringan';
                                                $isTangkapan3TonCategory = $kategoriKey === 'tangkapan_3_ton';
                                                $nilaiKategoriDikreditkan = $isJaringanCategory
                                                    ? $nilaiKategori * $jaringanCreditFactor
                                                    : ($isTangkapan3TonCategory
                                                        ? 0
                                                        : $nilaiKategori);
                                            @endphp
                                            <tr>
                                                <td>Total Nilai {{ $kategoriLabel }}
                                                    @if ($isJaringanCategory)
                                                        (Dikreditkan {{ $jaringanCreditPercent }}%)
                                                    @elseif ($isTangkapan3TonCategory)
                                                        (Tidak Dikreditkan)
                                                    @endif
                                                </td>
                                                <td class="text-right">Rp
                                                    {{ number_format($nilaiKategoriDikreditkan, 2, ',', '.') }}
                                                </td>
                                            </tr>
                                            @if ($isTangkapan3TonCategory && $nilaiKategori > 0)
                                                <tr>
                                                    <td>Nilai Tercatat {{ $kategoriLabel }}</td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($nilaiKategori, 2, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endif
                                            @if ($isJaringanCategory && (float) ($rekapGrandTotals['total_jaringan_bagi_hasil'] ?? 0) > 0)
                                                <tr>
                                                    <td>Bagi Hasil Jaringan ({{ $jaringanProfitSharingPercent }}%)</td>
                                                    <td class="text-right">Rp
                                                        {{ number_format((float) ($rekapGrandTotals['total_jaringan_bagi_hasil'] ?? 0), 2, ',', '.') }}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                        <tr>
                                            <td>Total Biaya Perbekalan Terpakai</td>
                                            <td class="text-right">Rp
                                                {{ number_format((float) $rekapGrandTotals['total_perbekalan_terpakai'], 2, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Total Biaya Operasional Trip</td>
                                            <td class="text-right">Rp
                                                {{ number_format((float) $rekapGrandTotals['total_operasional'], 2, ',', '.') }}
                                            </td>
                                        </tr>
                                        <tr class="table-warning font-weight-bold">
                                            <td>Grand Total Semua Komponen</td>
                                            <td class="text-right">Rp
                                                {{ number_format((float) $rekapGrandTotals['grand_total_semua_komponen'], 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="mb-2">Rekap Pemakaian Perbekalan</h6>
                            <p class="text-muted mb-3">Biaya dihitung dari jumlah terpakai = jumlah awal - jumlah sisa,
                                lalu dikalikan harga beli terakhir.</p>

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-sm trip-table-dynamic">
                                    <thead>
                                        <tr>
                                            <th>Nama Barang</th>
                                            <th>Satuan</th>
                                            <th>Jumlah Awal</th>
                                            <th>Jumlah Sisa</th>
                                            <th>Jumlah Terpakai</th>
                                            <th>Harga Beli</th>
                                            <th>Total Biaya</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($rekapPerbekalan as $row)
                                            <tr>
                                                <td>{{ $row->nama_barang }}</td>
                                                <td>{{ $row->satuan }}</td>
                                                <td>{{ number_format((float) $row->jumlah_awal, 2, ',', '.') }}</td>
                                                <td>
                                                    @if ($row->has_sisa)
                                                        {{ number_format((float) $row->jumlah_sisa, 2, ',', '.') }}
                                                    @else
                                                        <span class="text-muted">Belum diisi</span>
                                                    @endif
                                                </td>
                                                <td>{{ number_format((float) $row->jumlah_terpakai, 2, ',', '.') }}</td>
                                                <td>Rp {{ number_format((float) $row->harga_beli, 2, ',', '.') }}</td>
                                                <td>Rp {{ number_format((float) $row->total_biaya, 2, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">Belum ada data
                                                    perbekalan untuk trip ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="mb-2">Detail Operasional Trip</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm trip-table-dynamic">
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
                            <h6 class="mb-1">{{ $isReadOnly ? 'Aksi Riwayat Pelayaran' : 'Finalisasi Pelayaran' }}</h6>
                            <p class="text-muted mb-0">
                                {{ $isReadOnly ? 'Trip ini sudah ditutup. Gunakan halaman ini untuk melihat detail card penutupan.' : 'Tombol hanya aktif jika Perbekalan, Tangkapan, dan Operasional Trip sudah diisi.' }}
                            </p>
                        </div>

                        <div class="d-flex gap-2">
                            @if (!$isReadOnly)
                                @php
                                    $grandTotalPenutupan =
                                        (float) ($rekapGrandTotals['grand_total_semua_komponen'] ?? 0);
                                    $selectedPaymentMethod = old('payment_method', 'cash');
                                @endphp
                                <form action="{{ route('pelayaran.sisa.close') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="id_pelayaran"
                                        value="{{ $selectedPelayaran->id_pelayaran }}">
                                    <input type="hidden" name="tab" class="js-active-tab-input"
                                        value="{{ $activeTab }}">

                                    <button type="button" class="btn btn-success" data-toggle="modal"
                                        data-target="#closePelayaranModal" {{ $canClose ? '' : 'disabled' }}>
                                        Simpan Dan Tutup Pelayaran
                                    </button>

                                    <div class="modal fade trip-close-modal" id="closePelayaranModal" tabindex="-1"
                                        role="dialog" aria-labelledby="closePelayaranModalLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="closePelayaranModalLabel">Konfirmasi
                                                        Penutupan Pelayaran</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-info py-2">
                                                        Grand total penutupan ini akan otomatis masuk ke
                                                        <a href="{{ route('keuangan.arus-kas.index') }}">menu Arus
                                                            Kas</a>
                                                        sebagai transaksi kredit.
                                                    </div>

                                                    @if ($errors->has('payment_method') || $errors->has('bayar_tunai') || $errors->has('bayar_transfer'))
                                                        <div class="alert alert-danger py-2">
                                                            <ul class="mb-0 pl-3">
                                                                @error('payment_method')
                                                                    <li>{{ $message }}</li>
                                                                @enderror
                                                                @error('bayar_tunai')
                                                                    <li>{{ $message }}</li>
                                                                @enderror
                                                                @error('bayar_transfer')
                                                                    <li>{{ $message }}</li>
                                                                @enderror
                                                            </ul>
                                                        </div>
                                                    @endif

                                                    <div class="table-responsive mb-3">
                                                        <table class="table table-sm table-bordered mb-0">
                                                            <tbody>
                                                                <tr>
                                                                    <th>Total Perbekalan Terpakai</th>
                                                                    <td class="text-right">Rp
                                                                        {{ number_format((float) $rekapGrandTotals['total_perbekalan_terpakai'], 2, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                                @foreach ($kategoriTangkapanMap as $kategoriKey => $kategoriLabel)
                                                                    @php
                                                                        $modalRekapRow =
                                                                            $rekapTangkapan[$kategoriKey] ?? null;
                                                                        $modalNilaiKategori =
                                                                            (float) ($modalRekapRow->total_nilai ?? 0);
                                                                        $isJaringanCategory =
                                                                            $kategoriKey === 'jaringan';
                                                                        $isTangkapan3TonCategory =
                                                                            $kategoriKey === 'tangkapan_3_ton';
                                                                        $modalNilaiKategoriDikreditkan = $isJaringanCategory
                                                                            ? $modalNilaiKategori *
                                                                                $jaringanCreditFactor
                                                                            : ($isTangkapan3TonCategory
                                                                                ? 0
                                                                                : $modalNilaiKategori);
                                                                    @endphp
                                                                    <tr>
                                                                        <th>Total {{ $kategoriLabel }}
                                                                            @if ($isJaringanCategory)
                                                                                (Dikreditkan
                                                                                {{ $jaringanCreditPercent }}%)
                                                                            @elseif ($isTangkapan3TonCategory)
                                                                                (Tidak Dikreditkan)
                                                                            @endif
                                                                        </th>
                                                                        <td class="text-right">Rp
                                                                            {{ number_format($modalNilaiKategoriDikreditkan, 2, ',', '.') }}
                                                                        </td>
                                                                    </tr>
                                                                    @if ($isTangkapan3TonCategory && $modalNilaiKategori > 0)
                                                                        <tr>
                                                                            <th>Nilai Tercatat {{ $kategoriLabel }}</th>
                                                                            <td class="text-right">Rp
                                                                                {{ number_format($modalNilaiKategori, 2, ',', '.') }}
                                                                            </td>
                                                                        </tr>
                                                                    @endif
                                                                    @if ($isJaringanCategory && (float) ($rekapGrandTotals['total_jaringan_bagi_hasil'] ?? 0) > 0)
                                                                        <tr>
                                                                            <th>Bagi Hasil Jaringan
                                                                                ({{ $jaringanProfitSharingPercent }}%)
                                                                            </th>
                                                                            <td class="text-right">Rp
                                                                                {{ number_format((float) ($rekapGrandTotals['total_jaringan_bagi_hasil'] ?? 0), 2, ',', '.') }}
                                                                            </td>
                                                                        </tr>
                                                                    @endif
                                                                @endforeach
                                                                <tr>
                                                                    <th>Total Operasional Trip</th>
                                                                    <td class="text-right">Rp
                                                                        {{ number_format((float) $rekapGrandTotals['total_operasional'], 2, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                                <tr class="table-warning font-weight-bold">
                                                                    <th>Grand Total Pembayaran</th>
                                                                    <td class="text-right">Rp
                                                                        {{ number_format($grandTotalPenutupan, 2, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="font-weight-bold d-block mb-2 required-asterisk">
                                                            Metode Pembayaran Grand Total
                                                        </label>
                                                        <div class="row">
                                                            <div class="col-md-4 mb-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input
                                                                        class="custom-control-input js-close-payment-method"
                                                                        type="radio" name="payment_method"
                                                                        id="payment_method_cash" value="cash"
                                                                        {{ $selectedPaymentMethod === 'cash' ? 'checked' : '' }}>
                                                                    <label class="custom-control-label"
                                                                        for="payment_method_cash">Bayar Tunai / Kas
                                                                        Penuh</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input
                                                                        class="custom-control-input js-close-payment-method"
                                                                        type="radio" name="payment_method"
                                                                        id="payment_method_transfer" value="transfer"
                                                                        {{ $selectedPaymentMethod === 'transfer' ? 'checked' : '' }}>
                                                                    <label class="custom-control-label"
                                                                        for="payment_method_transfer">Bayar Transfer /
                                                                        Bank Penuh</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="custom-control custom-radio">
                                                                    <input
                                                                        class="custom-control-input js-close-payment-method"
                                                                        type="radio" name="payment_method"
                                                                        id="payment_method_both" value="both"
                                                                        {{ $selectedPaymentMethod === 'both' ? 'checked' : '' }}>
                                                                    <label class="custom-control-label"
                                                                        for="payment_method_both">Bayar Gabungan Kas +
                                                                        Transfer</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <small class="form-text text-muted">Jika memilih gabungan,
                                                            total kas dan transfer harus sama dengan grand total.</small>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="close_bayar_tunai">Bayar Tunai (Kas)</label>
                                                                <input type="number" min="0" step="0.01"
                                                                    class="form-control js-close-payment-input"
                                                                    id="close_bayar_tunai" name="bayar_tunai"
                                                                    value="{{ old('bayar_tunai', $selectedPaymentMethod === 'cash' ? number_format($grandTotalPenutupan, 2, '.', '') : '') }}"
                                                                    placeholder="0">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label for="close_bayar_transfer">Bayar Transfer</label>
                                                                <input type="number" min="0" step="0.01"
                                                                    class="form-control js-close-payment-input"
                                                                    id="close_bayar_transfer" name="bayar_transfer"
                                                                    value="{{ old('bayar_transfer', $selectedPaymentMethod === 'transfer' ? number_format($grandTotalPenutupan, 2, '.', '') : '') }}"
                                                                    placeholder="0">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="border rounded p-3 bg-light">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span>Grand Total</span>
                                                            <strong>Rp
                                                                {{ number_format($grandTotalPenutupan, 2, ',', '.') }}</strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <span>Total Dialokasikan</span>
                                                            <strong class="js-close-allocated-total">Rp 0,00</strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between">
                                                            <span>Selisih Alokasi</span>
                                                            <strong class="js-close-remaining-total text-danger">Rp
                                                                0,00</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light"
                                                        data-dismiss="modal">Batal</button>
                                                    <button type="submit"
                                                        class="btn btn-success js-confirm-close-button">
                                                        Konfirmasi & Tutup Pelayaran
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <a href="{{ route('pelayaran.index') }}" class="btn btn-light">Batal</a>
                            @else
                                <a href="{{ route('pelayaran.sisa.history') }}" class="btn btn-primary">Kembali ke
                                    Riwayat</a>
                            @endif
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

                var parseHargaNumber = function(rawValue) {
                    var numStr = String(rawValue || '').trim();
                    if (numStr === '') return 0;

                    numStr = numStr.replace(/[^\d,.-]/g, '');
                    numStr = numStr.replace(/\./g, '').replace(',', '.');

                    var num = parseFloat(numStr);
                    return isNaN(num) || !isFinite(num) ? 0 : num;
                };

                var formatHargaDisplay = function(value) {
                    var numStr = String(value || '').trim();
                    if (numStr === '') return '';

                    var parsed = parseHargaNumber(numStr);
                    if (parsed === 0 && numStr !== '0') return '';

                    return parsed.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                };

                var initHargaFormatter = function(container) {
                    if (!container) container = document;

                    container.querySelectorAll('.js-harga-input').forEach(function(input) {
                        if (input.dataset.hargaFormatterBound === '1') return;
                        input.dataset.hargaFormatterBound = '1';

                        // Store raw numeric value in data attribute
                        var initialValue = input.value.trim();
                        if (initialValue !== '') {
                            var rawNum = parseHargaNumber(initialValue);
                            input.dataset.hargaRawValue = rawNum;
                            input.value = formatHargaDisplay(rawNum);
                        } else {
                            input.dataset.hargaRawValue = '';
                        }

                        var handleInput = function(e) {
                            // Extract only digits and decimal/comma separators from displayed value
                            var displayed = input.value;
                            
                            // Remove all non-digit separators, keep only digits
                            var digitsOnly = displayed.replace(/[^\d]/g, '');
                            
                            if (digitsOnly === '') {
                                input.dataset.hargaRawValue = '';
                                input.value = '';
                                return;
                            }
                            
                            // Convert digits string to number (assuming 2 decimal places)
                            var numValue = parseInt(digitsOnly, 10) / 100;
                            
                            // Store raw value
                            input.dataset.hargaRawValue = numValue;
                            
                            // Format and display
                            input.value = numValue.toLocaleString('id-ID', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            });
                            
                            // Move cursor to end
                            setTimeout(function() {
                                input.setSelectionRange(input.value.length, input.value.length);
                            }, 0);
                        };

                        input.addEventListener('input', handleInput);
                    });
                };

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
                    
                    // Use stored raw value from data attribute
                    var harga = 0;
                    if (hargaInput) {
                        var rawValue = hargaInput.dataset.hargaRawValue;
                        harga = rawValue !== '' && rawValue !== undefined ? parseFloat(rawValue) : 0;
                    }
                    
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
                        
                        // Use stored raw value from data attribute
                        var rawValue = hargaInput.dataset.hargaRawValue;
                        var harga = rawValue !== '' && rawValue !== undefined ? parseFloat(rawValue) : 0;
                        
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

                    var kategoriTab = container.getAttribute('data-kategori-tab');
                    if (kategoriTab === 'tangkapan-3-ton') {
                        var guardMessage = container.querySelector('.js-3ton-live-guard-message');
                        var submitButton = container.querySelector('button[type="submit"]');
                        var isExceeded = totalBerat > 3000;

                        if (guardMessage) {
                            guardMessage.classList.toggle('d-none', !isExceeded);
                        }

                        if (submitButton) {
                            submitButton.disabled = isExceeded;
                            submitButton.title = isExceeded ? 'Total berat maksimal 3000 kg.' : '';
                        }
                    }
                };

                var refreshAnglerIndexes = function(listElement) {
                    if (!listElement) {
                        return;
                    }

                    var tabKey = listElement.getAttribute('data-tab') || 'pancingan-pribadi';
                    var cards = Array.from(listElement.querySelectorAll('.js-angler-card'));

                    cards.forEach(function(card, anglerIndex) {
                        card.setAttribute('data-angler-index', String(anglerIndex));

                        var nameInput = card.querySelector('.js-angler-name');
                        if (nameInput) {
                            nameInput.name = 'anglers[' + anglerIndex + '][name]';
                        }

                        var rows = Array.from(card.querySelectorAll('.js-angler-item-row'));
                        rows.forEach(function(row, itemIndex) {
                            row.setAttribute('data-item-index', String(itemIndex));

                            var selectInput = row.querySelector('.js-angler-fish-select');
                            var beratInput = row.querySelector('.js-berat-input');
                            var hargaInput = row.querySelector('.js-harga-input');
                            var nilaiOutput = row.querySelector('.js-nilai-output');
                            var outputId = 'nilai_' + tabKey + '_' + anglerIndex + '_' + itemIndex;

                            if (selectInput) {
                                selectInput.name =
                                    'anglers[' + anglerIndex + '][items][' + itemIndex + '][id_ikan_tangkapan]';
                            }

                            if (beratInput) {
                                beratInput.name = 'anglers[' + anglerIndex + '][items][' + itemIndex + '][berat]';
                                beratInput.setAttribute('data-target', '#' + outputId);
                            }

                            if (hargaInput) {
                                hargaInput.name =
                                    'anglers[' + anglerIndex + '][items][' + itemIndex + '][harga_per_kg]';
                                hargaInput.setAttribute('data-target', '#' + outputId);
                            }

                            if (nilaiOutput) {
                                nilaiOutput.id = outputId;
                            }
                        });

                        var removeAnglerBtn = card.querySelector('.js-remove-angler');
                        if (removeAnglerBtn) {
                            removeAnglerBtn.style.display = cards.length > 1 ? '' : 'none';
                        }
                    });
                };

                var addAnglerCard = function(listElement) {
                    if (!listElement) {
                        return;
                    }

                    var tabKey = listElement.getAttribute('data-tab');
                    var template = document.getElementById('js-angler-template-' + tabKey);

                    if (!template) {
                        return;
                    }

                    var nextIndex = listElement.querySelectorAll('.js-angler-card').length;
                    var html = template.innerHTML.replace(/__A__/g, String(nextIndex));
                    listElement.insertAdjacentHTML('beforeend', html);

                    if (window.rupiahInput && typeof window.rupiahInput.init === 'function') {
                        window.rupiahInput.init(listElement);
                    }

                    refreshAnglerIndexes(listElement);
                };

                var addAnglerItemRow = function(cardElement) {
                    if (!cardElement) {
                        return;
                    }

                    var tbody = cardElement.querySelector('.js-angler-item-list');
                    var firstRow = tbody ? tbody.querySelector('.js-angler-item-row') : null;

                    if (!tbody || !firstRow) {
                        return;
                    }

                    var rowClone = firstRow.cloneNode(true);
                    var selectInput = rowClone.querySelector('.js-angler-fish-select');
                    var beratInput = rowClone.querySelector('.js-berat-input');
                    var hargaInput = rowClone.querySelector('.js-harga-input');
                    var nilaiOutput = rowClone.querySelector('.js-nilai-output');

                    if (selectInput) {
                        selectInput.value = '';
                    }

                    if (beratInput) {
                        beratInput.value = '';
                    }

                    if (hargaInput) {
                        hargaInput.value = '';
                    }

                    if (nilaiOutput) {
                        nilaiOutput.textContent = 'Rp 0';
                    }

                    tbody.appendChild(rowClone);

                    if (window.rupiahInput && typeof window.rupiahInput.init === 'function') {
                        window.rupiahInput.init(cardElement);
                    }

                    var anglerList = cardElement.closest('.js-angler-list');
                    if (anglerList) {
                        refreshAnglerIndexes(anglerList);
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

                document.querySelectorAll('.js-angler-list').forEach(function(listElement) {
                    refreshAnglerIndexes(listElement);
                });

                initHargaFormatter();

                document.addEventListener('click', function(event) {
                    var addAnglerBtn = event.target.closest('.js-add-angler');
                    if (addAnglerBtn) {
                        event.preventDefault();
                        var tabKey = addAnglerBtn.getAttribute('data-tab');
                        var listElement = document.querySelector('.js-angler-list[data-tab="' + tabKey + '"]');
                        addAnglerCard(listElement);

                        var pane = addAnglerBtn.closest('.js-trip-pane');
                        if (pane) {
                            updateCategorySubtotal(pane);
                        }

                        return;
                    }

                    var removeAnglerBtn = event.target.closest('.js-remove-angler');
                    if (removeAnglerBtn) {
                        event.preventDefault();
                        var listElement = removeAnglerBtn.closest('.js-angler-list');
                        var card = removeAnglerBtn.closest('.js-angler-card');

                        if (listElement && card) {
                            var cards = listElement.querySelectorAll('.js-angler-card');
                            if (cards.length > 1) {
                                card.remove();
                                refreshAnglerIndexes(listElement);
                            }
                        }

                        return;
                    }

                    var addItemBtn = event.target.closest('.js-add-angler-item');
                    if (addItemBtn) {
                        event.preventDefault();
                        var card = addItemBtn.closest('.js-angler-card');
                        addAnglerItemRow(card);

                        var pane = addItemBtn.closest('.js-trip-pane');
                        if (pane) {
                            updateCategorySubtotal(pane);
                        }

                        return;
                    }

                    var removeItemBtn = event.target.closest('.js-remove-angler-item');
                    if (removeItemBtn) {
                        event.preventDefault();
                        var card = removeItemBtn.closest('.js-angler-card');
                        var tbody = card ? card.querySelector('.js-angler-item-list') : null;
                        var row = removeItemBtn.closest('.js-angler-item-row');

                        if (tbody && row) {
                            var rows = tbody.querySelectorAll('.js-angler-item-row');
                            if (rows.length > 1) {
                                row.remove();
                            } else {
                                var selectInput = row.querySelector('.js-angler-fish-select');
                                var beratInput = row.querySelector('.js-berat-input');
                                var hargaInput = row.querySelector('.js-harga-input');
                                var nilaiOutput = row.querySelector('.js-nilai-output');

                                if (selectInput) {
                                    selectInput.value = '';
                                }
                                if (beratInput) {
                                    beratInput.value = '';
                                }
                                if (hargaInput) {
                                    hargaInput.value = '';
                                }
                                if (nilaiOutput) {
                                    nilaiOutput.textContent = 'Rp 0';
                                }
                            }

                            var anglerList = card.closest('.js-angler-list');
                            if (anglerList) {
                                refreshAnglerIndexes(anglerList);
                            }
                        }

                        var pane = removeItemBtn.closest('.js-trip-pane');
                        if (pane) {
                            updateCategorySubtotal(pane);
                        }
                    }
                });

                document.querySelectorAll('.js-berat-input, .js-harga-input').forEach(function(input) {
                    var targetSelector = input.getAttribute('data-target');
                    if (targetSelector) {
                        updateEstimatedValue(targetSelector);
                    }

                    var pane = input.closest('.js-trip-pane');
                    if (pane) {
                        updateCategorySubtotal(pane);
                    }
                });

                document.addEventListener('input', function(event) {
                    var input = event.target.closest('.js-berat-input, .js-harga-input');
                    if (!input) {
                        return;
                    }

                    var targetSelector = input.getAttribute('data-target');
                    if (targetSelector) {
                        updateEstimatedValue(targetSelector);
                    }

                    var currentPane = input.closest('.js-trip-pane');
                    if (currentPane) {
                        updateCategorySubtotal(currentPane);
                    }
                });

                document.querySelectorAll('.js-trip-pane').forEach(function(pane) {
                    updateCategorySubtotal(pane);
                });

                var closePaymentMethodInputs = document.querySelectorAll('.js-close-payment-method');
                var closeBayarTunaiInput = document.getElementById('close_bayar_tunai');
                var closeBayarTransferInput = document.getElementById('close_bayar_transfer');
                var closeAllocatedOutput = document.querySelector('.js-close-allocated-total');
                var closeRemainingOutput = document.querySelector('.js-close-remaining-total');
                var closeConfirmButton = document.querySelector('.js-confirm-close-button');
                var closeGrandTotal = parseFloat(
                    '{{ number_format((float) ($rekapGrandTotals['grand_total_semua_komponen'] ?? 0), 2, '.', '') }}'
                );
                var hasOldClosePayment =
                    {{ old('payment_method') !== null || old('bayar_tunai') !== null || old('bayar_transfer') !== null ? 'true' : 'false' }};
                var shouldOpenCloseModal =
                    {{ $errors->has('payment_method') || $errors->has('bayar_tunai') || $errors->has('bayar_transfer') ? 'true' : 'false' }};

                var formatRupiah = function(value) {
                    return 'Rp ' + value.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                var updateClosePaymentSummary = function() {
                    if (!closeAllocatedOutput || !closeRemainingOutput) {
                        return;
                    }

                    var totalTunai = parseFloat((closeBayarTunaiInput && closeBayarTunaiInput.value) || '0');
                    var totalTransfer = parseFloat((closeBayarTransferInput && closeBayarTransferInput.value) ||
                        '0');
                    totalTunai = isNaN(totalTunai) ? 0 : totalTunai;
                    totalTransfer = isNaN(totalTransfer) ? 0 : totalTransfer;

                    var allocated = totalTunai + totalTransfer;
                    var difference = closeGrandTotal - allocated;
                    var differenceText = (difference < 0 ? '- ' : '') + formatRupiah(Math.abs(difference));

                    closeAllocatedOutput.textContent = formatRupiah(allocated);
                    closeRemainingOutput.textContent = differenceText;
                    closeRemainingOutput.classList.remove('text-success', 'text-danger');
                    closeRemainingOutput.classList.add(Math.abs(difference) < 0.01 ? 'text-success' :
                        'text-danger');

                    if (closeConfirmButton) {
                        closeConfirmButton.disabled = closeGrandTotal > 0 && Math.abs(difference) > 0.01;
                    }
                };

                var syncClosePaymentMode = function(autoFill) {
                    if (!closeBayarTunaiInput || !closeBayarTransferInput || closePaymentMethodInputs.length ===
                        0) {
                        return;
                    }

                    var selectedMethodInput = document.querySelector('.js-close-payment-method:checked');
                    var selectedMethod = selectedMethodInput ? selectedMethodInput.value : 'cash';

                    closeBayarTunaiInput.disabled = selectedMethod === 'transfer';
                    closeBayarTransferInput.disabled = selectedMethod === 'cash';

                    if (autoFill) {
                        if (selectedMethod === 'cash') {
                            closeBayarTunaiInput.value = closeGrandTotal > 0 ? closeGrandTotal.toFixed(2) : '';
                            closeBayarTransferInput.value = '';
                        } else if (selectedMethod === 'transfer') {
                            closeBayarTunaiInput.value = '';
                            closeBayarTransferInput.value = closeGrandTotal > 0 ? closeGrandTotal.toFixed(2) : '';
                        } else if (selectedMethod === 'both' &&
                            ((parseFloat(closeBayarTunaiInput.value || '0') || 0) +
                                (parseFloat(closeBayarTransferInput.value || '0') || 0) <= 0)) {
                            closeBayarTunaiInput.value = '';
                            closeBayarTransferInput.value = '';
                        }
                    }

                    updateClosePaymentSummary();
                };

                closePaymentMethodInputs.forEach(function(input) {
                    input.addEventListener('change', function() {
                        syncClosePaymentMode(true);
                    });
                });

                [closeBayarTunaiInput, closeBayarTransferInput].forEach(function(input) {
                    if (!input) {
                        return;
                    }

                    input.addEventListener('input', updateClosePaymentSummary);
                });

                syncClosePaymentMode(!hasOldClosePayment);

                if (shouldOpenCloseModal && window.jQuery && typeof window.jQuery.fn.modal === 'function') {
                    window.jQuery('#closePelayaranModal').modal('show');
                }

                // Normalize harga fields before form submission
                document.querySelectorAll('form').forEach(function(form) {
                    form.addEventListener('submit', function() {
                        // Use stored raw values from data attributes
                        form.querySelectorAll('input[name*="[harga_"], input[name*="[harga_per_kg"]').forEach(function(input) {
                            var rawValue = input.dataset.hargaRawValue;
                            
                            if (rawValue === '' || rawValue === undefined) {
                                input.value = '';
                                return;
                            }
                            
                            // Send as numeric string with 2 decimals
                            var numValue = parseFloat(rawValue);
                            input.value = isNaN(numValue) ? '' : numValue.toFixed(2);
                        });
                    });
                });
            });
        </script>
    @endif
@endsection
