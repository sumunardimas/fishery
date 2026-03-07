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
        Schema::create('master_operasional', function (Blueprint $table) {
            $table->increments('id_master_operasional');
            $table->string('nama_operasional');
            $table->text('deskripsi')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique('nama_operasional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_operasional');
    }
};
