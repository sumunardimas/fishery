<?php

namespace App\Http\Controllers;

use App\Models\MasterItemPembelian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterItemPembelianController extends Controller
{
    public function index(): View
    {
        $items = MasterItemPembelian::query()->orderByDesc('created_at')->get();

        return view('master.item-pembelian.index', compact('items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_item' => ['required', 'string', 'max:255', 'unique:master_item_pembelian,nama_item'],
            'kategori' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
        ]);

        MasterItemPembelian::create($data);

        return redirect()->route('master.item-pembelian.index')->with('success', 'Master item pembelian berhasil ditambahkan.');
    }

    public function update(Request $request, MasterItemPembelian $itemPembelian): RedirectResponse
    {
        $data = $request->validate([
            'nama_item' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_item_pembelian', 'nama_item')->ignore($itemPembelian->id_item_pembelian, 'id_item_pembelian'),
            ],
            'kategori' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $itemPembelian->update($data);

        return redirect()->route('master.item-pembelian.index')->with('success', 'Master item pembelian berhasil diperbarui.');
    }

    public function destroy(MasterItemPembelian $itemPembelian): RedirectResponse
    {
        $idItem = (int) $itemPembelian->id_item_pembelian;

        $usageMap = [
            'pembelian_transaction' => 'transaksi pembelian',
            'gudang_item_pembelian_stock' => 'stok gudang',
        ];

        $usedIn = [];

        foreach ($usageMap as $table => $label) {
            if (DB::table($table)->where('id_item_pembelian', $idItem)->exists()) {
                $usedIn[] = $label;
            }
        }

        if ($usedIn !== []) {
            return redirect()->route('master.item-pembelian.index')->withErrors([
                'message' => 'Data tidak bisa dihapus karena sudah digunakan pada: '.implode(', ', $usedIn).'.',
            ]);
        }

        $itemPembelian->delete();

        return redirect()->route('master.item-pembelian.index')->with('success', 'Master item pembelian berhasil dihapus.');
    }
}
