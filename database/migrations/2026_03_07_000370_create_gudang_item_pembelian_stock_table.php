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
        Schema::create('gudang_item_pembelian_stock', function (Blueprint $table) {
            $table->increments('id_stok_gudang_item');
            $table->unsignedInteger('id_gudang');
            $table->unsignedInteger('id_item_pembelian');
            $table->decimal('stok_aktual', 15, 2)->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('id_gudang')->references('id_gudang')->on('master_gudang');
            $table->foreign('id_item_pembelian')->references('id_item_pembelian')->on('master_item_pembelian');
            $table->unique(['id_gudang', 'id_item_pembelian'], 'uniq_gudang_item_pembelian_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudang_item_pembelian_stock');
    }
};
