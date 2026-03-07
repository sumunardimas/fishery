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
        Schema::create('master_operasional_kantor', function (Blueprint $table) {
            $table->increments('id_master_operasional_kantor');
            $table->string('item');
            $table->enum('kategori', ['Operasional', 'Gaji', 'Retribusi', 'Transportasi']);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique('item');
            $table->index('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_operasional_kantor');
    }
};
