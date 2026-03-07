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
        Schema::create('laporan_selisih_bongkaran', function (Blueprint $table) {
            $table->increments('id_laporan');
            $table->unsignedInteger('id_pelayaran');
            $table->decimal('total_berat_timbangan', 15, 2);
            $table->decimal('total_berat_catatan', 15, 2);
            $table->decimal('total_selisih', 15, 2);
            $table->date('tanggal_laporan');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('id_pelayaran')->references('id_pelayaran')->on('pelayaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_selisih_bongkaran');
    }
};
