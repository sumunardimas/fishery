<?php

namespace App\Http\Controllers;

use App\Models\Pelayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PelayaranSisaController extends Controller
{
    /**
     * Show active pelayaran and sisa form for selected pelayaran.
     */
    public function index(Request $request): View
    {
        $activePelayaran = Pelayaran::query()
            ->with('kapal')
            ->where('status_pelayaran', 'aktif')
            ->orderBy('tanggal_berangkat')
            ->get();

        $selectedPelayaranId = (int) ($request->integer('pelayaran_id') ?: ($activePelayaran->first()->id_pelayaran ?? 0));

        $selectedPelayaran = $activePelayaran
            ->firstWhere('id_pelayaran', $selectedPelayaranId);

        $perbekalanRows = collect();
        $existingSisa = [];
        $masterIkan = DB::table('master_ikan')->orderBy('nama_ikan')->get();
        $existingHasilIkan = [];

        if ($selectedPelayaran) {
            $perbekalanRows = DB::table('perbekalan_pelayaran as pp')
                ->join('master_perbekalan as mp', 'mp.id_barang', '=', 'pp.id_barang')
                ->where('pp.id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->orderBy('mp.nama_barang')
                ->select('pp.id_barang', 'mp.nama_barang', 'mp.kategori', 'mp.satuan', 'pp.jumlah as jumlah_awal')
                ->get();

            $existingSisa = DB::table('sisa_trip')
                ->where('id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->pluck('jumlah_sisa', 'id_barang')
                ->map(fn ($value) => (float) $value)
                ->toArray();

            $existingHasilIkan = DB::table('ikan_hasil_pelayaran')
                ->where('id_pelayaran', $selectedPelayaran->id_pelayaran)
                ->pluck('berat_hasil', 'id_ikan')
                ->map(fn ($value) => (float) $value)
                ->toArray();
        }

        return view('pelayaran.sisa.index', compact(
            'activePelayaran',
            'selectedPelayaran',
            'perbekalanRows',
            'existingSisa',
            'masterIkan',
            'existingHasilIkan'
        ));
    }

    /**
     * Close pelayaran and store remaining supplies + fish captures.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_pelayaran' => ['required', 'integer', 'exists:pelayaran,id_pelayaran'],
            'sisa_qty' => ['nullable', 'array'],
            'sisa_qty.*' => ['nullable', 'numeric', 'min:0'],
            'hasil_ikan' => ['nullable', 'array'],
            'hasil_ikan.*' => ['nullable', 'numeric', 'min:0'],
            'catatan_sisa' => ['nullable', 'string'],
        ]);

        $idPelayaran = (int) $validated['id_pelayaran'];

        $pelayaran = Pelayaran::query()
            ->where('id_pelayaran', $idPelayaran)
            ->firstOrFail();

        if ($pelayaran->status_pelayaran !== 'aktif') {
            return back()->withErrors([
                'message' => 'Pelayaran sudah ditutup sebelumnya.',
            ]);
        }

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

        $hasilIkan = $this->extractNumericMap($request->input('hasil_ikan', []), false);
        $validIkanIds = DB::table('master_ikan')->pluck('id_ikan')->map(fn ($id) => (int) $id)->all();
        $hasilIkan = $hasilIkan->only($validIkanIds)->filter(fn (float $berat) => $berat > 0);

        DB::transaction(function () use ($pelayaran, $idPelayaran, $sisaQty, $hasilIkan, $validated) {
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

            DB::table('ikan_hasil_pelayaran')->where('id_pelayaran', $idPelayaran)->delete();

            if ($hasilIkan->isNotEmpty()) {
                $now = now();
                $hasilRows = $hasilIkan->map(function (float $beratHasil, int $idIkan) use ($idPelayaran, $now) {
                    return [
                        'id_pelayaran' => $idPelayaran,
                        'id_ikan' => $idIkan,
                        'berat_hasil' => $beratHasil,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->values()->all();

                DB::table('ikan_hasil_pelayaran')->insert($hasilRows);
            }

            $pelayaran->update([
                'status_pelayaran' => 'selesai',
                'tanggal_selesai' => now()->toDateString(),
            ]);

            $periode = now()->format('Y-m');
            $this->recalculateStokIkan($periode, $hasilIkan->keys()->map(fn ($id) => (int) $id)->all());
        });

        return redirect()
            ->route('pelayaran.index')
            ->with('success', 'Sisa trip dan hasil tangkapan berhasil disimpan. Status pelayaran dipindahkan ke nonaktif.');
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
        if (empty($affectedIkanIds)) {
            return;
        }

        $salesByIkan = DB::table('penjualan')
            ->whereIn('id_ikan', $affectedIkanIds)
            ->whereRaw("DATE_FORMAT(tanggal_penjualan, '%Y-%m') = ?", [$periode])
            ->groupBy('id_ikan')
            ->selectRaw('id_ikan, SUM(berat) as total_penjualan')
            ->pluck('total_penjualan', 'id_ikan');

        $catchByIkan = DB::table('ikan_hasil_pelayaran as ihp')
            ->join('pelayaran as p', 'p.id_pelayaran', '=', 'ihp.id_pelayaran')
            ->whereIn('ihp.id_ikan', $affectedIkanIds)
            ->whereRaw("DATE_FORMAT(COALESCE(p.tanggal_selesai, p.tanggal_tiba), '%Y-%m') = ?", [$periode])
            ->groupBy('ihp.id_ikan')
            ->selectRaw('ihp.id_ikan, SUM(ihp.berat_hasil) as total_tangkapan')
            ->pluck('total_tangkapan', 'id_ikan');

        $now = now();
        $rows = collect($affectedIkanIds)->map(function (int $idIkan) use ($catchByIkan, $salesByIkan, $periode, $now) {
            $totalTangkapan = (float) ($catchByIkan[$idIkan] ?? 0);
            $totalPenjualan = (float) ($salesByIkan[$idIkan] ?? 0);

            return [
                'id_ikan' => $idIkan,
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
