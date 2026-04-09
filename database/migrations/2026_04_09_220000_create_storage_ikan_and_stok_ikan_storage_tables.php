<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('storage_ikan', function (Blueprint $table) {
            $table->increments('id_storage');
            $table->unsignedInteger('id_kapal')->unique();
            $table->string('nama_storage');
            $table->timestamps();

            $table->foreign('id_kapal')
                ->references('id_kapal')
                ->on('kapal')
                ->onDelete('cascade');
        });

        Schema::create('stok_ikan_storage', function (Blueprint $table) {
            $table->increments('id_stok_storage');
            $table->unsignedInteger('id_storage');
            $table->unsignedInteger('id_ikan');
            $table->decimal('stok_aktual', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_storage')
                ->references('id_storage')
                ->on('storage_ikan')
                ->onDelete('cascade');

            $table->foreign('id_ikan')
                ->references('id_ikan')
                ->on('master_ikan')
                ->onDelete('cascade');

            $table->unique(['id_storage', 'id_ikan'], 'stok_ikan_storage_storage_ikan_unique');
        });

        $now = now();
        $storageIdsByKapal = [];

        foreach (DB::table('kapal')->orderBy('id_kapal')->get(['id_kapal', 'nama_kapal']) as $kapal) {
            $storageIdsByKapal[(int) $kapal->id_kapal] = (int) DB::table('storage_ikan')->insertGetId([
                'id_kapal' => (int) $kapal->id_kapal,
                'nama_storage' => 'Storage ' . $kapal->nama_kapal,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $catchRows = DB::table('ikan_hasil_pelayaran as ihp')
            ->join('pelayaran as p', 'p.id_pelayaran', '=', 'ihp.id_pelayaran')
            ->where('p.status_pelayaran', 'selesai')
            ->select('p.id_kapal', 'ihp.id_ikan', DB::raw('SUM(ihp.berat_hasil) as total_berat'))
            ->groupBy('p.id_kapal', 'ihp.id_ikan')
            ->get();

        $stockRows = [];
        foreach ($catchRows as $row) {
            $idKapal = (int) $row->id_kapal;
            $idStorage = $storageIdsByKapal[$idKapal] ?? null;

            if ($idStorage === null) {
                continue;
            }

            $stockRows[] = [
                'id_storage' => $idStorage,
                'id_ikan' => (int) $row->id_ikan,
                'stok_aktual' => (float) $row->total_berat,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($stockRows !== []) {
            DB::table('stok_ikan_storage')->insert($stockRows);
        }

        $ikanTangkapanMap = DB::table('master_ikan')
            ->pluck('id_ikan_tangkapan', 'id_ikan')
            ->map(fn ($value) => $value === null ? null : (int) $value)
            ->toArray();

        $salesRows = DB::table('penjualan_items')
            ->select('id_ikan', DB::raw('SUM(berat) as total_berat'))
            ->groupBy('id_ikan')
            ->get();

        foreach ($salesRows as $saleRow) {
            $remaining = (float) $saleRow->total_berat;
            if ($remaining <= 0) {
                continue;
            }

            $idIkan = (int) $saleRow->id_ikan;
            $idIkanTangkapan = $ikanTangkapanMap[$idIkan] ?? null;

            $stockQuery = DB::table('stok_ikan_storage as sis')
                ->where('sis.stok_aktual', '>', 0);

            if ($idIkanTangkapan !== null) {
                $stockQuery
                    ->join('master_ikan as mi', 'mi.id_ikan', '=', 'sis.id_ikan')
                    ->where('mi.id_ikan_tangkapan', $idIkanTangkapan);
            } else {
                $stockQuery->where('sis.id_ikan', $idIkan);
            }

            $stockItems = $stockQuery
                ->orderBy('sis.id_stok_storage')
                ->get(['sis.id_stok_storage', 'sis.stok_aktual']);

            foreach ($stockItems as $stockItem) {
                if ($remaining <= 0) {
                    break;
                }

                $stokSaatIni = (float) $stockItem->stok_aktual;
                $terpakai = min($remaining, $stokSaatIni);
                $remaining -= $terpakai;

                DB::table('stok_ikan_storage')
                    ->where('id_stok_storage', (int) $stockItem->id_stok_storage)
                    ->update([
                        'stok_aktual' => max(0, $stokSaatIni - $terpakai),
                        'updated_at' => $now,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_ikan_storage');
        Schema::dropIfExists('storage_ikan');
    }
};
