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
        Schema::table('staffs', function (Blueprint $table) {
            $table->string('whatsapp')->nullable()->change();
            $table->smallInteger('gender')->nullable()->change();
            $table->foreignId('institusi_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->string('whatsapp')->nullable(false)->change();
            $table->smallInteger('gender')->nullable(false)->change();
            $table->foreignId('institusi_id')->nullable(false)->change();
        });
    }
};
