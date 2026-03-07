<?php

namespace App\Http\Controllers;

use App\Models\Kapal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KapalController extends Controller
{
    /**
     * Display a listing of kapal as cards.
     */
    public function index(): View
    {
        $kapals = Kapal::query()->orderByDesc('created_at')->get();

        return view('kapal.index', compact('kapals'));
    }

    /**
     * Show form to create kapal.
     */
    public function create(): View
    {
        return view('kapal.create');
    }

    /**
     * Store a newly created kapal.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_kapal' => ['required', 'string', 'max:255'],
            'tahun_dibangun' => ['required', 'integer', 'between:1900,2100'],
            'gross_tonnage' => ['required', 'numeric', 'min:0'],
            'deadweight_tonnage' => ['required', 'numeric', 'min:0'],
            'panjang_meter' => ['required', 'numeric', 'min:0'],
            'lebar_meter' => ['required', 'numeric', 'min:0'],
        ]);

        Kapal::create($data);

        return redirect()->route('kapal.index')->with('success', 'Data kapal berhasil ditambahkan.');
    }

    /**
     * Show form to edit kapal.
     */
    public function edit(Kapal $kapal): View
    {
        return view('kapal.edit', compact('kapal'));
    }

    /**
     * Update kapal in storage.
     */
    public function update(Request $request, Kapal $kapal): RedirectResponse
    {
        $data = $request->validate([
            'nama_kapal' => ['required', 'string', 'max:255'],
            'tahun_dibangun' => ['required', 'integer', 'between:1900,2100'],
            'gross_tonnage' => ['required', 'numeric', 'min:0'],
            'deadweight_tonnage' => ['required', 'numeric', 'min:0'],
            'panjang_meter' => ['required', 'numeric', 'min:0'],
            'lebar_meter' => ['required', 'numeric', 'min:0'],
        ]);

        $kapal->update($data);

        return redirect()->route('kapal.index')->with('success', 'Data kapal berhasil diperbarui.');
    }

    /**
     * Remove kapal from storage.
     */
    public function destroy(Kapal $kapal): RedirectResponse
    {
        $kapal->delete();

        return redirect()->route('kapal.index')->with('success', 'Data kapal berhasil dihapus.');
    }
}
