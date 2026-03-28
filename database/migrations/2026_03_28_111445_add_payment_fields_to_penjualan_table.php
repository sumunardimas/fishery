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
        Schema::table('penjualan', function (Blueprint $table) {
            $table->decimal('bayar_tunai', 15, 2)->default(0)->after('total_harga');
            $table->decimal('bayar_transfer', 15, 2)->default(0)->after('bayar_tunai');
            $table->decimal('piutang', 15, 2)->default(0)->after('bayar_transfer');
            $table->string('status_pembayaran', 20)->default('lunas')->after('piutang'); // lunas | piutang
        });
    }

    public function down(): void
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropColumn(['bayar_tunai', 'bayar_transfer', 'piutang', 'status_pembayaran']);
        });
    }
};
