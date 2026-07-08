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
        Schema::create('penjualan_item_storage_allocations', function (Blueprint $table) {
            $table->increments('id_alokasi_penjualan_item_storage');
            $table->unsignedInteger('id_item');
            $table->unsignedInteger('id_storage');
            $table->unsignedInteger('id_ikan');
            $table->decimal('berat_alokasi', 15, 2);
            $table->timestamps();

            $table->foreign('id_item', 'fk_penjualan_alloc_item')
                ->references('id_item')
                ->on('penjualan_items')
                ->onDelete('cascade');

            $table->foreign('id_storage', 'fk_penjualan_alloc_storage')
                ->references('id_storage')
                ->on('storage_ikan')
                ->onDelete('cascade');

            $table->foreign('id_ikan', 'fk_penjualan_alloc_ikan')
                ->references('id_ikan')
                ->on('master_ikan')
                ->onDelete('cascade');

            $table->index(['id_storage', 'id_ikan'], 'idx_penjualan_alloc_storage_ikan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_item_storage_allocations');
    }
};
