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
        Schema::table('operasional_kantor', function (Blueprint $table) {
            $table->unsignedInteger('id_master_operasional_kantor')->nullable()->after('id_operasional_kantor');
            $table->string('kategori')->nullable()->after('jenis_biaya');
            $table->string('item')->nullable()->after('kategori');
            $table->decimal('harga_satuan', 15, 2)->nullable()->after('deskripsi');
            $table->decimal('qty', 15, 2)->nullable()->after('harga_satuan');
            $table->decimal('total_biaya', 15, 2)->nullable()->after('jumlah');

            $table->foreign('id_master_operasional_kantor', 'fk_operasional_kantor_master')
                ->references('id_master_operasional_kantor')
                ->on('master_operasional_kantor');

            $table->index(['tanggal', 'kategori'], 'idx_operasional_kantor_tanggal_kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operasional_kantor', function (Blueprint $table) {
            $table->dropForeign('fk_operasional_kantor_master');
            $table->dropIndex('idx_operasional_kantor_tanggal_kategori');
            $table->dropColumn([
                'id_master_operasional_kantor',
                'kategori',
                'item',
                'harga_satuan',
                'qty',
                'total_biaya',
            ]);
        });
    }
};
