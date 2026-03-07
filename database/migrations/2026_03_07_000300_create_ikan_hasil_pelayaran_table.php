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
        Schema::create('ikan_hasil_pelayaran', function (Blueprint $table) {
            $table->increments('id_hasil');
            $table->unsignedInteger('id_pelayaran');
            $table->unsignedInteger('id_ikan');
            $table->decimal('berat_hasil', 15, 2);
            $table->timestamps();

            $table->foreign('id_pelayaran')
                ->references('id_pelayaran')
                ->on('pelayaran')
                ->onDelete('cascade');

            $table->foreign('id_ikan')
                ->references('id_ikan')
                ->on('master_ikan')
                ->onDelete('restrict');

            $table->unique(['id_pelayaran', 'id_ikan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ikan_hasil_pelayaran');
    }
};
