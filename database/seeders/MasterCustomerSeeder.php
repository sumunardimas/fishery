<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $customers = [
            ['nama_customer' => 'Budi Santoso',        'alamat' => 'Wonosari, Gunung Kidul',          'telepon' => '081234567801'],
            ['nama_customer' => 'Siti Rahayu',          'alamat' => 'Playen, Gunung Kidul',            'telepon' => '081234567802'],
            ['nama_customer' => 'Ahmad Fauzi',          'alamat' => 'Semanu, Gunung Kidul',            'telepon' => '081234567803'],
            ['nama_customer' => 'Dewi Kurniawati',      'alamat' => 'Tepus, Gunung Kidul',             'telepon' => '081234567804'],
            ['nama_customer' => 'UD Mina Jaya',         'alamat' => 'Imogiri, Bantul',                 'telepon' => '081234567805'],
            ['nama_customer' => 'CV Barokah Seafood',   'alamat' => 'Jl. Parangtritis No. 12, Bantul', 'telepon' => '081234567806'],
            ['nama_customer' => 'Pasar Ikan Wonosari',  'alamat' => 'Wonosari, Gunung Kidul',          'telepon' => '081234567807'],
            ['nama_customer' => 'Hendra Wijaya',        'alamat' => 'Paliyan, Gunung Kidul',           'telepon' => '081234567808'],
            ['nama_customer' => 'Rumah Makan Pak Dhe',  'alamat' => 'Baron, Gunung Kidul',             'telepon' => '081234567809'],
            ['nama_customer' => 'Koperasi Nelayan Sadeng', 'alamat' => 'Sadeng, Girisubo, Gunung Kidul', 'telepon' => '081234567810'],
        ];

        foreach ($customers as $row) {
            DB::table('master_customer')->updateOrInsert(
                ['nama_customer' => $row['nama_customer']],
                [
                    'alamat' => $row['alamat'],
                    'telepon' => $row['telepon'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
