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
        Schema::create('pelayaran', function (Blueprint $table) {
            $table->increments('id_pelayaran');
            $table->unsignedInteger('id_kapal');
            $table->date('tanggal_berangkat');
            $table->date('tanggal_tiba');
            $table->string('pelabuhan_asal');
            $table->string('pelabuhan_tujuan');
            $table->integer('jumlah_trip');
            $table->text('keterangan');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('id_kapal')->references('id_kapal')->on('kapal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelayaran');
    }
};
