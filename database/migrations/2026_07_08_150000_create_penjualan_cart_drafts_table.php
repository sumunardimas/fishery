<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan_cart_drafts', function (Blueprint $table) {
            $table->increments('id_penjualan_cart_draft');
            $table->unsignedBigInteger('id_user');
            $table->unsignedInteger('id_customer');
            $table->json('payload');
            $table->timestamps();

            $table->foreign('id_user')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('id_customer')
                ->references('id_customer')
                ->on('master_customer')
                ->onDelete('cascade');

            $table->unique(['id_user', 'id_customer'], 'uq_penjualan_cart_draft_user_customer');
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_cart_drafts');
    }
};
