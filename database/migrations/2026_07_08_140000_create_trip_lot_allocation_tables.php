<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// php artisan pelayaran:backfill-trip-lots --pelayaran=1
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stok_ikan_lots', function (Blueprint $table) {
            $table->increments('id_stok_ikan_lot');
            $table->unsignedInteger('id_storage');
            $table->unsignedInteger('id_ikan');
            $table->unsignedInteger('id_pelayaran')->nullable();
            $table->string('source_type', 30)->default('trip');
            $table->date('tanggal_lot');
            $table->decimal('berat_awal', 15, 2);
            $table->decimal('berat_sisa', 15, 2);
            $table->decimal('harga_per_kg', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_storage', 'fk_stok_ikan_lot_storage')
                ->references('id_storage')
                ->on('storage_ikan')
                ->onDelete('cascade');

            $table->foreign('id_ikan', 'fk_stok_ikan_lot_ikan')
                ->references('id_ikan')
                ->on('master_ikan')
                ->onDelete('cascade');

            $table->foreign('id_pelayaran', 'fk_stok_ikan_lot_pelayaran')
                ->references('id_pelayaran')
                ->on('pelayaran')
                ->nullOnDelete();

            $table->index(['id_storage', 'id_ikan', 'tanggal_lot'], 'idx_stok_ikan_lot_fifo');
        });

        Schema::create('penjualan_item_lot_allocations', function (Blueprint $table) {
            $table->increments('id_penjualan_item_lot_allocation');
            $table->unsignedInteger('id_item');
            $table->unsignedInteger('id_stok_ikan_lot');
            $table->unsignedInteger('id_storage');
            $table->unsignedInteger('id_ikan');
            $table->unsignedInteger('id_pelayaran')->nullable();
            $table->decimal('berat_alokasi', 15, 2);
            $table->decimal('harga_per_kg_lot', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_item', 'fk_penjualan_lot_alloc_item')
                ->references('id_item')
                ->on('penjualan_items')
                ->onDelete('cascade');

            $table->foreign('id_stok_ikan_lot', 'fk_penjualan_lot_alloc_lot')
                ->references('id_stok_ikan_lot')
                ->on('stok_ikan_lots')
                ->onDelete('cascade');

            $table->foreign('id_storage', 'fk_penjualan_lot_alloc_storage')
                ->references('id_storage')
                ->on('storage_ikan')
                ->onDelete('cascade');

            $table->foreign('id_ikan', 'fk_penjualan_lot_alloc_ikan')
                ->references('id_ikan')
                ->on('master_ikan')
                ->onDelete('cascade');

            $table->foreign('id_pelayaran', 'fk_penjualan_lot_alloc_pelayaran')
                ->references('id_pelayaran')
                ->on('pelayaran')
                ->nullOnDelete();

            $table->index(['id_pelayaran', 'id_ikan'], 'idx_penjualan_lot_alloc_pelayaran_ikan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_item_lot_allocations');
        Schema::dropIfExists('stok_ikan_lots');
    }
};
