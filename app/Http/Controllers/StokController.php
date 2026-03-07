<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StokController extends Controller
{
    public function ikan(): View
    {
        $latestPeriodePerIkan = DB::table('stok_ikan')
            ->select('id_ikan', DB::raw('MAX(periode) as periode'))
            ->groupBy('id_ikan');

        $items = DB::table('master_ikan as mi')
            ->leftJoinSub($latestPeriodePerIkan, 'sip', function ($join) {
                $join->on('sip.id_ikan', '=', 'mi.id_ikan');
            })
            ->leftJoin('stok_ikan as si', function ($join) {
                $join->on('si.id_ikan', '=', 'mi.id_ikan')
                    ->on('si.periode', '=', 'sip.periode');
            })
            ->select(
                'mi.id_ikan',
                'mi.nama_ikan',
                'mi.jenis_ikan',
                'mi.harga_default',
                DB::raw('COALESCE(si.stok_akhir, 0) as stok_aktual'),
                DB::raw("COALESCE(sip.periode, '-') as periode_terakhir")
            )
            ->orderBy('mi.nama_ikan')
            ->get();

        return view('stok.ikan.index', compact('items'));
    }

    public function barang(): View
    {
        $items = DB::table('master_item_pembelian as mip')
            ->leftJoin('gudang_item_pembelian_stock as s', 's.id_item_pembelian', '=', 'mip.id_item_pembelian')
            ->select(
                'mip.id_item_pembelian',
                'mip.nama_item',
                'mip.kategori',
                'mip.satuan',
                DB::raw('COALESCE(SUM(s.stok_aktual), 0) as stok_aktual')
            )
            ->groupBy('mip.id_item_pembelian', 'mip.nama_item', 'mip.kategori', 'mip.satuan')
            ->orderBy('mip.nama_item')
            ->get();

        return view('stok.barang.index', compact('items'));
    }
}
