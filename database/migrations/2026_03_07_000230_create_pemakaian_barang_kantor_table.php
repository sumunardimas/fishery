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
        Schema::create('pemakaian_barang_kantor', function (Blueprint $table) {
            $table->increments('id_pemakaian');
            $table->unsignedInteger('id_barang');
            $table->unsignedInteger('id_gudang')->nullable();
            $table->decimal('jumlah', 15, 2);
            $table->string('satuan');
            $table->date('tanggal');
            $table->text('keterangan');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('id_barang')->references('id_barang')->on('master_perbekalan');
            $table->foreign('id_gudang')->references('id_gudang')->on('master_gudang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemakaian_barang_kantor');
    }
};
