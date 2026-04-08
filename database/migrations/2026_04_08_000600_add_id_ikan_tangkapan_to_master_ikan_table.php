<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_ikan', function (Blueprint $table) {
            $table->unsignedInteger('id_ikan_tangkapan')
                ->nullable()
                ->after('nama_ikan');

            $table->index('id_ikan_tangkapan', 'master_ikan_id_ikan_tangkapan_index');
        });
    }

    public function down(): void
    {
        Schema::table('master_ikan', function (Blueprint $table) {
            $table->dropIndex('master_ikan_id_ikan_tangkapan_index');
            $table->dropColumn('id_ikan_tangkapan');
        });
    }
};
