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
        Schema::create('master_perbekalan', function (Blueprint $table) {
            $table->increments('id_barang');
            $table->string('nama_barang');
            $table->string('kategori');
            $table->string('satuan');
            $table->decimal('harga_default', 15, 2);
            $table->text('keterangan');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_perbekalan');
    }
};
