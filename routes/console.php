<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pelayaran:backfill-trip-lots {--pelayaran= : ID pelayaran yang ingin dibackfill} {--dry-run : Simulasi tanpa menulis data}', function () {
    $requiredTables = [
        'pelayaran',
        'ikan_hasil_pelayaran',
        'storage_ikan',
        'stok_ikan_storage',
        'stok_ikan_lots',
    ];

    foreach ($requiredTables as $table) {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            $this->error("Tabel '{$table}' belum tersedia. Jalankan migration terbaru terlebih dahulu.");

            return 1;
        }
    }

    $idPelayaranOption = $this->option('pelayaran');
    $dryRun = (bool) $this->option('dry-run');

    $query = DB::table('pelayaran')
        ->where('status_pelayaran', 'selesai')
        ->orderBy('id_pelayaran');

    if ($idPelayaranOption !== null && $idPelayaranOption !== '') {
        $query->where('id_pelayaran', (int) $idPelayaranOption);
    }

    $voyages = $query->get([
        'id_pelayaran',
        'id_kapal',
        'tanggal_berangkat',
        'tanggal_tiba',
        'tanggal_selesai',
    ]);

    if ($voyages->isEmpty()) {
        $this->warn('Tidak ada pelayaran selesai yang cocok dengan filter.');

        return 0;
    }

    $insertedLots = 0;
    $convertedLegacyWeight = 0.0;
    $snapshotWeight = 0.0;

    foreach ($voyages as $voyage) {
        DB::transaction(function () use (
            $voyage,
            $dryRun,
            &$insertedLots,
            &$convertedLegacyWeight,
            &$snapshotWeight
        ) {
            $idStorage = DB::table('storage_ikan')
                ->where('id_kapal', (int) $voyage->id_kapal)
                ->value('id_storage');

            if (! $idStorage) {
                $this->warn("Trip #{$voyage->id_pelayaran}: storage kapal belum ada, dilewati.");

                return;
            }

            $catchRows = DB::table('ikan_hasil_pelayaran')
                ->where('id_pelayaran', (int) $voyage->id_pelayaran)
                ->groupBy('id_ikan')
                ->selectRaw('id_ikan, SUM(berat_hasil) as total_berat, CASE WHEN SUM(berat_hasil) = 0 THEN 0 ELSE SUM(berat_hasil * COALESCE(harga_per_kg, 0)) / SUM(berat_hasil) END as avg_harga')
                ->get();

            if ($catchRows->isEmpty()) {
                $this->line("Trip #{$voyage->id_pelayaran}: tidak ada tangkapan, dilewati.");

                return;
            }

            $tanggalLot = $voyage->tanggal_selesai
                ?: ($voyage->tanggal_tiba ?: ($voyage->tanggal_berangkat ?: now()->toDateString()));

            foreach ($catchRows as $catchRow) {
                $idIkan = (int) $catchRow->id_ikan;
                $beratTangkapan = (float) $catchRow->total_berat;

                if ($beratTangkapan <= 0) {
                    continue;
                }

                $existingTripLots = (float) DB::table('stok_ikan_lots')
                    ->where('id_pelayaran', (int) $voyage->id_pelayaran)
                    ->where('id_storage', (int) $idStorage)
                    ->where('id_ikan', $idIkan)
                    ->sum('berat_awal');

                if ($existingTripLots > 0) {
                    continue;
                }

                $stokAktual = (float) (DB::table('stok_ikan_storage')
                    ->where('id_storage', (int) $idStorage)
                    ->where('id_ikan', $idIkan)
                    ->value('stok_aktual') ?? 0);

                if ($stokAktual <= 0) {
                    $this->line("Trip #{$voyage->id_pelayaran}, ikan #{$idIkan}: stok saat ini 0, tidak bisa backfill exact dari snapshot.");

                    continue;
                }

                $legacyRows = DB::table('stok_ikan_lots')
                    ->where('id_storage', (int) $idStorage)
                    ->where('id_ikan', $idIkan)
                    ->where('source_type', 'legacy_opening')
                    ->where('berat_sisa', '>', 0)
                    ->orderBy('id_stok_ikan_lot')
                    ->lockForUpdate()
                    ->get(['id_stok_ikan_lot', 'berat_sisa']);

                $legacyAvailable = (float) $legacyRows->sum('berat_sisa');
                $targetWeight = min($beratTangkapan, $stokAktual);
                $backfillWeight = 0.0;

                if ($legacyAvailable > 0) {
                    $toConvert = min($legacyAvailable, $targetWeight);
                    $remainingConvert = $toConvert;

                    foreach ($legacyRows as $legacyRow) {
                        if ($remainingConvert <= 0) {
                            break;
                        }

                        $legacySisa = (float) $legacyRow->berat_sisa;
                        $consumed = min($remainingConvert, $legacySisa);
                        $remainingConvert -= $consumed;

                        if (! $dryRun) {
                            DB::table('stok_ikan_lots')
                                ->where('id_stok_ikan_lot', (int) $legacyRow->id_stok_ikan_lot)
                                ->update([
                                    'berat_sisa' => max(0, $legacySisa - $consumed),
                                    'updated_at' => now(),
                                ]);
                        }
                    }

                    $backfillWeight = $toConvert;
                    $convertedLegacyWeight += $toConvert;
                } elseif ($targetWeight > 0) {
                    $backfillWeight = $targetWeight;
                    $snapshotWeight += $targetWeight;
                }

                if ($backfillWeight <= 0) {
                    continue;
                }

                if (! $dryRun) {
                    DB::table('stok_ikan_lots')->insert([
                        'id_storage' => (int) $idStorage,
                        'id_ikan' => $idIkan,
                        'id_pelayaran' => (int) $voyage->id_pelayaran,
                        'source_type' => 'backfill_trip',
                        'tanggal_lot' => $tanggalLot,
                        'berat_awal' => $backfillWeight,
                        'berat_sisa' => $backfillWeight,
                        'harga_per_kg' => (float) ($catchRow->avg_harga ?? 0),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $insertedLots++;
                $this->line(sprintf(
                    'Trip #%d ikan #%d: lot backfill %.2f kg (%s).',
                    (int) $voyage->id_pelayaran,
                    $idIkan,
                    $backfillWeight,
                    $dryRun ? 'dry-run' : 'saved'
                ));
            }
        });
    }

    $this->newLine();
    $this->info(($dryRun ? '[DRY RUN] ' : '').'Backfill selesai.');
    $this->line('Lot dibuat: '.$insertedLots);
    $this->line('Berat konversi dari legacy_opening: '.number_format($convertedLegacyWeight, 2).' kg');
    $this->line('Berat snapshot langsung dari stok saat ini: '.number_format($snapshotWeight, 2).' kg');

    return 0;
})->purpose('Backfill one-time trip lots from legacy stock for exact voyage sales attribution');
