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
        Schema::create('operasional_kantor', function (Blueprint $table) {
            $table->increments('id_operasional_kantor');
            $table->string('jenis_biaya');
            $table->text('deskripsi');
            $table->decimal('jumlah', 15, 2);
            $table->date('tanggal');
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
        Schema::dropIfExists('operasional_kantor');
    }
};
