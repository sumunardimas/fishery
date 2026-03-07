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
        Schema::table('pelayaran', function (Blueprint $table) {
            $table->string('status_pelayaran', 20)
                ->default('aktif')
                ->after('keterangan');
            $table->date('tanggal_selesai')->nullable()->after('status_pelayaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelayaran', function (Blueprint $table) {
            $table->dropColumn(['status_pelayaran', 'tanggal_selesai']);
        });
    }
};
