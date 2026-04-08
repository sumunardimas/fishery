<?php

namespace App\Http\Controllers;

use App\Models\MasterIkanTangkapan;
use App\Models\Pelayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    private const TAB_OPERASIONAL = 'operasional';
    private const TAB_REKAP = 'rekap';

    private const KATEGORI_TANGKAPAN = [
        'pancingan_pribadi' => 'Pancingan Pribadi',
        'pancingan_bersama' => 'Pancingan Bersama',
        'jaringan' => 'Jaringan',
    ];

    private const TAB_TO_KATEGORI = [
        self::TAB_TANGKAPAN_PRIBADI => 'pancingan_pribadi',
        self::TAB_TANGKAPAN_BERSAMA => 'pancingan_bersama',
        self::TAB_TANGKAPAN_JARINGAN => 'jaringan',
    ];

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
        $activeTab = $request->string('tab')->toString();
        if ($activeTab === 'tangkapan') {
            $activeTab = self::TAB_TANGKAPAN_PRIBADI;
        }
        $validTabs = [
            self::TAB_PERBEKALAN,
            self::TAB_TANGKAPAN_PRIBADI,
            self::TAB_TANGKAPAN_BERSAMA,
            self::TAB_TANGKAPAN_JARINGAN,
            self::TAB_OPERASIONAL,
            self::TAB_REKAP,
        ];
        if (!in_array($activeTab, $validTabs, true)) {
            $activeTab = self::TAB_PERBEKALAN;
        }

        $selectedPelayaran = $activePelayaran
            ->firstWhere('id_pelayaran', $selectedPelayaranId);

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
        $masterOperasional = DB::table('master_operasional')
            ->orderBy('nama_operasional')
            ->get();
        $existingOperasional = [];
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

        $canClose = $selectedPelayaran
            ? ($completionStatus['perbekalan'] && $completionStatus['tangkapan'] && $completionStatus['operasional'])
            : false;
        $kategoriTangkapanMap = self::KATEGORI_TANGKAPAN;

        return view('pelayaran.sisa.index', compact(
            'activePelayaran',
            'selectedPelayaran',
            'activeTab',
            'perbekalanRows',
            'existingSisa',
            'masterIkanTangkapan',
            'existingHasilIkanByKategori',
            'masterOperasional',
            'existingOperasional',
            'rekapOperasional',
            'rekapTangkapan',
            'completionStatus',
            'canClose',
            'kategoriTangkapanMap'
        ));
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
        ]);

        $idPelayaran = (int) $validated['id_pelayaran'];
        $pelayaran = $this->getActivePelayaranOrFail($idPelayaran);
        $kategoriTangkapan = (string) $validated['kategori_tangkapan'];

        $hasilIkan = $this->extractNumericMap($request->input('hasil_ikan', []), false);
        $hargaIkan = $this->extractNumericMap($request->input('harga_ikan', []), true);

        $ikanTangkapanCollection = MasterIkanTangkapan::query()
            ->whereHas('masterIkan')
            ->with(['masterIkan' => function ($query) {
                $query->select('id_ikan', 'id_ikan_tangkapan')->orderBy('id_ikan');
            }])
            ->get();

        $validIkanTangkapanIds = $ikanTangkapanCollection->pluck('id_ikan_tangkapan')
            ->map(fn ($id) => (int) $id)
            ->all();

        $hasilIkan = $hasilIkan->only($validIkanTangkapanIds)->filter(fn (float $berat) => $berat > 0);
        $hargaIkan = $hargaIkan->only($validIkanTangkapanIds);

        $relasiIkanMap = $ikanTangkapanCollection->mapWithKeys(function (MasterIkanTangkapan $item) {
            $representativeIkan = $item->masterIkan->sortBy('id_ikan')->first();

            return $representativeIkan
                ? [(int) $item->id_ikan_tangkapan => (int) $representativeIkan->id_ikan]
                : [];
        })->all();

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

        $hargaIkan = $hargaIkan->mapWithKeys(function (float $harga, int $idIkanTangkapan) use ($relasiIkanMap) {
            if (!isset($relasiIkanMap[$idIkanTangkapan])) {
                return [];
            }

            return [(int) $relasiIkanMap[$idIkanTangkapan] => $harga];
        });

        DB::transaction(function () use ($idPelayaran, $kategoriTangkapan, $hasilIkan, $hargaIkan) {
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

            if ($hasilIkan->isNotEmpty()) {
                $now = now();
                $hasilRows = $hasilIkan->map(function (float $beratHasil, int $idIkan) use ($idPelayaran, $kategoriTangkapan, $hargaIkan, $now) {
                    return [
                        'id_pelayaran' => $idPelayaran,
                        'id_ikan' => $idIkan,
                        'kategori_tangkapan' => $kategoriTangkapan,
                        'berat_hasil' => $beratHasil,
                        'harga_per_kg' => (float) ($hargaIkan[$idIkan] ?? 0),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->values()->all();

                DB::table('ikan_hasil_pelayaran')->insert($hasilRows);
            }

            $periode = now()->format('Y-m');
            $affectedIkanIds = collect($existingIkanIds)
                ->merge($hasilIkan->keys()->map(fn ($id) => (int) $id)->all())
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

        $pelayaran->update([
            'status_pelayaran' => 'selesai',
            'tanggal_selesai' => now()->toDateString(),
        ]);

        return redirect()
            ->route('pelayaran.index')
            ->with('success', 'Pelayaran berhasil ditutup.');
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
                ->whereRaw("DATE_FORMAT(p.tanggal_penjualan, '%Y-%m') = ?", [$periode])
                ->groupBy('mi.id_ikan_tangkapan')
                ->selectRaw('mi.id_ikan_tangkapan as group_key, SUM(pi.berat) as total_penjualan')
                ->pluck('total_penjualan', 'group_key');

        $catchByRelation = $relationIds->isEmpty()
            ? collect()
            : DB::table('ikan_hasil_pelayaran as ihp')
                ->join('pelayaran as p', 'p.id_pelayaran', '=', 'ihp.id_pelayaran')
                ->join('master_ikan as mi', 'mi.id_ikan', '=', 'ihp.id_ikan')
                ->whereIn('mi.id_ikan_tangkapan', $relationIds->all())
                ->whereRaw("DATE_FORMAT(COALESCE(p.tanggal_selesai, p.tanggal_tiba), '%Y-%m') = ?", [$periode])
                ->groupBy('mi.id_ikan_tangkapan')
                ->selectRaw('mi.id_ikan_tangkapan as group_key, SUM(ihp.berat_hasil) as total_tangkapan')
                ->pluck('total_tangkapan', 'group_key');

        $salesByIkan = empty($directIkanIds)
            ? collect()
            : DB::table('penjualan_items as pi')
                ->join('penjualan as p', 'p.id_penjualan', '=', 'pi.id_penjualan')
                ->whereIn('pi.id_ikan', $directIkanIds)
                ->whereRaw("DATE_FORMAT(p.tanggal_penjualan, '%Y-%m') = ?", [$periode])
                ->groupBy('pi.id_ikan')
                ->selectRaw('pi.id_ikan as ikan_key, SUM(pi.berat) as total_penjualan')
                ->pluck('total_penjualan', 'ikan_key');

        $catchByIkan = empty($directIkanIds)
            ? collect()
            : DB::table('ikan_hasil_pelayaran as ihp')
                ->join('pelayaran as p', 'p.id_pelayaran', '=', 'ihp.id_pelayaran')
                ->whereIn('ihp.id_ikan', $directIkanIds)
                ->whereRaw("DATE_FORMAT(COALESCE(p.tanggal_selesai, p.tanggal_tiba), '%Y-%m') = ?", [$periode])
                ->groupBy('ihp.id_ikan')
                ->selectRaw('ihp.id_ikan as ikan_key, SUM(ihp.berat_hasil) as total_tangkapan')
                ->pluck('total_tangkapan', 'ikan_key');

        $now = now();
        $rows = $targetIkan->map(function ($ikan) use ($catchByIkan, $catchByRelation, $salesByIkan, $salesByRelation, $periode, $now) {
            $relationId = $ikan->id_ikan_tangkapan ? (int) $ikan->id_ikan_tangkapan : null;
            $totalTangkapan = $relationId
                ? (float) ($catchByRelation[$relationId] ?? 0)
                : (float) ($catchByIkan[$ikan->id_ikan] ?? 0);
            $totalPenjualan = $relationId
                ? (float) ($salesByRelation[$relationId] ?? 0)
                : (float) ($salesByIkan[$ikan->id_ikan] ?? 0);

            return [
                'id_ikan' => (int) $ikan->id_ikan,
                'periode' => $periode,
                'total_tangkapan' => $totalTangkapan,
                'total_penjualan' => $totalPenjualan,
                'stok_akhir' => $totalTangkapan - $totalPenjualan,
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
}
