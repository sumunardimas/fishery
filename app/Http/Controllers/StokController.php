<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StokController extends Controller
{
    public function ikan(): View
    {
        $storages = DB::table('storage_ikan as si')
            ->join('kapal as k', 'k.id_kapal', '=', 'si.id_kapal')
            ->select('si.id_storage', 'si.nama_storage', 'k.nama_kapal')
            ->orderBy('k.nama_kapal')
            ->get();

        $groupedFish = DB::table('master_ikan_tangkapan')
            ->orderBy('nama_ikan_tangkapan')
            ->get(['id_ikan_tangkapan', 'nama_ikan_tangkapan'])
            ->map(function ($item) {
                return (object) [
                    'row_key' => 'group:' . $item->id_ikan_tangkapan,
                    'id_ikan' => (int) $item->id_ikan_tangkapan,
                    'nama_ikan' => $item->nama_ikan_tangkapan,
                ];
            });

        $directFish = DB::table('master_ikan')
            ->whereNull('id_ikan_tangkapan')
            ->orderBy('nama_ikan')
            ->get(['id_ikan', 'nama_ikan'])
            ->map(function ($item) {
                return (object) [
                    'row_key' => 'single:' . $item->id_ikan,
                    'id_ikan' => (int) $item->id_ikan,
                    'nama_ikan' => $item->nama_ikan,
                ];
            });

        $fishDefinitions = $groupedFish
            ->concat($directFish)
            ->sortBy('nama_ikan', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $stockRows = DB::table('stok_ikan_storage as sis')
            ->join('master_ikan as mi', 'mi.id_ikan', '=', 'sis.id_ikan')
            ->selectRaw(
                "CASE
                    WHEN mi.id_ikan_tangkapan IS NULL THEN CONCAT('single:', mi.id_ikan)
                    ELSE CONCAT('group:', mi.id_ikan_tangkapan)
                END as row_key,
                sis.id_storage,
                SUM(sis.stok_aktual) as stok_aktual"
            )
            ->groupBy('row_key', 'sis.id_storage')
            ->get();

        $stockByKey = $stockRows
            ->groupBy('row_key')
            ->map(function ($rows) {
                return $rows->mapWithKeys(function ($row) {
                    return [(int) $row->id_storage => (float) $row->stok_aktual];
                });
            });

        $items = $fishDefinitions->map(function ($fish) use ($storages, $stockByKey) {
            $stokPerStorage = $storages->mapWithKeys(function ($storage) use ($fish, $stockByKey) {
                return [(int) $storage->id_storage => (float) ($stockByKey[$fish->row_key][(int) $storage->id_storage] ?? 0)];
            })->all();

            return (object) [
                'id_ikan' => $fish->id_ikan,
                'nama_ikan' => $fish->nama_ikan,
                'stok_per_storage' => $stokPerStorage,
                'stok_aktual' => array_sum($stokPerStorage),
            ];
        });

        return view('stok.ikan.index', compact('items', 'storages'));
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

    public function laporanBarangGudang(): View
    {
        $barang = DB::table('master_item_pembelian as item')
            ->leftJoin('item_pembelian_stock as stock', 'stock.id_item_pembelian', '=', 'item.id_item_pembelian')
            ->select([
                DB::raw("'Barang' as jenis"),
                'item.nama_item as nama',
                'item.kategori',
                'item.satuan',
                'item.limit_minimal',
                DB::raw('COALESCE(SUM(stock.stok_aktual), 0) as stok_aktual'),
            ])
            ->groupBy('item.id_item_pembelian', 'item.nama_item', 'item.kategori', 'item.satuan', 'item.limit_minimal');

        $items = DB::table('master_perbekalan as item')
            ->leftJoin('perbekalan_stock as stock', 'stock.id_barang', '=', 'item.id_barang')
            ->select([
                DB::raw("'Perbekalan' as jenis"),
                'item.nama_barang as nama',
                DB::raw("'-' as kategori"),
                'item.satuan',
                'item.limit_minimal',
                DB::raw('COALESCE(SUM(stock.stok_aktual), 0) as stok_aktual'),
            ])
            ->groupBy('item.id_barang', 'item.nama_barang', 'item.satuan', 'item.limit_minimal')
            ->unionAll($barang)
            ->get()
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(function ($item) {
                $stok = (float) $item->stok_aktual;
                $limit = (float) $item->limit_minimal;

                $item->status = $limit <= 0
                    ? 'Belum diatur'
                    : ($stok <= $limit ? 'Stok rendah' : 'Aman');

                return $item;
            });

        $summary = [
            'total_item' => $items->count(),
            'barang' => $items->where('jenis', 'Barang')->count(),
            'perbekalan' => $items->where('jenis', 'Perbekalan')->count(),
            'stok_rendah' => $items->where('status', 'Stok rendah')->count(),
        ];

        return view('stok.laporan-barang-gudang', compact('items', 'summary'));
    }
}
