<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_item_pembelian', function (Blueprint $table) {
            $table->decimal('limit_minimal', 15, 2)->default(0)->after('satuan');
        });

        Schema::table('master_perbekalan', function (Blueprint $table) {
            $table->decimal('limit_minimal', 15, 2)->default(0)->after('satuan');
        });
    }

    public function down(): void
    {
        Schema::table('master_item_pembelian', function (Blueprint $table) {
            $table->dropColumn('limit_minimal');
        });

        Schema::table('master_perbekalan', function (Blueprint $table) {
            $table->dropColumn('limit_minimal');
        });
    }
};
