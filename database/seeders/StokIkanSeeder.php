<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StokIkanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $periode = now()->format('Y-m');

        // Use existing pelayaran records (created by FisherySeeder).
        // Falls back gracefully if ids differ.
        $pelayaran = DB::table('pelayaran')
            ->orderBy('id_pelayaran')
            ->pluck('id_pelayaran')
            ->toArray();

        if (empty($pelayaran)) {
            $this->command->warn('No pelayaran found. Run FisherySeeder first.');
            return;
        }

        $idPelA = $pelayaran[0];
        $idPelB = $pelayaran[1] ?? $pelayaran[0];

        // Representative catch weights per fish id (id_ikan => berat kg).
        // Pelayaran A — mostly tuna & cakalang
        $catchA = [
            1  => 850.00,   // BABY TUNA 1 MINI
            5  => 620.00,   // BABY TUNA 1 DOWN
            9  => 740.00,   // BABY TUNA 1 UP
            13 => 1200.00,  // BABY TUNA 5 UP
            17 => 980.00,   // TUNA 10 UP
            21 => 560.00,   // TUNA 20 UP
            25 => 1500.00,  // CAKALANG MINI
            29 => 1100.00,  // CAKALANG 1 DOWN
            33 => 870.00,   // CAKALANG 1 UP
            65 => 320.00,   // TENGGIRI
            88 => 200.00,   // MARLIN
            86 => 150.00,   // KAKAP
        ];

        // Pelayaran B — layang, tongkol, cumi, lemadang
        $catchB = [
            37 => 2200.00,  // LAYANG KECIL
            41 => 1800.00,  // LAYANG
            45 => 1400.00,  // LAYANG BESAR
            49 => 900.00,   // TONGKOL KECIL
            53 => 1300.00,  // TONGKOL
            69 => 450.00,   // CUMI KECIL
            72 => 380.00,   // CUMI BESAR
            75 => 600.00,   // LEMADANG 4 UP
            78 => 500.00,   // LEMADANG 2 UP
            84 => 180.00,   // BARAKUDA
            57 => 700.00,   // LAURA
            61 => 540.00,   // LAURA BESAR
        ];

        // Only insert for ikan that actually exist in master_ikan.
        $existingIds = DB::table('master_ikan')
            ->pluck('id_ikan')
            ->flip()
            ->toArray();

        $this->insertCatch($idPelA, $catchA, $existingIds, $now);
        $this->insertCatch($idPelB, $catchB, $existingIds, $now);

        // Recalculate stok_ikan for the current period.
        $allAffected = array_keys($catchA + $catchB); // + preserves int keys (no reindex)
        $this->recalculateStok($allAffected, $periode, $now);

        $total = count($catchA) + count($catchB);
        $this->command->info("StokIkan seeded: {$total} ikan_hasil_pelayaran rows, stok_ikan updated.");
    }

    private function insertCatch(int $idPelayaran, array $catch, array $existingIds, $now): void
    {
        foreach ($catch as $idIkan => $berat) {
            if (! array_key_exists($idIkan, $existingIds)) {
                continue;
            }

            DB::table('ikan_hasil_pelayaran')->updateOrInsert(
                ['id_pelayaran' => $idPelayaran, 'id_ikan' => $idIkan],
                ['berat_hasil' => $berat, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    private function recalculateStok(array $ids, string $periode, $now): void
    {
        // Total catch all-time per ikan (matches getAvailableStockByIkan logic)
        $catchByIkan = DB::table('ikan_hasil_pelayaran')
            ->whereIn('id_ikan', $ids)
            ->groupBy('id_ikan')
            ->selectRaw('id_ikan, SUM(berat_hasil) as total')
            ->pluck('total', 'id_ikan');

        $salesByIkan = DB::table('penjualan')
            ->whereIn('id_ikan', $ids)
            ->groupBy('id_ikan')
            ->selectRaw('id_ikan, SUM(berat) as total')
            ->pluck('total', 'id_ikan');

        $rows = [];
        foreach ($ids as $idIkan) {
            $totalTangkapan = (float) ($catchByIkan[$idIkan] ?? 0);
            $totalPenjualan = (float) ($salesByIkan[$idIkan] ?? 0);
            $rows[] = [
                'id_ikan'          => $idIkan,
                'periode'          => $periode,
                'total_tangkapan'  => $totalTangkapan,
                'total_penjualan'  => $totalPenjualan,
                'stok_akhir'       => $totalTangkapan - $totalPenjualan,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        DB::table('stok_ikan')->upsert(
            $rows,
            ['id_ikan', 'periode'],
            ['total_tangkapan', 'total_penjualan', 'stok_akhir', 'updated_at']
        );
    }
}
