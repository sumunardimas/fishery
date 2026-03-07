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
        Schema::create('lelang_ikan_harian', function (Blueprint $table) {
            $table->increments('id_lelang_harian');
            $table->date('tanggal')->unique();
            $table->decimal('berat_lelang', 15, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lelang_ikan_harian');
    }
};
