<?php

namespace App\Http\Controllers;

use App\Models\Kapal;
use App\Models\Pelayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PelayaranController extends Controller
{
    /**
     * Display active pelayaran cards and full list.
     */
    public function index(): View
    {
        $today = now()->toDateString();

        $kapals = Kapal::query()->orderBy('nama_kapal')->get();
        $pelayaran = Pelayaran::query()
            ->with('kapal')
            ->orderByDesc('tanggal_berangkat')
            ->get();

        $activePelayaran = $pelayaran->filter(function (Pelayaran $item) use ($today) {
            return $item->tanggal_berangkat?->toDateString() <= $today
                && $item->tanggal_tiba?->toDateString() >= $today;
        })->values();

        return view('pelayaran.index', compact('kapals', 'pelayaran', 'activePelayaran'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        $kapals = Kapal::query()->orderBy('nama_kapal')->get();
        $masterPerbekalan = DB::table('master_perbekalan')->orderBy('nama_barang')->get();
        $selectedPerbekalan = [];

        return view('pelayaran.create', compact('kapals', 'masterPerbekalan', 'selectedPerbekalan'));
    }

    /**
     * Store new pelayaran.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $perbekalanQty = $this->extractPerbekalanQty($request);

        if ($this->hasScheduleConflict(
            (int) $data['id_kapal'],
            $data['tanggal_berangkat'],
            $data['tanggal_tiba']
        )) {
            return back()
                ->withInput()
                ->withErrors(['tanggal_berangkat' => 'Jadwal bentrok: kapal sudah dipakai di rentang tanggal tersebut.']);
        }

        DB::transaction(function () use ($data, $perbekalanQty) {
            $pelayaran = Pelayaran::create($data);
            $this->syncPerbekalanPelayaran((int) $pelayaran->id_pelayaran, $perbekalanQty);
        });

        return redirect()->route('pelayaran.index')->with('success', 'Rencana pelayaran berhasil ditambahkan.');
    }

    /**
     * Show edit form.
     */
    public function edit(Pelayaran $pelayaran): View
    {
        $kapals = Kapal::query()->orderBy('nama_kapal')->get();
        $masterPerbekalan = DB::table('master_perbekalan')->orderBy('nama_barang')->get();
        $selectedPerbekalan = DB::table('perbekalan_pelayaran')
            ->where('id_pelayaran', $pelayaran->id_pelayaran)
            ->pluck('jumlah', 'id_barang')
            ->map(fn ($value) => (float) $value)
            ->toArray();

        return view('pelayaran.edit', compact('pelayaran', 'kapals', 'masterPerbekalan', 'selectedPerbekalan'));
    }

    /**
     * Update pelayaran.
     */
    public function update(Request $request, Pelayaran $pelayaran): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $perbekalanQty = $this->extractPerbekalanQty($request);

        if ($this->hasScheduleConflict(
            (int) $data['id_kapal'],
            $data['tanggal_berangkat'],
            $data['tanggal_tiba'],
            (int) $pelayaran->id_pelayaran
        )) {
            return back()
                ->withInput()
                ->withErrors(['tanggal_berangkat' => 'Jadwal bentrok: kapal sudah dipakai di rentang tanggal tersebut.']);
        }

        DB::transaction(function () use ($pelayaran, $data, $perbekalanQty) {
            $pelayaran->update($data);
            $this->syncPerbekalanPelayaran((int) $pelayaran->id_pelayaran, $perbekalanQty);
        });

        return redirect()->route('pelayaran.index')->with('success', 'Rencana pelayaran berhasil diperbarui.');
    }

    /**
     * Delete pelayaran only when no dependent transactions exist.
     */
    public function destroy(Pelayaran $pelayaran): RedirectResponse
    {
        $idPelayaran = (int) $pelayaran->id_pelayaran;

        $hasDependency =
            DB::table('perbekalan')->where('id_pelayaran', $idPelayaran)->exists()
            || DB::table('bongkaran')->where('id_pelayaran', $idPelayaran)->exists()
            || DB::table('operasional')->where('id_pelayaran', $idPelayaran)->exists()
            || DB::table('pendapatan')->where('id_pelayaran', $idPelayaran)->exists()
            || DB::table('sisa_trip')->where('id_pelayaran', $idPelayaran)->exists()
            || DB::table('laporan_selisih_bongkaran')->where('id_pelayaran', $idPelayaran)->exists();

        if ($hasDependency) {
            return back()->withErrors([
                'message' => 'Pelayaran tidak dapat dihapus karena sudah dipakai pada transaksi operasional.',
            ]);
        }

        $pelayaran->delete();

        return redirect()->route('pelayaran.index')->with('success', 'Rencana pelayaran berhasil dihapus.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'id_kapal' => ['required', 'integer', 'exists:kapal,id_kapal'],
            'tanggal_berangkat' => ['required', 'date'],
            'tanggal_tiba' => ['required', 'date', 'after_or_equal:tanggal_berangkat'],
            'pelabuhan_asal' => ['required', 'string', 'max:255'],
            'pelabuhan_tujuan' => ['required', 'string', 'max:255'],
            'jumlah_trip' => ['required', 'integer', 'min:1'],
            'keterangan' => ['required', 'string'],
            'perbekalan_qty' => ['nullable', 'array'],
            'perbekalan_qty.*' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    /**
     * Keep only filled perbekalan rows to keep storage efficient.
     */
    private function extractPerbekalanQty(Request $request): Collection
    {
        $qtyMap = collect($request->input('perbekalan_qty', []))
            ->mapWithKeys(function ($jumlah, $idBarang) {
                return [(int) $idBarang => (float) $jumlah];
            })
            ->filter(fn (float $jumlah) => $jumlah > 0);

        if ($qtyMap->isEmpty()) {
            return collect();
        }

        $validBarangIds = DB::table('master_perbekalan')
            ->whereIn('id_barang', $qtyMap->keys()->all())
            ->pluck('id_barang')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $qtyMap->only($validBarangIds);
    }

    private function syncPerbekalanPelayaran(int $idPelayaran, Collection $perbekalanQty): void
    {
        DB::table('perbekalan_pelayaran')
            ->where('id_pelayaran', $idPelayaran)
            ->delete();

        if ($perbekalanQty->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $perbekalanQty->map(function (float $jumlah, int $idBarang) use ($idPelayaran, $now) {
            return [
                'id_pelayaran' => $idPelayaran,
                'id_barang' => $idBarang,
                'jumlah' => $jumlah,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();

        DB::table('perbekalan_pelayaran')->insert($rows);
    }

    private function hasScheduleConflict(
        int $idKapal,
        string $tanggalBerangkat,
        string $tanggalTiba,
        ?int $ignorePelayaranId = null
    ): bool {
        $query = Pelayaran::query()
            ->where('id_kapal', $idKapal)
            ->whereDate('tanggal_berangkat', '<=', $tanggalTiba)
            ->whereDate('tanggal_tiba', '>=', $tanggalBerangkat);

        if ($ignorePelayaranId !== null) {
            $query->where('id_pelayaran', '!=', $ignorePelayaranId);
        }

        return $query->exists();
    }
}
