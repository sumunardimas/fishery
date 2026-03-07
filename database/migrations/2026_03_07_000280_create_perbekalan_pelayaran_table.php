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
        Schema::create('perbekalan_pelayaran', function (Blueprint $table) {
            $table->increments('id_perbekalan_pelayaran');
            $table->unsignedInteger('id_pelayaran');
            $table->unsignedInteger('id_barang');
            $table->decimal('jumlah', 15, 2);
            $table->timestamps();

            $table->foreign('id_pelayaran')
                ->references('id_pelayaran')
                ->on('pelayaran')
                ->onDelete('cascade');

            $table->foreign('id_barang')
                ->references('id_barang')
                ->on('master_perbekalan')
                ->onDelete('restrict');

            $table->unique(['id_pelayaran', 'id_barang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perbekalan_pelayaran');
    }
};
