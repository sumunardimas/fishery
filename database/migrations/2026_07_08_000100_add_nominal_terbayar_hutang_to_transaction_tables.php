<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operasional_kantor', function (Blueprint $table) {
            $table->decimal('nominal_terbayar_hutang', 15, 2)->default(0)->after('akun_pembayaran');
        });

        Schema::table('perbekalan_transaction', function (Blueprint $table) {
            $table->decimal('nominal_terbayar_hutang', 15, 2)->default(0)->after('total_harga');
        });

        Schema::table('pembelian_transaction', function (Blueprint $table) {
            $table->decimal('nominal_terbayar_hutang', 15, 2)->default(0)->after('total_harga');
        });
    }

    public function down(): void
    {
        Schema::table('operasional_kantor', function (Blueprint $table) {
            $table->dropColumn('nominal_terbayar_hutang');
        });

        Schema::table('perbekalan_transaction', function (Blueprint $table) {
            $table->dropColumn('nominal_terbayar_hutang');
        });

        Schema::table('pembelian_transaction', function (Blueprint $table) {
            $table->dropColumn('nominal_terbayar_hutang');
        });
    }
};
