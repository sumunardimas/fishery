<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->string('jenis_transaksi', 20)->default('penjualan')->after('tanggal_penjualan')->index();
            $table->string('tujuan_lawuhan')->nullable()->after('jenis_transaksi');
        });
    }

    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropIndex(['jenis_transaksi']);
            $table->dropColumn(['jenis_transaksi', 'tujuan_lawuhan']);
        });
    }
};
