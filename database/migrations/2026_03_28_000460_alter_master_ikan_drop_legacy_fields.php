<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_ikan', function (Blueprint $table) {
            $table->dropColumn(['jenis_ikan', 'harga_default', 'keterangan']);
        });
    }

    public function down(): void
    {
        Schema::table('master_ikan', function (Blueprint $table) {
            $table->string('jenis_ikan')->after('nama_ikan');
            $table->decimal('harga_default', 15, 2)->after('jenis_ikan');
            $table->text('keterangan')->after('harga_default');
        });
    }
};
