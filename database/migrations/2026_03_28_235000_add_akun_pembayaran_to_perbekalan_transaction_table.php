<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perbekalan_transaction', function (Blueprint $table) {
            $table->string('akun_pembayaran', 20)->nullable()->after('jenis_transaksi');
            $table->index('akun_pembayaran', 'idx_perbekalan_trx_akun_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('perbekalan_transaction', function (Blueprint $table) {
            $table->dropIndex('idx_perbekalan_trx_akun_pembayaran');
            $table->dropColumn('akun_pembayaran');
        });
    }
};
