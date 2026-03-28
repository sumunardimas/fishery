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
        Schema::table('master_perbekalan', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'harga_default', 'keterangan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_perbekalan', function (Blueprint $table) {
            $table->string('kategori')->default('-');
            $table->decimal('harga_default', 15, 2)->default(0);
            $table->text('keterangan')->default('-');
        });
    }
};
