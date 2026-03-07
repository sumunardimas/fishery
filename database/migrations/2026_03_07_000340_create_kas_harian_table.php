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
        Schema::create('kas_harian', function (Blueprint $table) {
            $table->increments('id_kas_harian');
            $table->date('tanggal')->unique();
            $table->decimal('saldo_awal', 15, 2);
            $table->decimal('total_masuk', 15, 2)->default(0);
            $table->decimal('total_keluar', 15, 2)->default(0);
            $table->decimal('saldo_akhir', 15, 2);
            $table->boolean('status_tutup')->default(false);
            $table->dateTime('waktu_tutup')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_harian');
    }
};
