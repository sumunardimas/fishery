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
        Schema::table('operasional', function (Blueprint $table) {
            $table->unsignedInteger('id_master_operasional')->nullable()->after('id_pelayaran');
            $table->foreign('id_master_operasional')->references('id_master_operasional')->on('master_operasional');
            $table->index(['id_pelayaran', 'id_master_operasional'], 'idx_operasional_pelayaran_master');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('operasional', function (Blueprint $table) {
            $table->dropForeign(['id_master_operasional']);
            $table->dropIndex('idx_operasional_pelayaran_master');
            $table->dropColumn('id_master_operasional');
        });
    }
};
