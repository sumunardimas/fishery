<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arus_kas', function (Blueprint $table) {
            $table->string('akun', 20)->default('kas')->after('id_kas');
            $table->index(['akun', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::table('arus_kas', function (Blueprint $table) {
            $table->dropIndex('arus_kas_akun_tanggal_index');
            $table->dropColumn('akun');
        });
    }
};
