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
        Schema::create('sisa_trip', function (Blueprint $table) {
            $table->increments('id_sisa_trip');
            $table->unsignedInteger('id_pelayaran');
            $table->unsignedInteger('id_barang');
            $table->decimal('jumlah_sisa', 15, 2);
            $table->string('satuan');
            $table->text('keterangan');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('id_pelayaran')->references('id_pelayaran')->on('pelayaran');
            $table->foreign('id_barang')->references('id_barang')->on('master_perbekalan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sisa_trip');
    }
};
