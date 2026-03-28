<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gudang_item_pembelian_stock') && !Schema::hasTable('item_pembelian_stock')) {
            Schema::rename('gudang_item_pembelian_stock', 'item_pembelian_stock');
        }

        Schema::table('item_pembelian_stock', function (Blueprint $table) {
            if (Schema::hasColumn('item_pembelian_stock', 'id_stok_gudang_item') && !Schema::hasColumn('item_pembelian_stock', 'id_stok_item_pembelian')) {
                $table->renameColumn('id_stok_gudang_item', 'id_stok_item_pembelian');
            }
        });
    }

    public function down(): void
    {
        Schema::table('item_pembelian_stock', function (Blueprint $table) {
            if (Schema::hasColumn('item_pembelian_stock', 'id_stok_item_pembelian') && !Schema::hasColumn('item_pembelian_stock', 'id_stok_gudang_item')) {
                $table->renameColumn('id_stok_item_pembelian', 'id_stok_gudang_item');
            }
        });

        if (Schema::hasTable('item_pembelian_stock') && !Schema::hasTable('gudang_item_pembelian_stock')) {
            Schema::rename('item_pembelian_stock', 'gudang_item_pembelian_stock');
        }
    }
};
