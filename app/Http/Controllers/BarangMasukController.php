<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BarangMasukController extends Controller
{
    public function index(): View
    {
        $itemPembelian = DB::table('master_item_pembelian')
            ->select('id_item_pembelian as id', 'nama_item as nama', 'satuan')
            ->orderBy('nama_item')
            ->get();

        $perbekalan = DB::table('master_perbekalan')
            ->select('id_barang as id', 'nama_barang as nama', 'satuan')
            ->orderBy('nama_barang')
            ->get();

        return view('barang-masuk.index', compact('itemPembelian', 'perbekalan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $item = $request->validate([
            'item' => ['required', 'string', 'regex:/^(pembelian|perbekalan):[1-9][0-9]*$/'],
        ])['item'];

        [$jenisItem, $id] = explode(':', $item, 2);

        if ($jenisItem === 'pembelian') {
            $request->merge(['id_item_pembelian' => (int) $id]);
            (new PembelianController)->storeTransaction($request);
        } elseif ($jenisItem === 'perbekalan') {
            $request->merge(['id_barang' => (int) $id]);
            (new MasterPerbekalanController)->storeTransaction($request);
        } else {
            throw ValidationException::withMessages(['item' => 'Jenis item tidak valid.']);
        }

        return redirect()->route('barang-masuk.index')
            ->with('success', 'Barang masuk berhasil dicatat.');
    }
}
