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
        Schema::create('pembelian_transaction', function (Blueprint $table) {
            $table->increments('id_transaction');
            $table->date('tanggal_transaksi');
            $table->unsignedInteger('id_item_pembelian');
            $table->unsignedInteger('id_gudang');
            $table->enum('jenis_transaksi', ['in', 'out']);
            $table->decimal('jumlah', 15, 2);
            $table->decimal('harga_satuan', 15, 2)->nullable();
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->string('sumber_tujuan')->nullable();
            $table->text('keterangan')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->foreign('id_item_pembelian')->references('id_item_pembelian')->on('master_item_pembelian');
            $table->foreign('id_gudang')->references('id_gudang')->on('master_gudang');
            $table->index(['id_item_pembelian', 'tanggal_transaksi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_transaction');
    }
};
