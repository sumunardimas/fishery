<?php

namespace App\Http\Controllers;

use App\Models\MasterPerbekalan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MasterPerbekalanController extends Controller
{
    public function index(): View
    {
        $items = MasterPerbekalan::query()->orderByDesc('created_at')->get();

        return view('master.perbekalan.index', compact('items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:100'],
            'harga_default' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['required', 'string'],
        ]);

        MasterPerbekalan::create($data);

        return redirect()->route('master.perbekalan.index')->with('success', 'Master perbekalan berhasil ditambahkan.');
    }

    public function update(Request $request, MasterPerbekalan $perbekalan): RedirectResponse
    {
        $data = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:100'],
            'harga_default' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['required', 'string'],
        ]);

        $perbekalan->update($data);

        return redirect()->route('master.perbekalan.index')->with('success', 'Master perbekalan berhasil diperbarui.');
    }

    public function destroy(MasterPerbekalan $perbekalan): RedirectResponse
    {
        $idBarang = (int) $perbekalan->id_barang;

        $usageMap = [
            'perbekalan' => 'perbekalan',
            'pembelian_barang' => 'pembelian barang',
            'pemakaian_barang_kantor' => 'pemakaian barang kantor',
            'sisa_trip' => 'sisa trip',
            'perbekalan_pelayaran' => 'perbekalan pelayaran',
        ];

        $usedIn = [];

        foreach ($usageMap as $table => $label) {
            if (DB::table($table)->where('id_barang', $idBarang)->exists()) {
                $usedIn[] = $label;
            }
        }

        if ($usedIn !== []) {
            return redirect()->route('master.perbekalan.index')->withErrors([
                'message' => 'Data tidak bisa dihapus karena sudah digunakan pada: '.implode(', ', $usedIn).'.',
            ]);
        }

        $perbekalan->delete();

        return redirect()->route('master.perbekalan.index')->with('success', 'Master perbekalan berhasil dihapus.');
    }
}
