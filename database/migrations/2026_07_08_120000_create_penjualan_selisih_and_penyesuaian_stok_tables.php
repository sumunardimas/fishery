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
        Schema::create('penjualan_selisih_stok', function (Blueprint $table) {
            $table->increments('id_penjualan_selisih');
            $table->unsignedInteger('id_penjualan')->unique();
            $table->string('status', 20)->default('pending');
            $table->string('catatan_kasir')->nullable();
            $table->string('catatan_admin')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('id_penjualan')
                ->references('id_penjualan')
                ->on('penjualan')
                ->onDelete('cascade');
        });

        Schema::create('penjualan_selisih_stok_items', function (Blueprint $table) {
            $table->increments('id_penjualan_selisih_item');
            $table->unsignedInteger('id_penjualan_selisih');
            $table->unsignedInteger('id_ikan');
            $table->decimal('stok_tersedia', 15, 2)->default(0);
            $table->decimal('berat_diminta', 15, 2)->default(0);
            $table->decimal('berat_selisih', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_penjualan_selisih', 'fk_penjualan_selisih_items_header')
                ->references('id_penjualan_selisih')
                ->on('penjualan_selisih_stok')
                ->onDelete('cascade');

            $table->foreign('id_ikan')
                ->references('id_ikan')
                ->on('master_ikan')
                ->onDelete('cascade');
        });

        Schema::create('penyesuaian_stok_ikan', function (Blueprint $table) {
            $table->increments('id_penyesuaian_stok');
            $table->unsignedInteger('id_penjualan_selisih')->nullable();
            $table->string('tipe_sumber', 40)->default('manual');
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->foreign('id_penjualan_selisih', 'fk_penyesuaian_stok_discrepancy')
                ->references('id_penjualan_selisih')
                ->on('penjualan_selisih_stok')
                ->nullOnDelete();
        });

        Schema::create('penyesuaian_stok_ikan_items', function (Blueprint $table) {
            $table->increments('id_penyesuaian_stok_item');
            $table->unsignedInteger('id_penyesuaian_stok');
            $table->unsignedInteger('id_storage');
            $table->unsignedInteger('id_ikan');
            $table->decimal('delta_berat', 15, 2);
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('id_penyesuaian_stok', 'fk_penyesuaian_stok_items_header')
                ->references('id_penyesuaian_stok')
                ->on('penyesuaian_stok_ikan')
                ->onDelete('cascade');

            $table->foreign('id_storage')
                ->references('id_storage')
                ->on('storage_ikan')
                ->onDelete('cascade');

            $table->foreign('id_ikan')
                ->references('id_ikan')
                ->on('master_ikan')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penyesuaian_stok_ikan_items');
        Schema::dropIfExists('penyesuaian_stok_ikan');
        Schema::dropIfExists('penjualan_selisih_stok_items');
        Schema::dropIfExists('penjualan_selisih_stok');
    }
};