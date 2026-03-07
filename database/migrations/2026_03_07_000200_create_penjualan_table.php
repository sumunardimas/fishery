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
        Schema::create('penjualan', function (Blueprint $table) {
            $table->increments('id_penjualan');
            $table->date('tanggal_penjualan');
            $table->unsignedInteger('id_ikan');
            $table->decimal('berat', 15, 2);
            $table->decimal('harga_per_kg', 15, 2);
            $table->decimal('total_harga', 15, 2);
            $table->string('pembeli');
            $table->text('keterangan');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('id_ikan')->references('id_ikan')->on('master_ikan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan');
    }
};
