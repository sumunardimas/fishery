<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryFifoService
{
    /**
     * @return array{total_cost: float, average_price: float, layers: array<int, array<string, float|int|string>>}
     */
    public function valueOutgoing(string $itemType, int $itemId, float $quantity, string $transactionDate): array
    {
        [$table, $idColumn, $masterTable, $masterIdColumn, $nameColumn] = match ($itemType) {
            'pembelian' => ['pembelian_transaction', 'id_item_pembelian', 'master_item_pembelian', 'id_item_pembelian', 'nama_item'],
            'perbekalan' => ['perbekalan_transaction', 'id_barang', 'master_perbekalan', 'id_barang', 'nama_barang'],
            default => throw ValidationException::withMessages(['items' => 'Jenis item tidak valid.']),
        };

        $itemName = (string) DB::table($masterTable)
            ->where($masterIdColumn, $itemId)
            ->value($nameColumn);

        $layers = [];
        $events = DB::table($table)
            ->where($idColumn, $itemId)
            ->whereDate('tanggal_transaksi', '<=', $transactionDate)
            ->orderBy('tanggal_transaksi')
            ->orderBy('id_transaction')
            ->lockForUpdate()
            ->get(['id_transaction', 'tanggal_transaksi', 'jenis_transaksi', 'jumlah', 'harga_satuan']);

        foreach ($events as $event) {
            if ($event->jenis_transaksi === 'in') {
                $layers[] = [
                    'remaining_qty' => (float) $event->jumlah,
                    'harga_satuan' => (float) ($event->harga_satuan ?? 0),
                    'tanggal_transaksi' => (string) $event->tanggal_transaksi,
                    'reference_id' => (int) $event->id_transaction,
                ];

                continue;
            }

            $this->consume($layers, (float) $event->jumlah);
        }

        $valuation = $this->consume($layers, $quantity);

        if ($valuation['shortage_qty'] > 0.00001) {
            throw ValidationException::withMessages([
                'items' => 'Lapisan FIFO '.$itemName.' tidak mencukupi pada tanggal transaksi. Kekurangan: '
                    .number_format($valuation['shortage_qty'], 2, ',', '.').'.',
            ]);
        }

        return [
            'total_cost' => round($valuation['total_cost'], 2),
            // Keep full precision here so the transaction's quantity × average price
            // resolves to the exact FIFO layer cost before database rounding.
            'average_price' => $quantity > 0 ? $valuation['total_cost'] / $quantity : 0,
            'layers' => $valuation['layers'],
        ];
    }

    /**
     * @param  array<int, array<string, float|int|string>>  $layers
     * @return array{total_cost: float, shortage_qty: float, layers: array<int, array<string, float|int|string>>}
     */
    private function consume(array &$layers, float $quantity): array
    {
        $remaining = $quantity;
        $totalCost = 0.0;
        $consumedLayers = [];

        foreach ($layers as &$layer) {
            if ($remaining <= 0.00001) {
                break;
            }

            $available = (float) $layer['remaining_qty'];
            if ($available <= 0.00001) {
                continue;
            }

            $taken = min($available, $remaining);
            $unitPrice = (float) $layer['harga_satuan'];
            $layer['remaining_qty'] = $available - $taken;
            $remaining -= $taken;
            $totalCost += $taken * $unitPrice;
            $consumedLayers[] = [
                'quantity' => $taken,
                'harga_satuan' => $unitPrice,
                'tanggal_transaksi' => (string) $layer['tanggal_transaksi'],
                'reference_id' => (int) $layer['reference_id'],
            ];
        }
        unset($layer);

        return [
            'total_cost' => $totalCost,
            'shortage_qty' => max(0, $remaining),
            'layers' => $consumedLayers,
        ];
    }
}
