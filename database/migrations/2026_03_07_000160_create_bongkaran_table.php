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
        Schema::create('bongkaran', function (Blueprint $table) {
            $table->increments('id_bongkaran');
            $table->unsignedInteger('id_pelayaran');
            $table->unsignedInteger('id_ikan');
            $table->decimal('berat_timbangan', 15, 2);
            $table->decimal('berat_tercatat', 15, 2);
            $table->decimal('selisih_berat', 15, 2);
            $table->decimal('harga_per_kg', 15, 2);
            $table->decimal('total_nilai', 15, 2);
            $table->date('tanggal_bongkar');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('id_pelayaran')->references('id_pelayaran')->on('pelayaran');
            $table->foreign('id_ikan')->references('id_ikan')->on('master_ikan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bongkaran');
    }
};
