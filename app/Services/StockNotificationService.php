<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockNotificationService
{
    private const WARNING_BUFFER = 0.20;

    /**
     * Central notification feed. Additional notification sources can be
     * concatenated here later without changing the navbar or notification page.
     */
    public function notifications(): Collection
    {
        return $this->inventoryNotifications()
            ->sortBy([
                ['severity_order', 'asc'],
                ['stock_ratio', 'asc'],
                ['item_name', 'asc'],
            ])
            ->values();
    }

    private function inventoryNotifications(): Collection
    {
        $barang = DB::table('master_item_pembelian as item')
            ->leftJoin('item_pembelian_stock as stock', 'stock.id_item_pembelian', '=', 'item.id_item_pembelian')
            ->where('item.limit_minimal', '>', 0)
            ->select(
                'item.id_item_pembelian as item_id',
                'item.nama_item as item_name',
                'item.satuan',
                'item.limit_minimal',
                DB::raw('COALESCE(stock.stok_aktual, 0) as current_stock')
            )
            ->get()
            ->map(fn ($item) => $this->makeNotification($item, 'Barang', 'master.item-pembelian.index'));

        $perbekalan = DB::table('master_perbekalan as item')
            ->leftJoin('perbekalan_stock as stock', 'stock.id_barang', '=', 'item.id_barang')
            ->where('item.limit_minimal', '>', 0)
            ->select(
                'item.id_barang as item_id',
                'item.nama_barang as item_name',
                'item.satuan',
                'item.limit_minimal',
                DB::raw('COALESCE(stock.stok_aktual, 0) as current_stock')
            )
            ->get()
            ->map(fn ($item) => $this->makeNotification($item, 'Perbekalan', 'master.perbekalan.index'));

        return $barang
            ->concat($perbekalan)
            ->filter()
            ->values();
    }

    private function makeNotification(object $item, string $category, string $routeName): ?object
    {
        $stock = (float) $item->current_stock;
        $limit = (float) $item->limit_minimal;

        if ($stock <= $limit) {
            $severity = 'danger';
            $title = 'Stok di bawah limit';
            $message = $item->item_name.' telah mencapai atau kurang dari limit minimal.';
            $severityOrder = 1;
        } elseif ($stock <= $limit * (1 + self::WARNING_BUFFER)) {
            $severity = 'warning';
            $title = 'Barang mendekati limit';
            $message = $item->item_name.' berada dalam batas 20% di atas limit minimal.';
            $severityOrder = 2;
        } else {
            return null;
        }

        return (object) [
            'type' => 'stock',
            'severity' => $severity,
            'severity_order' => $severityOrder,
            'title' => $title,
            'message' => $message,
            'category' => $category,
            'item_id' => (int) $item->item_id,
            'item_name' => (string) $item->item_name,
            'satuan' => (string) $item->satuan,
            'current_stock' => $stock,
            'limit_minimal' => $limit,
            'stock_ratio' => $limit > 0 ? $stock / $limit : 0,
            'route_name' => $routeName,
            'related_menu' => $category === 'Barang' ? 'Master Item Pembelian' : 'Master Perbekalan',
            'action_label' => 'Buka Master',
        ];
    }
}
