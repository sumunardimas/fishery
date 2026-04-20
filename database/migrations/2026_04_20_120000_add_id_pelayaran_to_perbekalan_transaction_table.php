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
        Schema::table('perbekalan_transaction', function (Blueprint $table) {
            $table->unsignedInteger('id_pelayaran')->nullable()->after('id_barang');
            $table->index(['id_pelayaran', 'id_barang'], 'idx_perbekalan_trx_pelayaran_barang');

            $table->foreign('id_pelayaran', 'fk_perbekalan_trx_pelayaran')
                ->references('id_pelayaran')
                ->on('pelayaran')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perbekalan_transaction', function (Blueprint $table) {
            $table->dropForeign('fk_perbekalan_trx_pelayaran');
            $table->dropIndex('idx_perbekalan_trx_pelayaran_barang');
            $table->dropColumn('id_pelayaran');
        });
    }
};