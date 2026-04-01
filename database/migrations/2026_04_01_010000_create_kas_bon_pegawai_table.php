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
        Schema::create('kas_bon_pegawai', function (Blueprint $table) {
            $table->increments('id_kas_bon_pegawai');
            $table->unsignedInteger('id_kas_sumber')->unique();
            $table->date('tanggal_pinjam');
            $table->string('akun_pengeluaran', 20);
            $table->string('nama_pegawai', 255);
            $table->decimal('nominal_awal', 15, 2);
            $table->decimal('sisa_piutang', 15, 2);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index('tanggal_pinjam');
            $table->index('nama_pegawai');
            $table->index('akun_pengeluaran');
            $table->foreign('id_kas_sumber')
                ->references('id_kas')
                ->on('arus_kas')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kas_bon_pegawai', function (Blueprint $table) {
            $table->dropForeign(['id_kas_sumber']);
        });

        Schema::dropIfExists('kas_bon_pegawai');
    }
};
