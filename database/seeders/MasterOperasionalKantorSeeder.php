<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterOperasionalKantorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            ['item' => 'ATK Kantor', 'kategori' => 'Operasional'],
            ['item' => 'Listrik Kantor', 'kategori' => 'Operasional'],
            ['item' => 'Gaji Staff Administrasi', 'kategori' => 'Gaji'],
            ['item' => 'Retribusi Kebersihan', 'kategori' => 'Retribusi'],
            ['item' => 'BBM Kendaraan Operasional', 'kategori' => 'Transportasi'],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('master_operasional_kantor')
                ->where('item', $row['item'])
                ->exists();

            if ($exists) {
                DB::table('master_operasional_kantor')
                    ->where('item', $row['item'])
                    ->update([
                        'kategori' => $row['kategori'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('master_operasional_kantor')->insert([
                    'item' => $row['item'],
                    'kategori' => $row['kategori'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
