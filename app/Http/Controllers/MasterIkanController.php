<?php

namespace App\Http\Controllers;

use App\Models\MasterIkan;
use App\Models\MasterIkanTangkapan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MasterIkanController extends Controller
{
    public function index(): View
    {
        $items = MasterIkan::query()
            ->with('ikanTangkapan')
            ->orderByDesc('created_at')
            ->get();

        $ikanTangkapanOptions = MasterIkanTangkapan::query()
            ->orderBy('nama_ikan_tangkapan')
            ->get();

        return view('master.ikan.index', compact('items', 'ikanTangkapanOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_ikan' => ['required', 'string', 'max:255'],
            'id_ikan_tangkapan' => ['nullable', 'integer', 'exists:master_ikan_tangkapan,id_ikan_tangkapan'],
        ]);

        MasterIkan::create($data);

        return redirect()->route('master.ikan.index')->with('success', 'Master ikan berhasil ditambahkan.');
    }

    public function update(Request $request, MasterIkan $ikan): RedirectResponse
    {
        $data = $request->validate([
            'nama_ikan' => ['required', 'string', 'max:255'],
            'id_ikan_tangkapan' => ['nullable', 'integer', 'exists:master_ikan_tangkapan,id_ikan_tangkapan'],
        ]);

        $ikan->update($data);

        return redirect()->route('master.ikan.index')->with('success', 'Master ikan berhasil diperbarui.');
    }

    public function destroy(MasterIkan $ikan): RedirectResponse
    {
        $idIkan = (int) $ikan->id_ikan;

        $usageMap = [
            'bongkaran' => 'bongkaran',
            'penjualan' => 'penjualan',
            'ikan_hasil_pelayaran' => 'ikan hasil pelayaran',
            'stok_ikan' => 'stok ikan',
        ];

        $usedIn = [];

        foreach ($usageMap as $table => $label) {
            if (DB::table($table)->where('id_ikan', $idIkan)->exists()) {
                $usedIn[] = $label;
            }
        }

        if ($usedIn !== []) {
            return redirect()->route('master.ikan.index')->withErrors([
                'message' => 'Data tidak bisa dihapus karena sudah digunakan pada: '.implode(', ', $usedIn).'.',
            ]);
        }

        $ikan->delete();

        return redirect()->route('master.ikan.index')->with('success', 'Master ikan berhasil dihapus.');
    }
}
