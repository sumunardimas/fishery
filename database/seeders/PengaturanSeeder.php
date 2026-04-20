<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PengaturanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('app_settings')->upsert([
            [
                'key' => 'company_name',
                'value' => 'UD Gunungkidul',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'company_address',
                'value' => 'Sadeng, Songbayu, Girisubo, Gunungkidul DIY,',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'company_phone',
                'value' => '+6282227024502',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'company_email',
                'value' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'bank_name',
                'value' => 'BRI',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'bank_account_number',
                'value' => '0029 01 004071 56 4',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'bank_account_holder',
                'value' => 'Uum Faida',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'invoice_notes',
                'value' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['key'], ['value', 'updated_at']);
    }
}
