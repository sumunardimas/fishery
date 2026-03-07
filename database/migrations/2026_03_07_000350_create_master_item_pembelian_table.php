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
        Schema::create('master_item_pembelian', function (Blueprint $table) {
            $table->increments('id_item_pembelian');
            $table->string('nama_item');
            $table->string('kategori');
            $table->string('satuan');
            $table->text('keterangan')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique('nama_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_item_pembelian');
    }
};
