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
        Schema::create('operasional', function (Blueprint $table) {
            $table->increments('id_operasional');
            $table->unsignedInteger('id_pelayaran');
            $table->string('jenis_biaya');
            $table->text('deskripsi');
            $table->decimal('jumlah', 15, 2);
            $table->date('tanggal');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('id_pelayaran')->references('id_pelayaran')->on('pelayaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operasional');
    }
};
