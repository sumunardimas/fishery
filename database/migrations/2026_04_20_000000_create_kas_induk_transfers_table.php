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
        Schema::create('kas_induk_transfers', function (Blueprint $table) {
            $table->increments('id_kas_induk_transfer');
            $table->unsignedInteger('id_kas_sumber')->unique();
            $table->date('tanggal_setor');
            $table->string('akun_sumber', 20);
            $table->text('deskripsi')->nullable();
            $table->decimal('nominal', 15, 2);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index('tanggal_setor');
            $table->index('akun_sumber');
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
        Schema::table('kas_induk_transfers', function (Blueprint $table) {
            $table->dropForeign(['id_kas_sumber']);
        });

        Schema::dropIfExists('kas_induk_transfers');
    }
};