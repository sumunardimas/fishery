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
        Schema::create('arus_kas', function (Blueprint $table) {
            $table->increments('id_kas');
            $table->date('tanggal');
            $table->string('jenis_transaksi');
            $table->string('kategori');
            $table->text('deskripsi');
            $table->decimal('uang_masuk', 15, 2);
            $table->decimal('uang_keluar', 15, 2);
            $table->decimal('saldo', 15, 2);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arus_kas');
    }
};
