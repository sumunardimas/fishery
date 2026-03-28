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
        Schema::table('ikan_hasil_pelayaran', function (Blueprint $table) {
            // MariaDB can bind FK support to the existing unique index.
            // Add a plain index first so dropping the old unique index is allowed.
            $table->index('id_pelayaran', 'ikan_hasil_pelayaran_id_pelayaran_idx');

            $table->string('kategori_tangkapan', 30)->default('pancingan_pribadi')->after('id_ikan');
            $table->decimal('harga_per_kg', 15, 2)->default(0)->after('berat_hasil');

            $table->dropUnique('ikan_hasil_pelayaran_id_pelayaran_id_ikan_unique');
            $table->unique(
                ['id_pelayaran', 'id_ikan', 'kategori_tangkapan'],
                'ikan_hasil_pelayaran_trip_ikan_kategori_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ikan_hasil_pelayaran', function (Blueprint $table) {
            $table->dropUnique('ikan_hasil_pelayaran_trip_ikan_kategori_unique');
            $table->unique(['id_pelayaran', 'id_ikan']);
            $table->dropIndex('ikan_hasil_pelayaran_id_pelayaran_idx');

            $table->dropColumn(['kategori_tangkapan', 'harga_per_kg']);
        });
    }
};
