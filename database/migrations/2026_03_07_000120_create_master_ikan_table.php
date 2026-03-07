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
        Schema::create('master_ikan', function (Blueprint $table) {
            $table->increments('id_ikan');
            $table->string('nama_ikan');
            $table->string('jenis_ikan');
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
        Schema::dropIfExists('master_ikan');
    }
};
