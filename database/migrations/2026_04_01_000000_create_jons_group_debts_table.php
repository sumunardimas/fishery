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
        Schema::create('jons_group_debts', function (Blueprint $table) {
            $table->increments('id_jons_group_debt');
            $table->unsignedInteger('id_kas_sumber')->unique();
            $table->date('tanggal_pinjam');
            $table->string('akun_penerimaan', 20);
            $table->text('deskripsi')->nullable();
            $table->decimal('nominal_awal', 15, 2);
            $table->decimal('sisa_hutang', 15, 2);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->index('tanggal_pinjam');
            $table->index('akun_penerimaan');
            $table->foreign('id_kas_sumber')
                ->references('id_kas')
                ->on('arus_kas')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jons_group_debts', function (Blueprint $table) {
            $table->dropForeign(['id_kas_sumber']);
        });

        Schema::dropIfExists('jons_group_debts');
    }
};