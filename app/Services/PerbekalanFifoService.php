<?php

namespace App\Services;

use App\Models\Pelayaran;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerbekalanFifoService
{
    public function buildTripUsageSummary(Pelayaran $pelayaran): Collection
    {
        $idPelayaran = (int) $pelayaran->id_pelayaran;
        $cutoffDate = $this->resolveTripCutoffDate($pelayaran);

        $plannedRows = DB::table('perbekalan_pelayaran as pp')
            ->join('master_perbekalan as mp', 'mp.id_barang', '=', 'pp.id_barang')
            ->leftJoin('sisa_trip as st', function ($join) use ($idPelayaran) {
                $join->on('st.id_barang', '=', 'pp.id_barang')
                    ->where('st.id_pelayaran', '=', $idPelayaran);
            })
            ->where('pp.id_pelayaran', $idPelayaran)
            ->orderBy('mp.nama_barang')
            ->select(
                'pp.id_barang',
                'pp.jumlah as jumlah_awal',
                'mp.nama_barang',
                'mp.satuan',
                'st.jumlah_sisa'
            )
            ->get();

        if ($plannedRows->isEmpty()) {
            return collect();
        }

        $usageRows = $plannedRows->map(function ($row) {
            $jumlahAwal = (float) ($row->jumlah_awal ?? 0);
            $hasSisa = $row->jumlah_sisa !== null;
            $jumlahSisa = $hasSisa ? min((float) $row->jumlah_sisa, $jumlahAwal) : null;
            $jumlahTerpakai = $hasSisa ? max(0, $jumlahAwal - (float) $jumlahSisa) : 0;

            return [
                'id_barang' => (int) $row->id_barang,
                'nama_barang' => (string) $row->nama_barang,
                'satuan' => (string) $row->satuan,
                'jumlah_awal' => $jumlahAwal,
                'jumlah_sisa' => $jumlahSisa,
                'jumlah_terpakai' => $jumlahTerpakai,
                'has_sisa' => $hasSisa,
            ];
        })->values();

        $valuations = $this->simulateTripConsumption(
            pelayaran: $pelayaran,
            cutoffDate: $cutoffDate,
            usageRows: $usageRows
        );

        return $usageRows->map(function (array $row) use ($valuations) {
            $valuation = $valuations->get($row['id_barang'], [
                'consumed_qty' => 0.0,
                'shortage_qty' => 0.0,
                'total_cost' => 0.0,
                'average_price' => 0.0,
                'layers' => [],
            ]);

            return (object) [
                'id_barang' => $row['id_barang'],
                'nama_barang' => $row['nama_barang'],
                'satuan' => $row['satuan'],
                'jumlah_awal' => $row['jumlah_awal'],
                'jumlah_sisa' => $row['jumlah_sisa'],
                'jumlah_terpakai' => $row['jumlah_terpakai'],
                'harga_beli' => (float) $valuation['average_price'],
                'total_biaya' => (float) $valuation['total_cost'],
                'fifo_layers' => $valuation['layers'],
                'fifo_shortage_qty' => (float) $valuation['shortage_qty'],
                'has_sisa' => $row['has_sisa'],
            ];
        });
    }

    public function recordTripConsumption(Pelayaran $pelayaran, Collection $usageSummary, ?string $transactionDate = null): void
    {
        $consumedRows = $usageSummary
            ->filter(fn ($row) => (float) ($row->jumlah_terpakai ?? 0) > 0)
            ->values();

        if ($consumedRows->isEmpty()) {
            return;
        }

        $stockRows = DB::table('perbekalan_stock')
            ->whereIn('id_barang', $consumedRows->pluck('id_barang')->all())
            ->lockForUpdate()
            ->get()
            ->keyBy(fn ($row) => (int) $row->id_barang);

        $errors = [];
        foreach ($consumedRows as $row) {
            $idBarang = (int) $row->id_barang;
            $jumlahTerpakai = (float) $row->jumlah_terpakai;
            $stockSaatIni = (float) ($stockRows->get($idBarang)->stok_aktual ?? 0);

            if ($stockSaatIni + 0.00001 < $jumlahTerpakai) {
                $errors['message'] = 'Stok perbekalan '.$row->nama_barang.' tidak mencukupi untuk menutup trip. Stok tersedia: '
                    .number_format($stockSaatIni, 2, ',', '.')
                    .' '
                    .$row->satuan
                    .'.';
            }

            if ((float) ($row->fifo_shortage_qty ?? 0) > 0.00001) {
                $errors['message'] = 'Lapisan FIFO perbekalan '.$row->nama_barang.' tidak mencukupi. Periksa histori pembelian dan pemakaian stok sebelum menutup trip.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $tanggalTransaksi = $transactionDate ?: now()->toDateString();
        $now = now();

        foreach ($consumedRows as $row) {
            $idBarang = (int) $row->id_barang;
            $stockSaatIni = (float) ($stockRows->get($idBarang)->stok_aktual ?? 0);

            DB::table('perbekalan_stock')
                ->where('id_barang', $idBarang)
                ->update([
                    'stok_aktual' => $stockSaatIni - (float) $row->jumlah_terpakai,
                    'updated_at' => $now,
                ]);
        }

        DB::table('perbekalan_transaction')
            ->where('jenis_transaksi', 'out')
            ->where('id_pelayaran', (int) $pelayaran->id_pelayaran)
            ->delete();

        $transactionRows = $consumedRows->map(function ($row) use ($pelayaran, $tanggalTransaksi, $now) {
            $jumlahTerpakai = (float) $row->jumlah_terpakai;
            $totalBiaya = round((float) $row->total_biaya, 2);
            $hargaSatuan = $jumlahTerpakai > 0 ? round($totalBiaya / $jumlahTerpakai, 2) : 0;

            return [
                'tanggal_transaksi' => $tanggalTransaksi,
                'id_barang' => (int) $row->id_barang,
                'id_pelayaran' => (int) $pelayaran->id_pelayaran,
                'jenis_transaksi' => 'out',
                'akun_pembayaran' => null,
                'jumlah' => $jumlahTerpakai,
                'harga_satuan' => $hargaSatuan,
                'total_harga' => $totalBiaya,
                'sumber_tujuan' => 'Pelayaran #'.(int) $pelayaran->id_pelayaran,
                'keterangan' => 'Pemakaian FIFO penutupan trip '.($pelayaran->kapal->nama_kapal ?? ('#'.(int) $pelayaran->id_kapal)).' - '.(string) $row->nama_barang,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DB::table('perbekalan_transaction')->insert($transactionRows);
    }

    private function simulateTripConsumption(Pelayaran $pelayaran, string $cutoffDate, Collection $usageRows): Collection
    {
        $itemIds = $usageRows->pluck('id_barang')->map(fn ($id) => (int) $id)->all();
        $events = $this->buildLedgerEvents($itemIds, $pelayaran, $cutoffDate, $usageRows);
        $layersByItem = [];
        $valuations = [];

        foreach ($events as $event) {
            $idBarang = (int) $event['id_barang'];

            if (!isset($layersByItem[$idBarang])) {
                $layersByItem[$idBarang] = [];
            }

            if ($event['type'] === 'in') {
                $layersByItem[$idBarang][] = [
                    'remaining_qty' => (float) $event['qty'],
                    'harga_satuan' => (float) $event['unit_price'],
                    'tanggal_transaksi' => (string) $event['date'],
                    'reference_id' => (int) $event['sequence'],
                ];

                continue;
            }

            $consumption = $this->consumeLayers($layersByItem[$idBarang], (float) $event['qty']);

            if ($event['type'] === 'target_trip') {
                $valuations[$idBarang] = $consumption;
            }
        }

        return collect($valuations);
    }

    private function buildLedgerEvents(array $itemIds, Pelayaran $targetPelayaran, string $cutoffDate, Collection $usageRows): Collection
    {
        if ($itemIds === []) {
            return collect();
        }

        $targetPelayaranId = (int) $targetPelayaran->id_pelayaran;

        $inEvents = DB::table('perbekalan_transaction')
            ->where('jenis_transaksi', 'in')
            ->whereIn('id_barang', $itemIds)
            ->whereDate('tanggal_transaksi', '<=', $cutoffDate)
            ->orderBy('tanggal_transaksi')
            ->orderBy('id_transaction')
            ->get([
                'id_transaction',
                'id_barang',
                'tanggal_transaksi',
                'jumlah',
                'harga_satuan',
            ])
            ->map(function ($row) {
                return [
                    'type' => 'in',
                    'priority' => 1,
                    'date' => (string) $row->tanggal_transaksi,
                    'sequence' => (int) $row->id_transaction,
                    'id_barang' => (int) $row->id_barang,
                    'qty' => (float) $row->jumlah,
                    'unit_price' => (float) ($row->harga_satuan ?? 0),
                ];
            });

        $outTransactionEvents = DB::table('perbekalan_transaction')
            ->where('jenis_transaksi', 'out')
            ->whereIn('id_barang', $itemIds)
            ->whereDate('tanggal_transaksi', '<=', $cutoffDate)
            ->where(function ($query) use ($targetPelayaranId) {
                $query->whereNull('id_pelayaran')
                    ->orWhere('id_pelayaran', '!=', $targetPelayaranId);
            })
            ->orderBy('tanggal_transaksi')
            ->orderBy('id_transaction')
            ->get([
                'id_transaction',
                'id_barang',
                'tanggal_transaksi',
                'jumlah',
            ])
            ->map(function ($row) {
                return [
                    'type' => 'out',
                    'priority' => 2,
                    'date' => (string) $row->tanggal_transaksi,
                    'sequence' => (int) $row->id_transaction,
                    'id_barang' => (int) $row->id_barang,
                    'qty' => (float) $row->jumlah,
                ];
            });

        $realTripOutKeys = DB::table('perbekalan_transaction')
            ->where('jenis_transaksi', 'out')
            ->whereNotNull('id_pelayaran')
            ->whereIn('id_barang', $itemIds)
            ->whereDate('tanggal_transaksi', '<=', $cutoffDate)
            ->get(['id_pelayaran', 'id_barang'])
            ->mapWithKeys(function ($row) {
                return [((int) $row->id_pelayaran).'-'.((int) $row->id_barang) => true];
            });

        $virtualTripOutEvents = DB::table('pelayaran as p')
            ->join('perbekalan_pelayaran as pp', 'pp.id_pelayaran', '=', 'p.id_pelayaran')
            ->leftJoin('sisa_trip as st', function ($join) {
                $join->on('st.id_pelayaran', '=', 'pp.id_pelayaran')
                    ->on('st.id_barang', '=', 'pp.id_barang');
            })
            ->where('p.status_pelayaran', 'selesai')
            ->where('p.id_pelayaran', '!=', $targetPelayaranId)
            ->whereIn('pp.id_barang', $itemIds)
            ->whereRaw('DATE(COALESCE(p.tanggal_selesai, p.tanggal_tiba)) <= ?', [$cutoffDate])
            ->selectRaw('p.id_pelayaran, pp.id_barang, pp.jumlah as jumlah_awal, st.jumlah_sisa, DATE(COALESCE(p.tanggal_selesai, p.tanggal_tiba)) as tanggal_event')
            ->get()
            ->filter(function ($row) use ($realTripOutKeys) {
                return !$realTripOutKeys->has(((int) $row->id_pelayaran).'-'.((int) $row->id_barang));
            })
            ->map(function ($row) {
                $jumlahAwal = (float) ($row->jumlah_awal ?? 0);
                $jumlahSisa = $row->jumlah_sisa === null ? 0.0 : min((float) $row->jumlah_sisa, $jumlahAwal);
                $jumlahTerpakai = max(0, $jumlahAwal - $jumlahSisa);

                return [
                    'type' => 'out',
                    'priority' => 3,
                    'date' => (string) $row->tanggal_event,
                    'sequence' => (int) $row->id_pelayaran,
                    'id_barang' => (int) $row->id_barang,
                    'qty' => $jumlahTerpakai,
                ];
            })
            ->filter(fn (array $event) => (float) $event['qty'] > 0)
            ->values();

        $targetEvents = $usageRows
            ->filter(fn (array $row) => (float) $row['jumlah_terpakai'] > 0)
            ->map(function (array $row) use ($targetPelayaran, $cutoffDate) {
                return [
                    'type' => 'target_trip',
                    'priority' => 4,
                    'date' => $cutoffDate,
                    'sequence' => (int) $targetPelayaran->id_pelayaran,
                    'id_barang' => (int) $row['id_barang'],
                    'qty' => (float) $row['jumlah_terpakai'],
                ];
            })
            ->values();

        return $inEvents
            ->concat($outTransactionEvents)
            ->concat($virtualTripOutEvents)
            ->concat($targetEvents)
            ->sort(function (array $left, array $right) {
                if ($left['date'] !== $right['date']) {
                    return strcmp($left['date'], $right['date']);
                }

                if ($left['priority'] !== $right['priority']) {
                    return $left['priority'] <=> $right['priority'];
                }

                if ($left['sequence'] !== $right['sequence']) {
                    return $left['sequence'] <=> $right['sequence'];
                }

                return $left['id_barang'] <=> $right['id_barang'];
            })
            ->values();
    }

    private function consumeLayers(array &$layers, float $requiredQty): array
    {
        $remainingQty = max(0, $requiredQty);
        $consumedQty = 0.0;
        $totalCost = 0.0;
        $breakdown = [];

        while ($remainingQty > 0.00001 && $layers !== []) {
            $currentLayer = &$layers[0];
            $availableQty = (float) ($currentLayer['remaining_qty'] ?? 0);

            if ($availableQty <= 0.00001) {
                array_shift($layers);
                continue;
            }

            $takenQty = min($availableQty, $remainingQty);
            $hargaSatuan = (float) ($currentLayer['harga_satuan'] ?? 0);
            $layerCost = $takenQty * $hargaSatuan;

            $consumedQty += $takenQty;
            $totalCost += $layerCost;
            $breakdown[] = [
                'tanggal_transaksi' => (string) ($currentLayer['tanggal_transaksi'] ?? ''),
                'jumlah' => $takenQty,
                'harga_satuan' => $hargaSatuan,
                'total_harga' => $layerCost,
            ];

            $currentLayer['remaining_qty'] = $availableQty - $takenQty;
            $remainingQty -= $takenQty;

            if ((float) $currentLayer['remaining_qty'] <= 0.00001) {
                array_shift($layers);
            }
        }

        return [
            'consumed_qty' => round($consumedQty, 2),
            'shortage_qty' => round(max(0, $remainingQty), 2),
            'total_cost' => round($totalCost, 2),
            'average_price' => $consumedQty > 0 ? round($totalCost / $consumedQty, 2) : 0.0,
            'layers' => $breakdown,
        ];
    }

    private function resolveTripCutoffDate(Pelayaran $pelayaran): string
    {
        return $pelayaran->tanggal_selesai?->toDateString()
            ?: $pelayaran->tanggal_tiba?->toDateString()
            ?: now()->toDateString();
    }
}