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
            ['nama_customer' => 'PT Laut Nusantara', 'alamat' => 'Belawan, Medan', 'telepon' => '0618001111'],
            ['nama_customer' => 'CV Samudra Jaya', 'alamat' => 'Banda Aceh', 'telepon' => '0651999222'],
            ['nama_customer' => 'UD Ikan Segar', 'alamat' => 'Pekanbaru', 'telepon' => null],
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
