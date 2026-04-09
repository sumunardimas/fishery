<?php

namespace App\Http\Controllers;

use App\Models\Kapal;
use App\Models\Pelayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PelayaranController extends Controller
{
    /**
     * Display active pelayaran cards and full list.
     */
    public function index(): View
    {
        $kapals = Kapal::query()->orderBy('nama_kapal')->get();
        $pelayaran = Pelayaran::query()
            ->with('kapal')
            ->orderByDesc('tanggal_berangkat')
            ->get();

        $activePelayaran = $pelayaran
            ->where('status_pelayaran', 'aktif')
            ->values();

        $inactivePelayaran = $pelayaran
            ->where('status_pelayaran', 'selesai')
            ->values();

        return view('pelayaran.index', compact('kapals', 'pelayaran', 'activePelayaran', 'inactivePelayaran'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        $kapals = $this->getSelectableKapals();
        $masterPerbekalan = $this->getMasterPerbekalanWithStock();
        $selectedPerbekalan = [];

        return view('pelayaran.create', compact('kapals', 'masterPerbekalan', 'selectedPerbekalan'));
    }

    /**
     * Store new pelayaran.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $this->ensureKapalIsSelectable((int) $data['id_kapal']);
        $perbekalanQty = $this->extractPerbekalanQty($request);
        $this->validatePerbekalanAvailability($perbekalanQty);

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
            $data['status_pelayaran'] = 'aktif';
            $data['tanggal_selesai'] = null;
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
        $kapals = $this->getSelectableKapals((int) $pelayaran->id_kapal);
        $masterPerbekalan = $this->getMasterPerbekalanWithStock();
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
        $this->ensureKapalIsSelectable((int) $data['id_kapal'], (int) $pelayaran->id_kapal);
        $perbekalanQty = $this->extractPerbekalanQty($request);
        $this->validatePerbekalanAvailability($perbekalanQty);

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
            // Editing route keeps trip as active planning unless closed via /pelayaran/sisa.
            $data['status_pelayaran'] = $pelayaran->status_pelayaran;
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
        $data = $request->validate([
            'id_kapal' => ['required', 'integer', 'exists:kapal,id_kapal'],
            'tanggal_berangkat' => ['required', 'date'],
            'tanggal_tiba' => ['required', 'date', 'after_or_equal:tanggal_berangkat'],
            'jumlah_trip' => ['nullable', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string'],
            'perbekalan_qty' => ['nullable', 'array'],
            'perbekalan_qty.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['jumlah_trip'] = max(1, (int) ($data['jumlah_trip'] ?? 1));
        $data['keterangan'] = (string) ($data['keterangan'] ?? '');

        return $data;
    }

    private function getSelectableKapals(?int $currentKapalId = null)
    {
        return Kapal::query()
            ->where(function ($query) use ($currentKapalId) {
                $query->whereDoesntHave('pelayaran', function ($pelayaranQuery) {
                    $pelayaranQuery->where('status_pelayaran', 'aktif');
                });

                if ($currentKapalId !== null) {
                    $query->orWhere('id_kapal', $currentKapalId);
                }
            })
            ->orderBy('nama_kapal')
            ->get();
    }

    private function ensureKapalIsSelectable(int $idKapal, ?int $currentKapalId = null): void
    {
        if ($currentKapalId !== null && $idKapal === $currentKapalId) {
            return;
        }

        $isStillSailing = Pelayaran::query()
            ->where('id_kapal', $idKapal)
            ->where('status_pelayaran', 'aktif')
            ->exists();

        if ($isStillSailing) {
            throw ValidationException::withMessages([
                'id_kapal' => 'Kapal masih dalam pelayaran aktif dan belum bisa dipilih.',
            ]);
        }
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

    private function validatePerbekalanAvailability(Collection $perbekalanQty): void
    {
        if ($perbekalanQty->isEmpty()) {
            return;
        }

        $stockMap = DB::table('perbekalan_stock')
            ->whereIn('id_barang', $perbekalanQty->keys()->all())
            ->pluck('stok_aktual', 'id_barang')
            ->map(fn ($stok) => (float) $stok);

        $nameMap = DB::table('master_perbekalan')
            ->whereIn('id_barang', $perbekalanQty->keys()->all())
            ->pluck('nama_barang', 'id_barang');

        $errors = [];

        foreach ($perbekalanQty as $idBarang => $qty) {
            $available = (float) ($stockMap[$idBarang] ?? 0);
            if ((float) $qty > $available) {
                $namaBarang = (string) ($nameMap[$idBarang] ?? ('ID '.$idBarang));
                $errors['perbekalan_qty.'.$idBarang] =
                    'Jumlah '.$namaBarang.' melebihi stok. Stok tersedia: '.number_format($available, 2, ',', '.').'.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function getMasterPerbekalanWithStock()
    {
        return DB::table('master_perbekalan as mp')
            ->leftJoin('perbekalan_stock as ps', 'ps.id_barang', '=', 'mp.id_barang')
            ->select(
                'mp.id_barang',
                'mp.nama_barang',
                'mp.satuan',
                DB::raw('COALESCE(ps.stok_aktual, 0) as stok_aktual')
            )
            ->orderBy('mp.nama_barang')
            ->get();
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
