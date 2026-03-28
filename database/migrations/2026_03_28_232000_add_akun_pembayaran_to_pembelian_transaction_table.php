<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelian_transaction', function (Blueprint $table) {
            $table->string('akun_pembayaran', 20)->nullable()->after('jenis_transaksi');
            $table->index('akun_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('pembelian_transaction', function (Blueprint $table) {
            $table->dropIndex('pembelian_transaction_akun_pembayaran_index');
            $table->dropColumn('akun_pembayaran');
        });
    }
};
