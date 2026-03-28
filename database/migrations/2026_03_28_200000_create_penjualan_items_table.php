<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create penjualan_items line-items table
        Schema::create('penjualan_items', function (Blueprint $table) {
            $table->increments('id_item');
            $table->unsignedInteger('id_penjualan');
            $table->unsignedInteger('id_ikan');
            $table->decimal('berat', 15, 2);
            $table->decimal('harga_per_kg', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->foreign('id_penjualan')
                ->references('id_penjualan')->on('penjualan')
                ->onDelete('cascade');

            $table->foreign('id_ikan')
                ->references('id_ikan')->on('master_ikan');
        });

        // 2. Migrate existing single-item penjualan rows → penjualan_items
        $existing = DB::table('penjualan')
            ->whereNotNull('id_ikan')
            ->whereNotNull('berat')
            ->get();

        foreach ($existing as $row) {
            DB::table('penjualan_items')->insert([
                'id_penjualan' => $row->id_penjualan,
                'id_ikan'      => $row->id_ikan,
                'berat'        => $row->berat,
                'harga_per_kg' => $row->harga_per_kg,
                'subtotal'     => $row->total_harga,
                'created_at'   => $row->created_at,
                'updated_at'   => $row->updated_at,
            ]);
        }

        // 3. Make per-item columns nullable on penjualan (new records store items in penjualan_items)
        Schema::table('penjualan', function (Blueprint $table) {
            $table->unsignedInteger('id_ikan')->nullable()->change();
            $table->decimal('berat', 15, 2)->nullable()->change();
            $table->decimal('harga_per_kg', 15, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->unsignedInteger('id_ikan')->nullable(false)->change();
            $table->decimal('berat', 15, 2)->nullable(false)->change();
            $table->decimal('harga_per_kg', 15, 2)->nullable(false)->change();
        });

        Schema::dropIfExists('penjualan_items');
    }
};
