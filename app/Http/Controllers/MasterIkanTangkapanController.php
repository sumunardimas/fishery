<?php

namespace App\Http\Controllers;

use App\Models\MasterIkanTangkapan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterIkanTangkapanController extends Controller
{
    public function index(): View
    {
        $items = MasterIkanTangkapan::query()
            ->orderBy('id_ikan_tangkapan')
            ->get();

        return view('master.ikan-tangkapan.index', compact('items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_ikan_tangkapan' => ['required', 'string', 'max:255'],
        ]);

        MasterIkanTangkapan::create($data);

        return redirect()->route('master.ikan-tangkapan.index')->with('success', 'Master ikan tangkapan berhasil ditambahkan.');
    }

    public function update(Request $request, MasterIkanTangkapan $ikanTangkapan): RedirectResponse
    {
        $data = $request->validate([
            'nama_ikan_tangkapan' => ['required', 'string', 'max:255'],
        ]);

        $ikanTangkapan->update($data);

        return redirect()->route('master.ikan-tangkapan.index')->with('success', 'Master ikan tangkapan berhasil diperbarui.');
    }

    public function destroy(MasterIkanTangkapan $ikanTangkapan): RedirectResponse
    {
        $ikanTangkapan->delete();

        return redirect()->route('master.ikan-tangkapan.index')->with('success', 'Master ikan tangkapan berhasil dihapus.');
    }
}
