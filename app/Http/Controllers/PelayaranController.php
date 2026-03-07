<?php

namespace App\Http\Controllers;

use App\Models\Kapal;
use App\Models\Pelayaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('pelayaran.create', compact('kapals'));
    }

    /**
     * Store new pelayaran.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        if ($this->hasScheduleConflict(
            (int) $data['id_kapal'],
            $data['tanggal_berangkat'],
            $data['tanggal_tiba']
        )) {
            return back()
                ->withInput()
                ->withErrors(['tanggal_berangkat' => 'Jadwal bentrok: kapal sudah dipakai di rentang tanggal tersebut.']);
        }

        Pelayaran::create($data);

        return redirect()->route('pelayaran.index')->with('success', 'Rencana pelayaran berhasil ditambahkan.');
    }

    /**
     * Show edit form.
     */
    public function edit(Pelayaran $pelayaran): View
    {
        $kapals = Kapal::query()->orderBy('nama_kapal')->get();

        return view('pelayaran.edit', compact('pelayaran', 'kapals'));
    }

    /**
     * Update pelayaran.
     */
    public function update(Request $request, Pelayaran $pelayaran): RedirectResponse
    {
        $data = $this->validatePayload($request);

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

        $pelayaran->update($data);

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
        ]);
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
