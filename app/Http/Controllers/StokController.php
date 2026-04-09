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

        $latestPeriodePerTangkapan = DB::table('stok_ikan as si')
            ->join('master_ikan as mi', 'mi.id_ikan', '=', 'si.id_ikan')
            ->whereNotNull('mi.id_ikan_tangkapan')
            ->select('mi.id_ikan_tangkapan', DB::raw('MAX(si.periode) as periode'))
            ->groupBy('mi.id_ikan_tangkapan');

        $linkedItems = DB::table('master_ikan_tangkapan as mit')
            ->leftJoinSub($latestPeriodePerTangkapan, 'sipt', function ($join) {
                $join->on('sipt.id_ikan_tangkapan', '=', 'mit.id_ikan_tangkapan');
            })
            ->leftJoin('master_ikan as mi', 'mi.id_ikan_tangkapan', '=', 'mit.id_ikan_tangkapan')
            ->leftJoin('stok_ikan as si', function ($join) {
                $join->on('si.id_ikan', '=', 'mi.id_ikan')
                    ->on('si.periode', '=', 'sipt.periode');
            })
            ->select(
                DB::raw('mit.id_ikan_tangkapan as id_ikan'),
                DB::raw('mit.nama_ikan_tangkapan as nama_ikan'),
                DB::raw('COALESCE(MAX(si.stok_akhir), 0) as stok_aktual'),
                DB::raw("COALESCE(sipt.periode, '-') as periode_terakhir")
            )
            ->groupBy('mit.id_ikan_tangkapan', 'mit.nama_ikan_tangkapan', 'sipt.periode');

        $unmappedItems = DB::table('master_ikan as mi')
            ->leftJoinSub($latestPeriodePerIkan, 'sip', function ($join) {
                $join->on('sip.id_ikan', '=', 'mi.id_ikan');
            })
            ->leftJoin('stok_ikan as si', function ($join) {
                $join->on('si.id_ikan', '=', 'mi.id_ikan')
                    ->on('si.periode', '=', 'sip.periode');
            })
            ->whereNull('mi.id_ikan_tangkapan')
            ->select(
                'mi.id_ikan',
                'mi.nama_ikan',
                DB::raw('COALESCE(si.stok_akhir, 0) as stok_aktual'),
                DB::raw("COALESCE(sip.periode, '-') as periode_terakhir")
            );

        $items = $linkedItems
            ->unionAll($unmappedItems)
            ->orderBy('nama_ikan')
            ->get();

        return view('stok.ikan.index', compact('items'));
    }

    public function barang(): View
    {
        $items = DB::table('master_item_pembelian as mip')
            ->leftJoin('item_pembelian_stock as s', 's.id_item_pembelian', '=', 'mip.id_item_pembelian')
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
