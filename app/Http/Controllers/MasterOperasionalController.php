<?php

namespace App\Http\Controllers;

use App\Models\MasterOperasional;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterOperasionalController extends Controller
{
    public function index(): View
    {
        $items = MasterOperasional::query()->orderByDesc('created_at')->get();

        return view('master.operasional.index', compact('items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_operasional' => ['required', 'string', 'max:255', 'unique:master_operasional,nama_operasional'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        MasterOperasional::create($data);

        return redirect()->route('master.operasional.index')->with('success', 'Master operasional berhasil ditambahkan.');
    }

    public function update(Request $request, MasterOperasional $operasional): RedirectResponse
    {
        $data = $request->validate([
            'nama_operasional' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_operasional', 'nama_operasional')->ignore($operasional->id_master_operasional, 'id_master_operasional'),
            ],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $operasional->update($data);

        return redirect()->route('master.operasional.index')->with('success', 'Master operasional berhasil diperbarui.');
    }

    public function destroy(MasterOperasional $operasional): RedirectResponse
    {
        $idMasterOperasional = (int) $operasional->id_master_operasional;

        if (DB::table('operasional')->where('id_master_operasional', $idMasterOperasional)->exists()) {
            return redirect()->route('master.operasional.index')->withErrors([
                'message' => 'Data tidak bisa dihapus karena sudah digunakan pada transaksi operasional.',
            ]);
        }

        $operasional->delete();

        return redirect()->route('master.operasional.index')->with('success', 'Master operasional berhasil dihapus.');
    }
}
