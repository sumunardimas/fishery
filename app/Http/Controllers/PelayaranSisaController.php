<?php

namespace App\Http\Controllers;

use App\Models\MasterIkanTangkapan;
use App\Models\Pelayaran;
use App\Services\PerbekalanFifoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PelayaranSisaController extends Controller
{
    private const TAB_PERBEKALAN = 'perbekalan';
    private const TAB_TANGKAPAN_PRIBADI = 'tangkapan-pribadi';
    private const TAB_TANGKAPAN_BERSAMA = 'tangkapan-bersama';
    private const TAB_TANGKAPAN_JARINGAN = 'tangkapan-jaringan';
    private const TAB_TANGKAPAN_3_TON = 'tangkapan-3-ton';
    private const TAB_OPERASIONAL = 'operasional';
    private const TAB_REKAP = 'rekap';

    private const KATEGORI_TANGKAPAN = [
        'pancingan_pribadi' => 'Pancingan Pribadi',
        'pancingan_bersama' => 'Pancingan Bersama',
        'jaringan' => 'Jaringan',
        'tangkapan_3_ton' => 'Tangkapan 3 Ton',
    ];

    private const TAB_TO_KATEGORI = [
        self::TAB_TANGKAPAN_PRIBADI => 'pancingan_pribadi',
        self::TAB_TANGKAPAN_BERSAMA => 'pancingan_bersama',
        self::TAB_TANGKAPAN_JARINGAN => 'jaringan',
        self::TAB_TANGKAPAN_3_TON => 'tangkapan_3_ton',
    ];

    private const PAYMENT_METHODS = ['cash', 'transfer', 'both'];
    private const JARINGAN_CREDIT_FACTOR = 0.5;
    private const TANGKAPAN_3_TON_MAX_BERAT = 3000;

    public function __construct(private readonly PerbekalanFifoService $perbekalanFifoService)
    {
    }

    /**
     * Show active pelayaran and consolidated closing wizard.
     */
    public function index(Request $request): View
    {
        $activePelayaran = Pelayaran::query()
            ->with('kapal')
            ->where('status_pelayaran', 'aktif')
            ->orderBy('tanggal_berangkat')
            ->get();

        $selectedPelayaranId = (int) ($request->integer('pelayaran_id') ?: ($activePelayaran->first()->id_pelayaran ?? 0));
        $activeTab = $this->normalizeActiveTab($request->string('tab')->toString());
        $selectedPelayaran = $activePelayaran->firstWhere('id_pelayaran', $selectedPelayaranId);

        return view('pelayaran.sisa.index', $this->buildIndexViewData(
            $activePelayaran,
            $selectedPelayaran,
            $activeTab,
            false
        ));
    }

    public function history(): View
    {
        $items = Pelayaran::query()
            ->with('kapal')
            ->where('status_pelayaran', 'selesai')
            ->orderByDesc('tanggal_selesai')
            ->orderByDesc('tanggal_tiba')
            ->orderByDesc('id_pelayaran')
            ->get();

        return view('pelayaran.sisa.history', compact('items'));
    }

    public function showHistoryDetail(Request $request, Pelayaran $pelayaran): View
    {
        abort_unless($pelayaran->status_pelayaran === 'selesai', 404);

        $pelayaran->load('kapal');
        $activeTab = $this->normalizeActiveTab($request->string('tab')->toString());

        return view('pelayaran.sisa.index', $this->buildIndexViewData(
            collect(),
            $pelayaran,
            $activeTab,
            true
        ));
    }

    public function report(Pelayaran $pelayaran): View
    {
        abort_unless($pelayaran->status_pelayaran === 'selesai', 404);

        $pelayaran->load('kapal');
        $viewData = $this->buildIndexViewData(collect(), $pelayaran, self::TAB_REKAP, true);

        $salesWindow = $this->buildVoyageSalesWindow($pelayaran);
        $catchBreakdown = $this->buildVoyageCatchBreakdown((int) $pelayaran->id_pelayaran);
        $hasTripLots = DB::table('stok_ikan_lots')
            ->where('id_pelayaran', (int) $pelayaran->id_pelayaran)
            ->exists();
        $salesBreakdown = $this->buildVoyageSalesActualBreakdown((int) $pelayaran->id_pelayaran);
        $salesAttributionMode = 'exact';

        // Fallback to estimate only for historical trips that do not yet have lot data.
        if (! $hasTripLots && $salesBreakdown->isEmpty()) {
            $salesBreakdown = $this->buildVoyageSalesEstimateBreakdown($catchBreakdown, $salesWindow);
            $salesAttributionMode = 'estimate';
        }

        $storageSnapshot = $this->buildVesselStorageSnapshot((int) $pelayaran->id_kapal);

        $salesByKey = $salesBreakdown->keyBy('commodity_key');
        $storageByKey = $storageSnapshot->keyBy('commodity_key');

        $reportRows = $catchBreakdown->map(function ($row) use ($salesByKey, $storageByKey) {
            $saleRow = $salesByKey->get($row->commodity_key);
            $storageRow = $storageByKey->get($row->commodity_key);
            $catchWeight = (float) $row->catch_weight;
            $catchValue = (float) $row->catch_value;
            $salesWeight = (float) ($saleRow->sales_weight ?? 0);
            $salesValue = (float) ($saleRow->sales_value ?? 0);
            $currentStorageWeight = (float) ($storageRow->current_storage_weight ?? 0);

            return (object) [
                'commodity_key' => $row->commodity_key,
                'commodity_name' => $row->commodity_name,
                'catch_weight' => $catchWeight,
                'catch_value' => $catchValue,
                'sales_weight' => $salesWeight,
                'sales_value' => $salesValue,
                'weight_gap' => $catchWeight - $salesWeight,
                'value_gap' => $catchValue - $salesValue,
                'current_storage_weight' => $currentStorageWeight,
            ];
        })->values();

        $tripCostTotal = (float) ($viewData['rekapGrandTotals']['total_perbekalan_terpakai'] ?? 0)
            + (float) ($viewData['rekapGrandTotals']['total_operasional'] ?? 0);
        $salesTotal = (float) $reportRows->sum('sales_value');
        $tripCatchTotal = (float) $reportRows->sum('catch_value');

        $reportSummary = [
            'trip_catch_total' => $tripCatchTotal,
            'trip_cost_total' => $tripCostTotal,
            'sales_total' => $salesTotal,
            'sales_net' => $salesTotal - $tripCostTotal,
            'current_storage_total_weight' => (float) $reportRows->sum('current_storage_weight'),
        ];

        return view('pelayaran.laporan.show', array_merge($viewData, compact(
            'pelayaran',
            'salesWindow',
            'salesAttributionMode',
            'reportRows',
            'reportSummary'
        )));
    }

    /**
     * Backward compatible endpoint: save all sections and close trip.
     */
    public function store(Request $request): RedirectResponse
    {
        return $this->closePelayaran($request);
    }

    public function storePerbekalan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_pelayaran' => ['required', 'integer', 'exists:pelayaran,id_pelayaran'],
            'sisa_qty' => ['nullable', 'array'],
            'sisa_qty.*' => ['nullable', 'numeric', 'min:0'],
            'catatan_sisa' => ['nullable', 'string'],
        ]);

        $idPelayaran = (int) $validated['id_pelayaran'];
        $pelayaran = $this->getActivePelayaranOrFail($idPelayaran);

        $plannedPerbekalan = DB::table('perbekalan_pelayaran')
            ->where('id_pelayaran', $idPelayaran)
            ->pluck('jumlah', 'id_barang')
            ->map(fn ($value) => (float) $value);

        $sisaQty = $this->extractNumericMap($request->input('sisa_qty', []), true)
            ->only($plannedPerbekalan->keys()->all());

        foreach ($sisaQty as $idBarang => $nilaiSisa) {
            $jumlahAwal = (float) ($plannedPerbekalan[$idBarang] ?? 0);
            if ($nilaiSisa > $jumlahAwal) {
                return back()
                    ->withInput()
                    ->withErrors(['message' => "Sisa barang tidak boleh lebih besar dari jumlah awal untuk id_barang {$idBarang}."]);
            }
        }

        DB::transaction(function () use ($idPelayaran, $sisaQty, $validated) {
            DB::table('sisa_trip')->where('id_pelayaran', $idPelayaran)->delete();

            if ($sisaQty->isNotEmpty()) {
                $satuanMap = DB::table('master_perbekalan')
                    ->whereIn('id_barang', $sisaQty->keys()->all())
                    ->pluck('satuan', 'id_barang');

                $now = now();
                $rows = $sisaQty->map(function (float $jumlahSisa, int $idBarang) use ($idPelayaran, $satuanMap, $validated, $now) {
                    return [
                        'id_pelayaran' => $idPelayaran,
                        'id_barang' => $idBarang,
                        'jumlah_sisa' => $jumlahSisa,
                        'satuan' => $satuanMap[$idBarang] ?? '-',
                        'keterangan' => $validated['catatan_sisa'] ?? 'Pencatatan sisa trip',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->values()->all();

                DB::table('sisa_trip')->insert($rows);
            }
        });

        return redirect()
            ->route('pelayaran.sisa.index', ['pelayaran_id' => $pelayaran->id_pelayaran, 'tab' => self::TAB_PERBEKALAN])
            ->with('success', 'Card Sisa Perbekalan berhasil disimpan.');
    }

    public function storeTangkapan(Request $request): RedirectResponse
    {
        $kategoriTangkapanMap = self::KATEGORI_TANGKAPAN;
        $validated = $request->validate([
            'id_pelayaran' => ['required', 'integer', 'exists:pelayaran,id_pelayaran'],
            'kategori_tangkapan' => ['required', 'string', 'in:' . implode(',', array_keys($kategoriTangkapanMap))],
            'hasil_ikan' => ['nullable', 'array'],
            'hasil_ikan.*' => ['nullable', 'numeric', 'min:0'],
            'harga_ikan' => ['nullable', 'array'],
            'harga_ikan.*' => ['nullable', 'numeric', 'min:0'],
            'anglers' => ['nullable', 'array'],
            'anglers.*.name' => ['nullable', 'string', 'max:120'],
            'anglers.*.items' => ['nullable', 'array'],
            'anglers.*.items.*.id_ikan_tangkapan' => ['nullable', 'integer'],
            'anglers.*.items.*.berat' => ['nullable', 'numeric', 'min:0'],
            'anglers.*.items.*.harga_per_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        $idPelayaran = (int) $validated['id_pelayaran'];
        $pelayaran = $this->getActivePelayaranOrFail($idPelayaran);
        $kategoriTangkapan = (string) $validated['kategori_tangkapan'];

        $hasilIkan = collect();
        $hargaIkan = collect();
        $insertRows = collect();

        $ikanTangkapanCollection = MasterIkanTangkapan::query()
            ->whereHas('masterIkan')
            ->with(['masterIkan' => function ($query) {
                $query->select('id_ikan', 'id_ikan_tangkapan')->orderBy('id_ikan');
            }])
            ->get();

        $validIkanTangkapanIds = $ikanTangkapanCollection->pluck('id_ikan_tangkapan')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($kategoriTangkapan !== 'pancingan_pribadi') {
            $hasilIkan = $this->extractNumericMap($request->input('hasil_ikan', []), false)
                ->only($validIkanTangkapanIds)
                ->filter(fn (float $berat) => $berat > 0);
            $hargaIkan = $this->extractNumericMap($request->input('harga_ikan', []), true)
                ->only($validIkanTangkapanIds);
        }

        $relasiIkanMap = $ikanTangkapanCollection->mapWithKeys(function (MasterIkanTangkapan $item) {
            $representativeIkan = $item->masterIkan->sortBy('id_ikan')->first();

            return $representativeIkan
                ? [(int) $item->id_ikan_tangkapan => (int) $representativeIkan->id_ikan]
                : [];
        })->all();

        if ($kategoriTangkapan === 'pancingan_pribadi') {
            $insertRows = $this->extractPersonalCatchRows(
                anglersInput: $request->input('anglers', []),
                validIkanTangkapanIds: $validIkanTangkapanIds,
                relasiIkanMap: $relasiIkanMap
            );

            if ($insertRows->isEmpty()) {
                throw ValidationException::withMessages([
                    'anglers' => 'Isi minimal satu nama penangkap dengan item ikan (berat > 0).',
                ]);
            }
        } else {
            $unmappedIds = $hasilIkan->keys()
                ->filter(fn (int $idIkanTangkapan) => !isset($relasiIkanMap[$idIkanTangkapan]))
                ->values()
                ->all();

            if ($unmappedIds !== []) {
                throw ValidationException::withMessages([
                    'hasil_ikan' => 'Beberapa Master Ikan Tangkapan belum terhubung ke Master Ikan penjualan. Lengkapi relasinya terlebih dahulu.',
                ]);
            }

            $hasilIkan = $hasilIkan->mapWithKeys(function (float $beratHasil, int $idIkanTangkapan) use ($relasiIkanMap) {
                return [(int) $relasiIkanMap[$idIkanTangkapan] => $beratHasil];
            });

            if ($kategoriTangkapan === 'tangkapan_3_ton') {
                $totalBerat3Ton = (float) $hasilIkan->sum();
                if ($totalBerat3Ton > self::TANGKAPAN_3_TON_MAX_BERAT) {
                    throw ValidationException::withMessages([
                        'hasil_ikan' => 'Total berat Tangkapan 3 Ton maksimal ' . self::TANGKAPAN_3_TON_MAX_BERAT . ' kg.',
                    ]);
                }
            }

            $hargaIkan = $hargaIkan->mapWithKeys(function (float $harga, int $idIkanTangkapan) use ($relasiIkanMap) {
                if (!isset($relasiIkanMap[$idIkanTangkapan])) {
                    return [];
                }

                return [(int) $relasiIkanMap[$idIkanTangkapan] => $harga];
            });

            $insertRows = $hasilIkan->map(function (float $beratHasil, int $idIkan) use ($hargaIkan) {
                return [
                    'id_ikan' => $idIkan,
                    'berat_hasil' => $beratHasil,
                    'harga_per_kg' => (float) ($hargaIkan[$idIkan] ?? 0),
                    'nama_penangkap' => '',
                ];
            })->values();
        }

        DB::transaction(function () use ($idPelayaran, $kategoriTangkapan, $insertRows) {
            $existingIkanIds = DB::table('ikan_hasil_pelayaran')
                ->where('id_pelayaran', $idPelayaran)
                ->where('kategori_tangkapan', $kategoriTangkapan)
                ->pluck('id_ikan')
                ->map(fn ($id) => (int) $id)
                ->all();

            DB::table('ikan_hasil_pelayaran')
                ->where('id_pelayaran', $idPelayaran)
                ->where('kategori_tangkapan', $kategoriTangkapan)
                ->delete();

            if ($insertRows->isNotEmpty()) {
                $now = now();
                $hasilRows = $insertRows->map(function (array $row) use ($idPelayaran, $kategoriTangkapan, $now) {
                    return [
                        'id_pelayaran' => $idPelayaran,
                        'id_ikan' => (int) $row['id_ikan'],
                        'kategori_tangkapan' => $kategoriTangkapan,
                        'berat_hasil' => (float) $row['berat_hasil'],
                        'harga_per_kg' => (float) ($row['harga_per_kg'] ?? 0),
                        'nama_penangkap' => (string) ($row['nama_penangkap'] ?? ''),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->values()->all();

                DB::table('ikan_hasil_pelayaran')->insert($hasilRows);
            }

            $periode = now()->format('Y-m');
            $affectedIkanIds = collect($existingIkanIds)
                ->merge($insertRows->pluck('id_ikan')->map(fn ($id) => (int) $id)->all())
                ->unique()
                ->values()
                ->all();
            $this->recalculateStokIkan($periode, $affectedIkanIds);
        });

        $tab = array_search($kategoriTangkapan, self::TAB_TO_KATEGORI, true) ?: self::TAB_TANGKAPAN_PRIBADI;

        return redirect()
            ->route('pelayaran.sisa.index', ['pelayaran_id' => $pelayaran->id_pelayaran, 'tab' => $tab])
            ->with('success', 'Data tangkapan kategori ' . ($kategoriTangkapanMap[$kategoriTangkapan] ?? $kategoriTangkapan) . ' berhasil disimpan.');
    }

    public function storeOperasional(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_pelayaran' => ['required', 'integer', 'exists:pelayaran,id_pelayaran'],
            'tanggal' => ['required', 'array'],
            'tanggal.*' => ['nullable', 'date'],
            'jumlah' => ['required', 'array'],
            'jumlah.*' => ['nullable', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'array'],
            'deskripsi.*' => ['nullable', 'string'],
        ]);

        $pelayaran = $this->getActivePelayaranOrFail((int) $data['id_pelayaran']);

        $masterOperasional = DB::table('master_operasional')
            ->select('id_master_operasional', 'nama_operasional', 'deskripsi')
            ->get()
            ->keyBy('id_master_operasional');

        $tanggalMap = $data['tanggal'] ?? [];
        $jumlahMap = $data['jumlah'] ?? [];
        $deskripsiMap = $data['deskripsi'] ?? [];

        $records = [];
        $defaultTanggal = now()->toDateString();

        foreach ($masterOperasional as $masterId => $master) {
            $jumlahRaw = $jumlahMap[$masterId] ?? null;
            $jumlah = $jumlahRaw === null || $jumlahRaw === '' ? 0 : (float) $jumlahRaw;

            if ($jumlah <= 0) {
                continue;
            }

            $tanggal = $tanggalMap[$masterId] ?? null;
            $tanggal = $tanggal ?: $defaultTanggal;

            $deskripsi = $deskripsiMap[$masterId] ?? null;

            $records[] = [
                'id_pelayaran' => (int) $data['id_pelayaran'],
                'id_master_operasional' => (int) $masterId,
                'jenis_biaya' => $master->nama_operasional,
                'deskripsi' => $deskripsi ?: ($master->deskripsi ?? '-'),
                'jumlah' => $jumlah,
                'tanggal' => $tanggal,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($records === []) {
            throw ValidationException::withMessages([
                'jumlah' => 'Isi minimal satu biaya operasional dengan jumlah > 0.',
            ]);
        }

        DB::transaction(function () use ($data, $records) {
            DB::table('operasional')
                ->where('id_pelayaran', (int) $data['id_pelayaran'])
                ->delete();

            DB::table('operasional')->insert($records);
        });

        return redirect()
            ->route('pelayaran.sisa.index', ['pelayaran_id' => $pelayaran->id_pelayaran, 'tab' => self::TAB_OPERASIONAL])
            ->with('success', 'Data Operasional Trip berhasil disimpan.');
    }

    public function closePelayaran(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_pelayaran' => ['required', 'integer', 'exists:pelayaran,id_pelayaran'],
            'payment_method' => ['nullable', 'string', 'in:' . implode(',', self::PAYMENT_METHODS)],
            'bayar_tunai' => ['nullable', 'numeric', 'min:0'],
            'bayar_transfer' => ['nullable', 'numeric', 'min:0'],
        ]);

        $idPelayaran = (int) $validated['id_pelayaran'];
        $pelayaran = $this->getActivePelayaranOrFail($idPelayaran);

        $plannedPerbekalanCount = DB::table('perbekalan_pelayaran')
            ->where('id_pelayaran', $idPelayaran)
            ->count();
        $sisaTripCount = DB::table('sisa_trip')
            ->where('id_pelayaran', $idPelayaran)
            ->count();
        $hasilIkanCount = DB::table('ikan_hasil_pelayaran')
            ->where('id_pelayaran', $idPelayaran)
            ->count();
        $kategoriTangkapanCount = DB::table('ikan_hasil_pelayaran')
            ->where('id_pelayaran', $idPelayaran)
            ->distinct('kategori_tangkapan')
            ->count('kategori_tangkapan');
        $masterIkanCount = DB::table('master_ikan_tangkapan')->count();
        $operasionalCount = DB::table('operasional')
            ->where('id_pelayaran', $idPelayaran)
            ->count();
        $masterOperasionalCount = DB::table('master_operasional')->count();

        $missing = [];
        if ($plannedPerbekalanCount > 0 && $sisaTripCount < $plannedPerbekalanCount) {
            $missing[] = 'Card Sisa Perbekalan';
        }
        if ($masterIkanCount > 0 && ($hasilIkanCount === 0 || $kategoriTangkapanCount < count(self::KATEGORI_TANGKAPAN))) {
            $missing[] = 'Card Hasil Tangkapan Ikan';
        }
        if ($masterOperasionalCount > 0 && $operasionalCount === 0) {
            $missing[] = 'Operasional Trip';
        }

        if ($missing !== []) {
            return redirect()
                ->route('pelayaran.sisa.index', ['pelayaran_id' => $idPelayaran, 'tab' => self::TAB_PERBEKALAN])
                ->withErrors([
                    'message' => 'Tidak bisa menutup pelayaran. Lengkapi terlebih dahulu: ' . implode(', ', $missing) . '.',
                ]);
        }

        $closingCashflow = $this->buildClosingCashflowSummary($pelayaran);
        $paymentAllocation = $this->normalizeClosePaymentAllocation(
            grandTotal: (float) $closingCashflow['grand_total'],
            paymentMethod: (string) ($validated['payment_method'] ?? ''),
            bayarTunai: (float) ($validated['bayar_tunai'] ?? 0),
            bayarTransfer: (float) ($validated['bayar_transfer'] ?? 0)
        );

        DB::transaction(function () use ($pelayaran, $closingCashflow, $paymentAllocation) {
            $lockedClosingCashflow = $this->buildClosingCashflowSummary($pelayaran);

            if (abs((float) $lockedClosingCashflow['grand_total'] - (float) $closingCashflow['grand_total']) > 0.01) {
                throw ValidationException::withMessages([
                    'message' => 'Nilai penutupan trip berubah karena histori stok perbekalan berubah. Muat ulang halaman rekap lalu coba tutup pelayaran lagi.',
                ]);
            }

            $this->perbekalanFifoService->recordTripConsumption(
                pelayaran: $pelayaran,
                usageSummary: $lockedClosingCashflow['perbekalan_summary'],
                transactionDate: now()->toDateString()
            );

            $this->storeCatchToKapalStorage($pelayaran);

            if ((float) $lockedClosingCashflow['grand_total'] > 0) {
                $this->postClosingCashflowEntries(
                    pelayaran: $pelayaran,
                    components: $lockedClosingCashflow['components'],
                    bayarTunai: (float) $paymentAllocation['bayar_tunai'],
                    bayarTransfer: (float) $paymentAllocation['bayar_transfer']
                );
            }

            $pelayaran->update([
                'status_pelayaran' => 'selesai',
                'tanggal_selesai' => now()->toDateString(),
            ]);
        });

        $successMessage = 'Pelayaran berhasil ditutup.';

        if ((float) $closingCashflow['grand_total'] > 0) {
            $successMessage .= ' Kredit arus kas tercatat: Kas Rp '
                . number_format((float) $paymentAllocation['bayar_tunai'], 2, ',', '.')
                . ' dan Transfer Rp '
                . number_format((float) $paymentAllocation['bayar_transfer'], 2, ',', '.')
                . '.';
        }

        return redirect()
            ->route('pelayaran.index')
            ->with('success', $successMessage);
    }

    private function buildClosingCashflowSummary(Pelayaran $pelayaran): array
    {
        $pelayaran->loadMissing('kapal');

        $idPelayaran = (int) $pelayaran->id_pelayaran;
        $tripLabel = 'Trip #'.$idPelayaran.' - '.($pelayaran->kapal->nama_kapal ?? ('Kapal #'.$pelayaran->id_kapal));
        $rekapPerbekalan = $this->perbekalanFifoService->buildTripUsageSummary($pelayaran);

        $rekapTangkapan = DB::table('ikan_hasil_pelayaran')
            ->where('id_pelayaran', $idPelayaran)
            ->selectRaw('kategori_tangkapan, SUM(berat_hasil) as total_berat, SUM(berat_hasil * COALESCE(harga_per_kg, 0)) as total_nilai')
            ->groupBy('kategori_tangkapan')
            ->get()
            ->keyBy('kategori_tangkapan');

        $rekapOperasional = DB::table('operasional')
            ->where('id_pelayaran', $idPelayaran)
            ->selectRaw('COUNT(*) as total_item_biaya, COALESCE(SUM(jumlah), 0) as total_biaya')
            ->first();

        $components = collect();

        $totalPerbekalan = (float) $rekapPerbekalan->sum('total_biaya');
        if ($totalPerbekalan > 0) {
            $components->push([
                'category' => 'Penutupan Trip - Perbekalan Terpakai',
                'amount' => $totalPerbekalan,
                'description' => $tripLabel.' | Pemakaian perbekalan terpakai '.(int) $rekapPerbekalan->filter(fn ($row) => (float) $row->jumlah_terpakai > 0)->count().' item.',
            ]);
        }

        foreach (self::KATEGORI_TANGKAPAN as $kategoriKey => $label) {
            $row = $rekapTangkapan->get($kategoriKey);
            $amount = $this->calculateCatchCreditAmount($kategoriKey, (float) ($row->total_nilai ?? 0));

            if ($amount <= 0) {
                continue;
            }

            $isJaringan = $kategoriKey === 'jaringan';
            $factorPercent = (int) round(self::JARINGAN_CREDIT_FACTOR * 100);
            $components->push([
                'category' => 'Penutupan Trip - '.$label.($isJaringan ? ' (Dikreditkan '.$factorPercent.'%)' : ''),
                'amount' => $amount,
                'description' => $tripLabel.' | Pembelian hasil '.$label.' total '.number_format((float) ($row->total_berat ?? 0), 2, ',', '.').' kg.'.($isJaringan ? ' Nominal kredit memakai '.$factorPercent.'% dari nilai Jaringan.' : ''),
            ]);
        }

        $totalOperasional = (float) ($rekapOperasional->total_biaya ?? 0);
        if ($totalOperasional > 0) {
            $components->push([
                'category' => 'Penutupan Trip - Operasional',
                'amount' => $totalOperasional,
                'description' => $tripLabel.' | Total '.(int) ($rekapOperasional->total_item_biaya ?? 0).' item biaya operasional trip.',
            ]);
        }

        return [
            'components' => $components->values()->all(),
            'grand_total' => (float) $components->sum('amount'),
            'perbekalan_summary' => $rekapPerbekalan,
        ];
    }

    private function normalizeClosePaymentAllocation(float $grandTotal, string $paymentMethod, float $bayarTunai, float $bayarTransfer): array
    {
        $grandTotal = round($grandTotal, 2);
        $bayarTunai = round($bayarTunai, 2);
        $bayarTransfer = round($bayarTransfer, 2);

        if ($grandTotal <= 0) {
            return [
                'payment_method' => $paymentMethod ?: 'cash',
                'bayar_tunai' => 0,
                'bayar_transfer' => 0,
            ];
        }

        if (! in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            if ($bayarTunai > 0 && $bayarTransfer > 0) {
                $paymentMethod = 'both';
            } elseif ($bayarTransfer > 0) {
                $paymentMethod = 'transfer';
            } else {
                $paymentMethod = 'cash';
            }
        }

        if ($paymentMethod === 'cash') {
            $bayarTunai = $grandTotal;
            $bayarTransfer = 0;
        } elseif ($paymentMethod === 'transfer') {
            $bayarTunai = 0;
            $bayarTransfer = $grandTotal;
        } elseif ($bayarTunai <= 0 || $bayarTransfer <= 0) {
            throw ValidationException::withMessages([
                'bayar_tunai' => 'Untuk metode gabungan, isi nominal kas dan transfer lebih dari 0.',
            ]);
        }

        $allocatedTotal = round($bayarTunai + $bayarTransfer, 2);
        if (abs($allocatedTotal - $grandTotal) > 0.01) {
            throw ValidationException::withMessages([
                'bayar_tunai' => 'Total pembayaran harus sama dengan grand total penutupan trip (Rp '.number_format($grandTotal, 2, ',', '.').').',
            ]);
        }

        return [
            'payment_method' => $paymentMethod,
            'bayar_tunai' => $bayarTunai,
            'bayar_transfer' => $bayarTransfer,
        ];
    }

    private function postClosingCashflowEntries(Pelayaran $pelayaran, array $components, float $bayarTunai, float $bayarTransfer): void
    {
        $remainingCash = round($bayarTunai, 2);
        $remainingTransfer = round($bayarTransfer, 2);
        $tanggal = now()->toDateString();
        $componentCount = count($components);

        foreach ($components as $index => $component) {
            $amount = round((float) ($component['amount'] ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $allocation = $this->allocateClosingAmountByAccount(
                amount: $amount,
                remainingCash: $remainingCash,
                remainingTransfer: $remainingTransfer,
                isLastComponent: $index === ($componentCount - 1)
            );

            if ($allocation['kas'] > 0) {
                $this->postArusKas(
                    akun: 'kas',
                    tanggal: $tanggal,
                    kategori: (string) ($component['category'] ?? 'Penutupan Trip'),
                    deskripsi: trim((string) ($component['description'] ?? 'Penutupan trip')).' | Dibayar via kas.',
                    debit: 0,
                    kredit: (float) $allocation['kas']
                );
            }

            if ($allocation['bank'] > 0) {
                $this->postArusKas(
                    akun: 'bank',
                    tanggal: $tanggal,
                    kategori: (string) ($component['category'] ?? 'Penutupan Trip'),
                    deskripsi: trim((string) ($component['description'] ?? 'Penutupan trip')).' | Dibayar via transfer.',
                    debit: 0,
                    kredit: (float) $allocation['bank']
                );
            }
        }
    }

    private function allocateClosingAmountByAccount(float $amount, float &$remainingCash, float &$remainingTransfer, bool $isLastComponent = false): array
    {
        $amount = round($amount, 2);
        $cashPortion = 0.0;
        $transferPortion = 0.0;

        if ($amount <= 0) {
            return ['kas' => 0, 'bank' => 0];
        }

        if ($isLastComponent) {
            $cashPortion = min($amount, $remainingCash);
            $transferPortion = round($amount - $cashPortion, 2);
        } else {
            $remainingGrandTotal = round($remainingCash + $remainingTransfer, 2);
            $cashRatio = $remainingGrandTotal > 0 ? ($remainingCash / $remainingGrandTotal) : 0;

            $cashPortion = round($amount * $cashRatio, 2);
            $cashPortion = min($cashPortion, $remainingCash, $amount);
            $transferPortion = round($amount - $cashPortion, 2);

            if ($transferPortion > $remainingTransfer) {
                $transferPortion = min($remainingTransfer, $amount);
                $cashPortion = round($amount - $transferPortion, 2);
            }

            if ($cashPortion > $remainingCash) {
                $cashPortion = min($remainingCash, $amount);
                $transferPortion = round($amount - $cashPortion, 2);
            }
        }

        $remainingCash = round(max(0, $remainingCash - $cashPortion), 2);
        $remainingTransfer = round(max(0, $remainingTransfer - $transferPortion), 2);

        return [
            'kas' => $cashPortion,
            'bank' => $transferPortion,
        ];
    }

    private function normalizeActiveTab(string $activeTab): string
    {
        if ($activeTab === 'tangkapan') {
            $activeTab = self::TAB_TANGKAPAN_PRIBADI;
        }

        $validTabs = [
            self::TAB_PERBEKALAN,
            self::TAB_TANGKAPAN_PRIBADI,
            self::TAB_TANGKAPAN_BERSAMA,
            self::TAB_TANGKAPAN_JARINGAN,
            self::TAB_TANGKAPAN_3_TON,
            self::TAB_OPERASIONAL,
            self::TAB_REKAP,
        ];

        return in_array($activeTab, $validTabs, true)
            ? $activeTab
            : self::TAB_PERBEKALAN;
    }

    private function buildIndexViewData(Collection $activePelayaran, ?Pelayaran $selectedPelayaran, string $activeTab, bool $isReadOnly = false): array
    {
        $perbekalanRows = collect();
        $existingSisa = [];
        $masterIkanTangkapan = MasterIkanTangkapan::query()
            ->whereHas('masterIkan')
            ->with(['masterIkan' => function ($query) {
                $query->select('id_ikan', 'id_ikan_tangkapan', 'nama_ikan')
                    ->orderBy('nama_ikan');
            }])
            ->orderBy('nama_ikan_tangkapan')
            ->get();
        $existingHasilIkanByKategori = [];
        $existingPersonalAnglers = [];
        $masterOperasional = DB::table('master_operasional')
            ->orderBy('nama_operasional')
            ->get();
        $existingOperasional = [];
        $existingOperasionalTanggal = [];
        $rekapOperasional = [
            'total_item_biaya' => 0,
            'total_biaya' => 0,
            'detail' => collect(),
        ];
        $completionStatus = [
            'perbekalan' => false,
            'tangkapan' => false,
            'operasional' => false,
        ];
        $rekapTangkapan = collect();
        $rekapPerbekalan = collect();
        $rekapGrandTotals = [
            'item_perbekalan_terpakai' => 0,
            'total_perbekalan_terpakai' => 0,
            'total_tangkapan' => 0,
            'total_tangkapan_bruto' => 0,
            'total_operasional' => 0,
            'grand_total_semua_komponen' => 0,
            'estimasi_selisih_bersih' => 0,
            'total_jaringan_bruto' => 0,
            'total_jaringan_dikreditkan' => 0,
            'total_jaringan_bagi_hasil' => 0,
            'jaringan_credit_factor' => self::JARINGAN_CREDIT_FACTOR,
        ];

        if ($selectedPelayaran) {
            $perbekalanRows = DB::table('perbekalan_pelayaran as pp')
                ->join('master_perbekalan as mp', 'mp.id_barang', '=', 'pp.id_barang')
                ->where('pp.id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->orderBy('mp.nama_barang')
                ->select('pp.id_barang', 'mp.nama_barang', 'mp.satuan', 'pp.jumlah as jumlah_awal')
                ->get();

            $existingSisa = DB::table('sisa_trip')
                ->where('id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->pluck('jumlah_sisa', 'id_barang')
                ->map(fn ($value) => (float) $value)
                ->toArray();

            $rekapPerbekalan = $this->perbekalanFifoService->buildTripUsageSummary($selectedPelayaran);

            $existingHasilIkanByKategori = DB::table('ikan_hasil_pelayaran as ihp')
                ->leftJoin('master_ikan as mi', 'mi.id_ikan', '=', 'ihp.id_ikan')
                ->where('ihp.id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->selectRaw(
                    'ihp.kategori_tangkapan,
                    COALESCE(mi.id_ikan_tangkapan, ihp.id_ikan) as form_ikan_key,
                    SUM(ihp.berat_hasil) as berat_hasil,
                    CASE
                        WHEN SUM(ihp.berat_hasil) = 0 THEN 0
                        ELSE SUM(ihp.berat_hasil * COALESCE(ihp.harga_per_kg, 0)) / SUM(ihp.berat_hasil)
                    END as harga_per_kg'
                )
                ->groupBy('ihp.kategori_tangkapan', DB::raw('COALESCE(mi.id_ikan_tangkapan, ihp.id_ikan)'))
                ->get()
                ->groupBy('kategori_tangkapan')
                ->map(function ($rows) {
                    return $rows->mapWithKeys(function ($row) {
                        return [(int) $row->form_ikan_key => [
                            'berat_hasil' => (float) $row->berat_hasil,
                            'harga_per_kg' => (float) ($row->harga_per_kg ?? 0),
                        ]];
                    })->toArray();
                })
                ->toArray();

            $existingPersonalAnglers = DB::table('ikan_hasil_pelayaran as ihp')
                ->join('master_ikan as mi', 'mi.id_ikan', '=', 'ihp.id_ikan')
                ->where('ihp.id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->where('ihp.kategori_tangkapan', 'pancingan_pribadi')
                ->select('ihp.nama_penangkap', 'mi.id_ikan_tangkapan', 'ihp.berat_hasil', 'ihp.harga_per_kg')
                ->orderBy('ihp.nama_penangkap')
                ->orderBy('mi.id_ikan_tangkapan')
                ->get()
                ->groupBy(function ($row) {
                    $nama = trim((string) ($row->nama_penangkap ?? ''));

                    return $nama !== '' ? $nama : 'Tanpa Nama';
                })
                ->map(function ($rows, $namaPenangkap) {
                    return [
                        'name' => (string) $namaPenangkap,
                        'items' => $rows->map(function ($row) {
                            return [
                                'id_ikan_tangkapan' => (int) $row->id_ikan_tangkapan,
                                'berat' => (float) $row->berat_hasil,
                                'harga_per_kg' => (float) ($row->harga_per_kg ?? 0),
                            ];
                        })->values()->all(),
                    ];
                })
                ->values()
                ->all();

            $rekapTangkapan = DB::table('ikan_hasil_pelayaran')
                ->where('id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->selectRaw('kategori_tangkapan, SUM(berat_hasil) as total_berat, SUM(berat_hasil * harga_per_kg) as total_nilai')
                ->groupBy('kategori_tangkapan')
                ->get()
                ->keyBy('kategori_tangkapan');

            $existingOperasional = DB::table('operasional')
                ->where('id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->pluck('jumlah', 'id_master_operasional')
                ->map(fn ($value) => (float) $value)
                ->toArray();

            $existingOperasionalTanggal = DB::table('operasional')
                ->where('id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->pluck('tanggal', 'id_master_operasional')
                ->toArray();

            $rekapDetail = DB::table('operasional as o')
                ->leftJoin('master_operasional as mo', 'mo.id_master_operasional', '=', 'o.id_master_operasional')
                ->where('o.id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->select(
                    'o.id_operasional',
                    'o.tanggal',
                    'o.jumlah',
                    'o.jenis_biaya',
                    'o.deskripsi',
                    'mo.nama_operasional'
                )
                ->orderByDesc('o.tanggal')
                ->orderByDesc('o.id_operasional')
                ->get();

            $rekapOperasional = [
                'total_item_biaya' => $rekapDetail->count(),
                'total_biaya' => (float) $rekapDetail->sum('jumlah'),
                'detail' => $rekapDetail,
            ];

            $totalNilaiTangkapanBruto = (float) $rekapTangkapan->sum(fn ($row) => (float) ($row->total_nilai ?? 0));
            $totalNilaiTangkapan = 0.0;
            foreach (array_keys(self::KATEGORI_TANGKAPAN) as $kategoriKey) {
                $row = $rekapTangkapan->get($kategoriKey);
                $totalNilaiTangkapan += $this->calculateCatchCreditAmount($kategoriKey, (float) ($row->total_nilai ?? 0));
            }
            $totalNilaiJaringanBruto = (float) ($rekapTangkapan->get('jaringan')->total_nilai ?? 0);
            $totalNilaiJaringanDikreditkan = $this->calculateCatchCreditAmount('jaringan', $totalNilaiJaringanBruto);
            $totalPerbekalanTerpakai = (float) $rekapPerbekalan->sum('total_biaya');
            $totalOperasional = (float) $rekapOperasional['total_biaya'];

            $rekapGrandTotals = [
                'item_perbekalan_terpakai' => $rekapPerbekalan->filter(fn ($row) => (float) $row->jumlah_terpakai > 0)->count(),
                'total_perbekalan_terpakai' => $totalPerbekalanTerpakai,
                'total_tangkapan' => $totalNilaiTangkapan,
                'total_tangkapan_bruto' => $totalNilaiTangkapanBruto,
                'total_operasional' => $totalOperasional,
                'grand_total_semua_komponen' => $totalPerbekalanTerpakai + $totalNilaiTangkapan + $totalOperasional,
                'estimasi_selisih_bersih' => $totalNilaiTangkapan - $totalPerbekalanTerpakai - $totalOperasional,
                'total_jaringan_bruto' => $totalNilaiJaringanBruto,
                'total_jaringan_dikreditkan' => $totalNilaiJaringanDikreditkan,
                'total_jaringan_bagi_hasil' => max(0, $totalNilaiJaringanBruto - $totalNilaiJaringanDikreditkan),
                'jaringan_credit_factor' => self::JARINGAN_CREDIT_FACTOR,
            ];

            $plannedCount = $perbekalanRows->count();
            $filledSisaCount = DB::table('sisa_trip')
                ->where('id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->count();

            $completionStatus = [
                'perbekalan' => $plannedCount === 0 ? true : $filledSisaCount >= $plannedCount,
                'tangkapan' => $masterIkanTangkapan->count() === 0
                    ? true
                    : collect(array_keys(self::KATEGORI_TANGKAPAN))->every(function (string $kategori) use ($existingHasilIkanByKategori) {
                        return !empty($existingHasilIkanByKategori[$kategori] ?? []);
                    }),
                'operasional' => $masterOperasional->count() === 0 ? true : !empty($existingOperasional),
            ];
        }

        $canClose = !$isReadOnly && $selectedPelayaran
            ? ($completionStatus['perbekalan'] && $completionStatus['tangkapan'] && $completionStatus['operasional'])
            : false;
        $kategoriTangkapanMap = self::KATEGORI_TANGKAPAN;

        return [
            'activePelayaran' => $activePelayaran,
            'selectedPelayaran' => $selectedPelayaran,
            'activeTab' => $activeTab,
            'perbekalanRows' => $perbekalanRows,
            'existingSisa' => $existingSisa,
            'masterIkanTangkapan' => $masterIkanTangkapan,
            'existingHasilIkanByKategori' => $existingHasilIkanByKategori,
            'existingPersonalAnglers' => $existingPersonalAnglers,
            'masterOperasional' => $masterOperasional,
            'existingOperasional' => $existingOperasional,
            'existingOperasionalTanggal' => $existingOperasionalTanggal,
            'rekapOperasional' => $rekapOperasional,
            'rekapTangkapan' => $rekapTangkapan,
            'rekapPerbekalan' => $rekapPerbekalan,
            'rekapGrandTotals' => $rekapGrandTotals,
            'completionStatus' => $completionStatus,
            'canClose' => $canClose,
            'kategoriTangkapanMap' => $kategoriTangkapanMap,
            'isReadOnly' => $isReadOnly,
        ];
    }

    private function getActivePelayaranOrFail(int $idPelayaran): Pelayaran
    {
        $pelayaran = Pelayaran::query()
            ->where('id_pelayaran', $idPelayaran)
            ->firstOrFail();

        if ($pelayaran->status_pelayaran !== 'aktif') {
            throw ValidationException::withMessages([
                'message' => 'Pelayaran sudah ditutup sebelumnya.',
            ]);
        }

        return $pelayaran;
    }

    private function buildVoyageSalesWindow(Pelayaran $pelayaran): array
    {
        $startDate = ($pelayaran->tanggal_selesai ?? $pelayaran->tanggal_tiba ?? $pelayaran->tanggal_berangkat ?? now())->toDateString();

        $nextPelayaran = Pelayaran::query()
            ->where('id_kapal', (int) $pelayaran->id_kapal)
            ->where('status_pelayaran', 'selesai')
            ->where('id_pelayaran', '!=', (int) $pelayaran->id_pelayaran)
            ->get(['id_pelayaran', 'tanggal_tiba', 'tanggal_selesai'])
            ->map(function (Pelayaran $item) {
                $closeDate = ($item->tanggal_selesai ?? $item->tanggal_tiba)?->toDateString();

                return $closeDate ? (object) ['trip' => $item, 'close_date' => $closeDate] : null;
            })
            ->filter()
            ->filter(fn ($item) => $item->close_date > $startDate)
            ->sortBy('close_date')
            ->first();

        $endExclusive = $nextPelayaran?->close_date;
        $displayEnd = $endExclusive
            ? Carbon::parse($endExclusive)->subDay()->toDateString()
            : now()->toDateString();

        return [
            'start_date' => $startDate,
            'end_date' => $displayEnd,
            'end_exclusive' => $endExclusive,
            'next_pelayaran' => $nextPelayaran?->trip,
        ];
    }

    private function buildVoyageCatchBreakdown(int $idPelayaran): Collection
    {
        return DB::table('ikan_hasil_pelayaran as ihp')
            ->join('master_ikan as mi', 'mi.id_ikan', '=', 'ihp.id_ikan')
            ->leftJoin('master_ikan_tangkapan as mit', 'mit.id_ikan_tangkapan', '=', 'mi.id_ikan_tangkapan')
            ->where('ihp.id_pelayaran', $idPelayaran)
            ->selectRaw(
                "CASE WHEN mi.id_ikan_tangkapan IS NULL THEN 'single' ELSE 'group' END as commodity_type,
                COALESCE(mi.id_ikan_tangkapan, mi.id_ikan) as commodity_ref_id,
                CASE
                    WHEN mi.id_ikan_tangkapan IS NULL THEN CONCAT('single:', mi.id_ikan)
                    ELSE CONCAT('group:', mi.id_ikan_tangkapan)
                END as commodity_key,
                COALESCE(mit.nama_ikan_tangkapan, mi.nama_ikan) as commodity_name,
                SUM(ihp.berat_hasil) as catch_weight,
                SUM(ihp.berat_hasil * COALESCE(ihp.harga_per_kg, 0)) as catch_value"
            )
            ->groupBy(
                DB::raw("CASE WHEN mi.id_ikan_tangkapan IS NULL THEN 'single' ELSE 'group' END"),
                DB::raw('COALESCE(mi.id_ikan_tangkapan, mi.id_ikan)'),
                DB::raw("CASE WHEN mi.id_ikan_tangkapan IS NULL THEN CONCAT('single:', mi.id_ikan) ELSE CONCAT('group:', mi.id_ikan_tangkapan) END"),
                DB::raw('COALESCE(mit.nama_ikan_tangkapan, mi.nama_ikan)')
            )
            ->orderBy('commodity_name')
            ->get();
    }

    private function buildVoyageSalesEstimateBreakdown(Collection $catchBreakdown, array $salesWindow): Collection
    {
        $groupIds = $catchBreakdown
            ->where('commodity_type', 'group')
            ->pluck('commodity_ref_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $directIds = $catchBreakdown
            ->where('commodity_type', 'single')
            ->pluck('commodity_ref_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($groupIds === [] && $directIds === []) {
            return collect();
        }

        return DB::table('penjualan_items as pi')
            ->join('penjualan as p', 'p.id_penjualan', '=', 'pi.id_penjualan')
            ->join('master_ikan as mi', 'mi.id_ikan', '=', 'pi.id_ikan')
            ->leftJoin('master_ikan_tangkapan as mit', 'mit.id_ikan_tangkapan', '=', 'mi.id_ikan_tangkapan')
            ->whereDate('p.tanggal_penjualan', '>=', $salesWindow['start_date'])
            ->when($salesWindow['end_exclusive'], function ($query, $endExclusive) {
                $query->whereDate('p.tanggal_penjualan', '<', $endExclusive);
            })
            ->where(function ($query) use ($groupIds, $directIds) {
                if ($groupIds !== []) {
                    $query->whereIn('mi.id_ikan_tangkapan', $groupIds);
                }

                if ($directIds !== []) {
                    if ($groupIds !== []) {
                        $query->orWhereIn('pi.id_ikan', $directIds);
                    } else {
                        $query->whereIn('pi.id_ikan', $directIds);
                    }
                }
            })
            ->selectRaw(
                "CASE WHEN mi.id_ikan_tangkapan IS NULL THEN CONCAT('single:', mi.id_ikan) ELSE CONCAT('group:', mi.id_ikan_tangkapan) END as commodity_key,
                COALESCE(mit.nama_ikan_tangkapan, mi.nama_ikan) as commodity_name,
                SUM(pi.berat) as sales_weight,
                SUM(pi.subtotal) as sales_value"
            )
            ->groupBy(
                DB::raw("CASE WHEN mi.id_ikan_tangkapan IS NULL THEN CONCAT('single:', mi.id_ikan) ELSE CONCAT('group:', mi.id_ikan_tangkapan) END"),
                DB::raw('COALESCE(mit.nama_ikan_tangkapan, mi.nama_ikan)')
            )
            ->orderBy('commodity_name')
            ->get();
    }

    private function buildVoyageSalesActualBreakdown(int $idPelayaran): Collection
    {
        return DB::table('penjualan_item_lot_allocations as pila')
            ->join('master_ikan as mi', 'mi.id_ikan', '=', 'pila.id_ikan')
            ->leftJoin('master_ikan_tangkapan as mit', 'mit.id_ikan_tangkapan', '=', 'mi.id_ikan_tangkapan')
            ->where('pila.id_pelayaran', $idPelayaran)
            ->selectRaw(
                "CASE WHEN mi.id_ikan_tangkapan IS NULL THEN CONCAT('single:', mi.id_ikan) ELSE CONCAT('group:', mi.id_ikan_tangkapan) END as commodity_key,
                COALESCE(mit.nama_ikan_tangkapan, mi.nama_ikan) as commodity_name,
                SUM(pila.berat_alokasi) as sales_weight,
                SUM(pila.berat_alokasi * pila.harga_per_kg_lot) as sales_value"
            )
            ->groupBy(
                DB::raw("CASE WHEN mi.id_ikan_tangkapan IS NULL THEN CONCAT('single:', mi.id_ikan) ELSE CONCAT('group:', mi.id_ikan_tangkapan) END"),
                DB::raw('COALESCE(mit.nama_ikan_tangkapan, mi.nama_ikan)')
            )
            ->orderBy('commodity_name')
            ->get();
    }

    private function buildVesselStorageSnapshot(int $idKapal): Collection
    {
        return DB::table('storage_ikan as si')
            ->join('stok_ikan_storage as sis', 'sis.id_storage', '=', 'si.id_storage')
            ->join('master_ikan as mi', 'mi.id_ikan', '=', 'sis.id_ikan')
            ->leftJoin('master_ikan_tangkapan as mit', 'mit.id_ikan_tangkapan', '=', 'mi.id_ikan_tangkapan')
            ->where('si.id_kapal', $idKapal)
            ->selectRaw(
                "CASE WHEN mi.id_ikan_tangkapan IS NULL THEN CONCAT('single:', mi.id_ikan) ELSE CONCAT('group:', mi.id_ikan_tangkapan) END as commodity_key,
                COALESCE(mit.nama_ikan_tangkapan, mi.nama_ikan) as commodity_name,
                SUM(sis.stok_aktual) as current_storage_weight"
            )
            ->groupBy(
                DB::raw("CASE WHEN mi.id_ikan_tangkapan IS NULL THEN CONCAT('single:', mi.id_ikan) ELSE CONCAT('group:', mi.id_ikan_tangkapan) END"),
                DB::raw('COALESCE(mit.nama_ikan_tangkapan, mi.nama_ikan)')
            )
            ->orderBy('commodity_name')
            ->get();
    }

    private function storeCatchToKapalStorage(Pelayaran $pelayaran): void
    {
        $pelayaran->loadMissing('kapal');

        $idStorage = $this->getOrCreateStorageIdForKapal(
            (int) $pelayaran->id_kapal,
            (string) ($pelayaran->kapal->nama_kapal ?? ('Kapal #' . $pelayaran->id_kapal))
        );

        $catchRows = DB::table('ikan_hasil_pelayaran')
            ->where('id_pelayaran', (int) $pelayaran->id_pelayaran)
            ->selectRaw('id_ikan, SUM(berat_hasil) as total_berat')
            ->groupBy('id_ikan')
            ->get();

        if ($catchRows->isEmpty()) {
            return;
        }

        $existingStocks = DB::table('stok_ikan_storage')
            ->where('id_storage', $idStorage)
            ->whereIn('id_ikan', $catchRows->pluck('id_ikan')->all())
            ->lockForUpdate()
            ->get()
            ->keyBy(fn ($row) => (int) $row->id_ikan);

        $now = now();
        $rows = $catchRows->map(function ($row) use ($idStorage, $existingStocks, $now) {
            $existingStock = $existingStocks->get((int) $row->id_ikan);
            $stokSaatIni = (float) ($existingStock->stok_aktual ?? 0);

            return [
                'id_storage' => $idStorage,
                'id_ikan' => (int) $row->id_ikan,
                'stok_aktual' => $stokSaatIni + (float) $row->total_berat,
                'created_at' => $existingStock->created_at ?? $now,
                'updated_at' => $now,
            ];
        })->values()->all();

        DB::table('stok_ikan_storage')->upsert(
            $rows,
            ['id_storage', 'id_ikan'],
            ['stok_aktual', 'updated_at']
        );

        $lotRows = $catchRows->map(function ($row) use ($idStorage, $pelayaran, $now) {
            return [
                'id_storage' => $idStorage,
                'id_ikan' => (int) $row->id_ikan,
                'id_pelayaran' => (int) $pelayaran->id_pelayaran,
                'source_type' => 'trip',
                'tanggal_lot' => ($pelayaran->tanggal_selesai ?? $pelayaran->tanggal_tiba ?? now())->toDateString(),
                'berat_awal' => (float) $row->total_berat,
                'berat_sisa' => (float) $row->total_berat,
                'harga_per_kg' => (float) (DB::table('ikan_hasil_pelayaran')
                    ->where('id_pelayaran', (int) $pelayaran->id_pelayaran)
                    ->where('id_ikan', (int) $row->id_ikan)
                    ->selectRaw('CASE WHEN SUM(berat_hasil) = 0 THEN 0 ELSE SUM(berat_hasil * COALESCE(harga_per_kg, 0)) / SUM(berat_hasil) END as avg_harga')
                    ->value('avg_harga') ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();

        DB::table('stok_ikan_lots')->insert($lotRows);

        $this->recalculateStokIkan(now()->format('Y-m'), $catchRows->pluck('id_ikan')->map(fn ($id) => (int) $id)->all());
    }

    private function getOrCreateStorageIdForKapal(int $idKapal, string $namaKapal): int
    {
        $existingId = DB::table('storage_ikan')
            ->where('id_kapal', $idKapal)
            ->value('id_storage');

        if ($existingId !== null) {
            return (int) $existingId;
        }

        return (int) DB::table('storage_ikan')->insertGetId([
            'id_kapal' => $idKapal,
            'nama_storage' => 'Storage ' . $namaKapal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function extractNumericMap(array $input, bool $allowZero): Collection
    {
        return collect($input)
            ->mapWithKeys(function ($value, $key) {
                if ($value === null || $value === '') {
                    return [];
                }

                return [(int) $key => (float) $value];
            })
            ->filter(function (float $value) use ($allowZero) {
                return $allowZero ? $value >= 0 : $value > 0;
            });
    }

    private function extractPersonalCatchRows(array $anglersInput, array $validIkanTangkapanIds, array $relasiIkanMap): Collection
    {
        $validIdSet = array_flip($validIkanTangkapanIds);
        $rows = collect();

        foreach ($anglersInput as $anglerIndex => $angler) {
            $nama = trim((string) ($angler['name'] ?? ''));
            $items = is_array($angler['items'] ?? null) ? $angler['items'] : [];

            foreach ($items as $itemIndex => $item) {
                $idIkanTangkapan = (int) ($item['id_ikan_tangkapan'] ?? 0);
                $beratRaw = $item['berat'] ?? null;
                $hargaRaw = $item['harga_per_kg'] ?? null;

                $berat = ($beratRaw === null || $beratRaw === '') ? 0 : (float) $beratRaw;
                $harga = ($hargaRaw === null || $hargaRaw === '') ? 0 : (float) $hargaRaw;
                $hasAnyValue = $idIkanTangkapan > 0 || $berat > 0 || ($hargaRaw !== null && $hargaRaw !== '');

                if (!$hasAnyValue) {
                    continue;
                }

                if ($nama === '') {
                    throw ValidationException::withMessages([
                        'anglers.' . $anglerIndex . '.name' => 'Nama penangkap wajib diisi jika ada ikan tangkapan.',
                    ]);
                }

                if (!isset($validIdSet[$idIkanTangkapan])) {
                    throw ValidationException::withMessages([
                        'anglers.' . $anglerIndex . '.items.' . $itemIndex . '.id_ikan_tangkapan' => 'Pilih ikan tangkapan yang valid.',
                    ]);
                }

                if ($berat <= 0) {
                    throw ValidationException::withMessages([
                        'anglers.' . $anglerIndex . '.items.' . $itemIndex . '.berat' => 'Berat tangkapan harus lebih dari 0.',
                    ]);
                }

                if (!isset($relasiIkanMap[$idIkanTangkapan])) {
                    throw ValidationException::withMessages([
                        'anglers' => 'Beberapa Master Ikan Tangkapan belum terhubung ke Master Ikan penjualan. Lengkapi relasinya terlebih dahulu.',
                    ]);
                }

                $rows->push([
                    'nama_penangkap' => $nama,
                    'id_ikan' => (int) $relasiIkanMap[$idIkanTangkapan],
                    'berat_hasil' => $berat,
                    'harga_per_kg' => $harga,
                ]);
            }
        }

        if ($rows->isEmpty()) {
            return collect();
        }

        return $rows
            ->groupBy(fn (array $row) => $row['nama_penangkap'] . '|' . $row['id_ikan'])
            ->map(function (Collection $group) {
                $first = $group->first();
                $totalBerat = (float) $group->sum('berat_hasil');
                $totalNilai = (float) $group->sum(function (array $row) {
                    return (float) $row['berat_hasil'] * (float) $row['harga_per_kg'];
                });

                return [
                    'nama_penangkap' => (string) $first['nama_penangkap'],
                    'id_ikan' => (int) $first['id_ikan'],
                    'berat_hasil' => $totalBerat,
                    'harga_per_kg' => $totalBerat > 0 ? ($totalNilai / $totalBerat) : 0,
                ];
            })
            ->values();
    }

    private function calculateCatchCreditAmount(string $kategoriKey, float $rawAmount): float
    {
        if ($rawAmount <= 0) {
            return 0;
        }

        if ($kategoriKey === 'tangkapan_3_ton') {
            return 0;
        }

        if ($kategoriKey === 'jaringan') {
            return round($rawAmount * self::JARINGAN_CREDIT_FACTOR, 2);
        }

        return round($rawAmount, 2);
    }

    /**
     * Recalculate monthly fish stock snapshot using catches and sales.
     */
    private function recalculateStokIkan(string $periode, array $affectedIkanIds): void
    {
        $affectedIkanIds = collect($affectedIkanIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($affectedIkanIds->isEmpty()) {
            return;
        }

        $periodeStart = now()->createFromFormat('Y-m', $periode)->startOfMonth()->toDateString();
        $periodeEnd = now()->createFromFormat('Y-m', $periode)->endOfMonth()->toDateString();

        $seedIkan = DB::table('master_ikan')
            ->whereIn('id_ikan', $affectedIkanIds->all())
            ->get(['id_ikan', 'id_ikan_tangkapan']);

        $relationIds = $seedIkan->pluck('id_ikan_tangkapan')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $targetIkan = DB::table('master_ikan')
            ->when($relationIds->isNotEmpty(), function ($query) use ($relationIds, $affectedIkanIds) {
                $query->whereIn('id_ikan', $affectedIkanIds->all())
                    ->orWhereIn('id_ikan_tangkapan', $relationIds->all());
            }, function ($query) use ($affectedIkanIds) {
                $query->whereIn('id_ikan', $affectedIkanIds->all());
            })
            ->get(['id_ikan', 'id_ikan_tangkapan']);

        $directIkanIds = $targetIkan
            ->filter(fn ($ikan) => empty($ikan->id_ikan_tangkapan))
            ->pluck('id_ikan')
            ->map(fn ($id) => (int) $id)
            ->all();

        $salesByRelation = $relationIds->isEmpty()
            ? collect()
            : DB::table('penjualan_items as pi')
                ->join('penjualan as p', 'p.id_penjualan', '=', 'pi.id_penjualan')
                ->join('master_ikan as mi', 'mi.id_ikan', '=', 'pi.id_ikan')
                ->whereIn('mi.id_ikan_tangkapan', $relationIds->all())
                ->whereBetween('p.tanggal_penjualan', [$periodeStart, $periodeEnd])
                ->groupBy('mi.id_ikan_tangkapan')
                ->selectRaw('mi.id_ikan_tangkapan as group_key, SUM(pi.berat) as total_penjualan')
                ->pluck('total_penjualan', 'group_key');

        $catchByRelation = $relationIds->isEmpty()
            ? collect()
            : DB::table('ikan_hasil_pelayaran as ihp')
                ->join('pelayaran as p', 'p.id_pelayaran', '=', 'ihp.id_pelayaran')
                ->join('master_ikan as mi', 'mi.id_ikan', '=', 'ihp.id_ikan')
                ->whereIn('mi.id_ikan_tangkapan', $relationIds->all())
                ->whereRaw('DATE(COALESCE(p.tanggal_selesai, p.tanggal_tiba)) BETWEEN ? AND ?', [$periodeStart, $periodeEnd])
                ->groupBy('mi.id_ikan_tangkapan')
                ->selectRaw('mi.id_ikan_tangkapan as group_key, SUM(ihp.berat_hasil) as total_tangkapan')
                ->pluck('total_tangkapan', 'group_key');

        $salesByIkan = empty($directIkanIds)
            ? collect()
            : DB::table('penjualan_items as pi')
                ->join('penjualan as p', 'p.id_penjualan', '=', 'pi.id_penjualan')
                ->whereIn('pi.id_ikan', $directIkanIds)
                ->whereBetween('p.tanggal_penjualan', [$periodeStart, $periodeEnd])
                ->groupBy('pi.id_ikan')
                ->selectRaw('pi.id_ikan as ikan_key, SUM(pi.berat) as total_penjualan')
                ->pluck('total_penjualan', 'ikan_key');

        $catchByIkan = empty($directIkanIds)
            ? collect()
            : DB::table('ikan_hasil_pelayaran as ihp')
                ->join('pelayaran as p', 'p.id_pelayaran', '=', 'ihp.id_pelayaran')
                ->whereIn('ihp.id_ikan', $directIkanIds)
                ->whereRaw('DATE(COALESCE(p.tanggal_selesai, p.tanggal_tiba)) BETWEEN ? AND ?', [$periodeStart, $periodeEnd])
                ->groupBy('ihp.id_ikan')
                ->selectRaw('ihp.id_ikan as ikan_key, SUM(ihp.berat_hasil) as total_tangkapan')
                ->pluck('total_tangkapan', 'ikan_key');

        $adjustmentByRelation = $relationIds->isEmpty()
            ? collect()
            : DB::table('penyesuaian_stok_ikan_items as psii')
                ->join('penyesuaian_stok_ikan as psi', 'psi.id_penyesuaian_stok', '=', 'psii.id_penyesuaian_stok')
                ->join('master_ikan as mi', 'mi.id_ikan', '=', 'psii.id_ikan')
                ->whereIn('mi.id_ikan_tangkapan', $relationIds->all())
                ->whereBetween(DB::raw('DATE(psi.created_at)'), [$periodeStart, $periodeEnd])
                ->groupBy('mi.id_ikan_tangkapan')
                ->selectRaw('mi.id_ikan_tangkapan as group_key, SUM(psii.delta_berat) as total_penyesuaian')
                ->pluck('total_penyesuaian', 'group_key');

        $adjustmentByIkan = empty($directIkanIds)
            ? collect()
            : DB::table('penyesuaian_stok_ikan_items as psii')
                ->join('penyesuaian_stok_ikan as psi', 'psi.id_penyesuaian_stok', '=', 'psii.id_penyesuaian_stok')
                ->whereIn('psii.id_ikan', $directIkanIds)
                ->whereBetween(DB::raw('DATE(psi.created_at)'), [$periodeStart, $periodeEnd])
                ->groupBy('psii.id_ikan')
                ->selectRaw('psii.id_ikan as ikan_key, SUM(psii.delta_berat) as total_penyesuaian')
                ->pluck('total_penyesuaian', 'ikan_key');

        $now = now();
        $rows = $targetIkan->map(function ($ikan) use ($adjustmentByIkan, $adjustmentByRelation, $catchByIkan, $catchByRelation, $salesByIkan, $salesByRelation, $periode, $now) {
            $relationId = $ikan->id_ikan_tangkapan ? (int) $ikan->id_ikan_tangkapan : null;
            $totalTangkapan = $relationId
                ? (float) ($catchByRelation[$relationId] ?? 0)
                : (float) ($catchByIkan[$ikan->id_ikan] ?? 0);
            $totalPenjualan = $relationId
                ? (float) ($salesByRelation[$relationId] ?? 0)
                : (float) ($salesByIkan[$ikan->id_ikan] ?? 0);
            $totalPenyesuaian = $relationId
                ? (float) ($adjustmentByRelation[$relationId] ?? 0)
                : (float) ($adjustmentByIkan[$ikan->id_ikan] ?? 0);

            return [
                'id_ikan' => (int) $ikan->id_ikan,
                'periode' => $periode,
                'total_tangkapan' => $totalTangkapan,
                'total_penjualan' => $totalPenjualan,
                'stok_akhir' => $totalTangkapan + $totalPenyesuaian - $totalPenjualan,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();

        DB::table('stok_ikan')->upsert(
            $rows,
            ['id_ikan', 'periode'],
            ['total_tangkapan', 'total_penjualan', 'stok_akhir', 'updated_at']
        );
    }

    private function getLastSaldoByAkun(string $akun): float
    {
        return (float) (DB::table('arus_kas')
            ->where('akun', $akun)
            ->orderByDesc('id_kas')
            ->value('saldo') ?? 0);
    }

    private function postArusKas(string $akun, string $tanggal, string $kategori, string $deskripsi, float $debit, float $kredit): void
    {
        $lastSaldo = $this->getLastSaldoByAkun($akun);
        $saldoBaru = $lastSaldo + $debit - $kredit;

        if ($saldoBaru < -0.009) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'nominal' => 'Saldo '.strtoupper($akun).' tidak mencukupi. Saldo tersedia Rp '.number_format($lastSaldo, 2, ',', '.').', sehingga transaksi ini tidak boleh membuat saldo minus.',
            ]);
        }

        DB::table('arus_kas')->insert([
            'akun' => $akun,
            'tanggal' => $tanggal,
            'jenis_transaksi' => $debit > 0 ? 'Masuk' : 'Keluar',
            'kategori' => $kategori,
            'deskripsi' => $deskripsi,
            'uang_masuk' => $debit,
            'uang_keluar' => $kredit,
            'saldo' => $saldoBaru,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
