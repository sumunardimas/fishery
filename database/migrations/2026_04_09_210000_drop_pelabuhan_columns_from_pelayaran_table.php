<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dropPelabuhanAsal = Schema::hasColumn('pelayaran', 'pelabuhan_asal');
        $dropPelabuhanTujuan = Schema::hasColumn('pelayaran', 'pelabuhan_tujuan');

        if (! $dropPelabuhanAsal && ! $dropPelabuhanTujuan) {
            return;
        }

        Schema::table('pelayaran', function (Blueprint $table) use ($dropPelabuhanAsal, $dropPelabuhanTujuan) {
            $columns = array_values(array_filter([
                $dropPelabuhanAsal ? 'pelabuhan_asal' : null,
                $dropPelabuhanTujuan ? 'pelabuhan_tujuan' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasPelabuhanAsal = Schema::hasColumn('pelayaran', 'pelabuhan_asal');
        $hasPelabuhanTujuan = Schema::hasColumn('pelayaran', 'pelabuhan_tujuan');

        if ($hasPelabuhanAsal && $hasPelabuhanTujuan) {
            return;
        }

        Schema::table('pelayaran', function (Blueprint $table) use ($hasPelabuhanAsal, $hasPelabuhanTujuan) {
            if (! $hasPelabuhanAsal) {
                $table->string('pelabuhan_asal')->nullable()->after('tanggal_tiba');
            }

            if (! $hasPelabuhanTujuan) {
                $table->string('pelabuhan_tujuan')->nullable()->after('pelabuhan_asal');
            }
        });
    }
};
