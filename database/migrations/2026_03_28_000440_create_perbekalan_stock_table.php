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
        Schema::create('perbekalan_stock', function (Blueprint $table) {
            $table->increments('id_stok_perbekalan');
            $table->unsignedInteger('id_barang');
            $table->decimal('stok_aktual', 15, 2)->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('id_barang')->references('id_barang')->on('master_perbekalan');
            $table->unique('id_barang', 'uniq_perbekalan_stock_barang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perbekalan_stock');
    }
};
