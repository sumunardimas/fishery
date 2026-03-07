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
        Schema::create('stok_ikan', function (Blueprint $table) {
            $table->increments('id_stok');
            $table->unsignedInteger('id_ikan');
            $table->string('periode', 7); // Format: YYYY-MM
            $table->decimal('total_tangkapan', 15, 2)->default(0);
            $table->decimal('total_penjualan', 15, 2)->default(0);
            $table->decimal('stok_akhir', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_ikan')
                ->references('id_ikan')
                ->on('master_ikan')
                ->onDelete('cascade');

            $table->unique(['id_ikan', 'periode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_ikan');
    }
};
