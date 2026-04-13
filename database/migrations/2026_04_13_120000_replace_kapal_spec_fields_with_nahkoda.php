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
        Schema::table('kapal', function (Blueprint $table) {
            if (! Schema::hasColumn('kapal', 'nahkoda')) {
                $table->string('nahkoda')->nullable()->after('nama_kapal');
            }
            foreach (['tahun_dibangun', 'gross_tonnage', 'deadweight_tonnage', 'panjang_meter', 'lebar_meter'] as $col) {
                if (Schema::hasColumn('kapal', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kapal', function (Blueprint $table) {
            if (! Schema::hasColumn('kapal', 'tahun_dibangun')) {
                $table->integer('tahun_dibangun')->nullable()->after('nama_kapal');
            }
            if (! Schema::hasColumn('kapal', 'gross_tonnage')) {
                $table->decimal('gross_tonnage', 10, 2)->nullable()->after('tahun_dibangun');
            }
            if (! Schema::hasColumn('kapal', 'deadweight_tonnage')) {
                $table->decimal('deadweight_tonnage', 10, 2)->nullable()->after('gross_tonnage');
            }
            if (! Schema::hasColumn('kapal', 'panjang_meter')) {
                $table->decimal('panjang_meter', 10, 2)->nullable()->after('deadweight_tonnage');
            }
            if (! Schema::hasColumn('kapal', 'lebar_meter')) {
                $table->decimal('lebar_meter', 10, 2)->nullable()->after('panjang_meter');
            }
            if (Schema::hasColumn('kapal', 'nahkoda')) {
                $table->dropColumn('nahkoda');
            }
        });
    }
};
