<?php

namespace App\Http\Controllers;

use App\Models\MasterIkan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MasterIkanController extends Controller
{
    public function index(): View
    {
        $items = MasterIkan::query()->orderByDesc('created_at')->get();

        return view('master.ikan.index', compact('items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_ikan' => ['required', 'string', 'max:255'],
            'jenis_ikan' => ['required', 'string', 'max:255'],
            'harga_default' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['required', 'string'],
        ]);

        MasterIkan::create($data);

        return redirect()->route('master.ikan.index')->with('success', 'Master ikan berhasil ditambahkan.');
    }

    public function update(Request $request, MasterIkan $ikan): RedirectResponse
    {
        $data = $request->validate([
            'nama_ikan' => ['required', 'string', 'max:255'],
            'jenis_ikan' => ['required', 'string', 'max:255'],
            'harga_default' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['required', 'string'],
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
