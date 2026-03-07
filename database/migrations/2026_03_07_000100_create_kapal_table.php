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
        Schema::create('kapal', function (Blueprint $table) {
            $table->increments('id_kapal');
            $table->string('nama_kapal');
            $table->integer('tahun_dibangun');
            $table->decimal('gross_tonnage', 10, 2);
            $table->decimal('deadweight_tonnage', 10, 2);
            $table->decimal('panjang_meter', 10, 2);
            $table->decimal('lebar_meter', 10, 2);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kapal');
    }
};
