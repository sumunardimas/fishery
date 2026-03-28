<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $aggregatedStock = DB::table('gudang_item_pembelian_stock')
            ->select(
                'id_item_pembelian',
                DB::raw('SUM(stok_aktual) as stok_aktual'),
                DB::raw('MIN(created_at) as created_at'),
                DB::raw('MAX(updated_at) as updated_at')
            )
            ->groupBy('id_item_pembelian')
            ->get();

        Schema::table('gudang_item_pembelian_stock', function (Blueprint $table) {
            try {
                $table->dropForeign(['id_gudang']);
            } catch (\Throwable $e) {
            }

            try {
                $table->dropUnique('uniq_gudang_item_pembelian_stock');
            } catch (\Throwable $e) {
            }

            $table->dropColumn('id_gudang');
            $table->unique('id_item_pembelian', 'uniq_item_pembelian_stock');
        });

        DB::table('gudang_item_pembelian_stock')->delete();
        foreach ($aggregatedStock as $row) {
            DB::table('gudang_item_pembelian_stock')->insert([
                'id_item_pembelian' => $row->id_item_pembelian,
                'stok_aktual' => (float) $row->stok_aktual,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }

        Schema::table('pembelian_transaction', function (Blueprint $table) {
            try {
                $table->dropForeign(['id_gudang']);
            } catch (\Throwable $e) {
            }

            $table->dropColumn('id_gudang');
        });
    }

    public function down(): void
    {
        Schema::table('pembelian_transaction', function (Blueprint $table) {
            $table->unsignedInteger('id_gudang')->nullable()->after('id_item_pembelian');
            $table->foreign('id_gudang')->references('id_gudang')->on('master_gudang');
        });

        Schema::table('gudang_item_pembelian_stock', function (Blueprint $table) {
            try {
                $table->dropUnique('uniq_item_pembelian_stock');
            } catch (\Throwable $e) {
            }

            $table->unsignedInteger('id_gudang')->nullable()->after('id_stok_gudang_item');
            $table->foreign('id_gudang')->references('id_gudang')->on('master_gudang');
            $table->unique(['id_gudang', 'id_item_pembelian'], 'uniq_gudang_item_pembelian_stock');
        });
    }
};
