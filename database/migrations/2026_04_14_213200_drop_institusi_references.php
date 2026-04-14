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
        if (Schema::hasTable('staffs') && Schema::hasColumn('staffs', 'institusi_id')) {
            Schema::table('staffs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('institusi_id');
            });
        }

        if (Schema::hasTable('kasirs') && Schema::hasColumn('kasirs', 'institusi_id')) {
            Schema::table('kasirs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('institusi_id');
            });
        }

        Schema::dropIfExists('institusis');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('institusis')) {
            Schema::create('institusis', function (Blueprint $table) {
                $table->id();
                $table->string('nama')->unique();
                $table->string('alamat');
                $table->string('email')->unique();
                $table->string('telepon')->unique();
                $table->string('website')->unique();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('staffs') && !Schema::hasColumn('staffs', 'institusi_id')) {
            Schema::table('staffs', function (Blueprint $table) {
                $table->foreignId('institusi_id')->nullable()->constrained();
            });
        }

        if (Schema::hasTable('kasirs') && !Schema::hasColumn('kasirs', 'institusi_id')) {
            Schema::table('kasirs', function (Blueprint $table) {
                $table->foreignId('institusi_id')->nullable()->constrained();
            });
        }
    }
};
