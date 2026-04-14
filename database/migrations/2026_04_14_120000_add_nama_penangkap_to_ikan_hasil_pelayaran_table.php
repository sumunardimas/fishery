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
            $table->dropUnique('ikan_hasil_pelayaran_trip_ikan_kategori_unique');
            $table->string('nama_penangkap', 120)->default('')->after('kategori_tangkapan');
            $table->unique(
                ['id_pelayaran', 'id_ikan', 'kategori_tangkapan', 'nama_penangkap'],
                'ikan_hasil_pelayaran_trip_ikan_kategori_penangkap_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ikan_hasil_pelayaran', function (Blueprint $table) {
            $table->dropUnique('ikan_hasil_pelayaran_trip_ikan_kategori_penangkap_unique');
            $table->dropColumn('nama_penangkap');
            $table->unique(
                ['id_pelayaran', 'id_ikan', 'kategori_tangkapan'],
                'ikan_hasil_pelayaran_trip_ikan_kategori_unique'
            );
        });
    }
};
