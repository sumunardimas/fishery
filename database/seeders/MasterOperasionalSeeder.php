<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterOperasionalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $rows = [
            ['nama_operasional' => 'Retribusi', 'deskripsi' => 'Biaya retribusi pelabuhan'],
            ['nama_operasional' => 'Pajak', 'deskripsi' => 'Pajak operasional perjalanan'],
            ['nama_operasional' => 'Fee', 'deskripsi' => 'Biaya fee jasa pihak ketiga'],
            ['nama_operasional' => 'Upah ABK', 'deskripsi' => 'Upah awak kapal (sailors)'],
            ['nama_operasional' => 'Port Fee', 'deskripsi' => 'Biaya sandar dan layanan pelabuhan'],
            ['nama_operasional' => 'Dokumen', 'deskripsi' => 'Administrasi dokumen pelayaran'],
            ['nama_operasional' => 'Lain-lain', 'deskripsi' => 'Biaya operasional tambahan'],
        ];

        foreach ($rows as $row) {
            $exists = DB::table('master_operasional')
                ->where('nama_operasional', $row['nama_operasional'])
                ->exists();

            if ($exists) {
                DB::table('master_operasional')
                    ->where('nama_operasional', $row['nama_operasional'])
                    ->update([
                        'deskripsi' => $row['deskripsi'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('master_operasional')->insert([
                    'nama_operasional' => $row['nama_operasional'],
                    'deskripsi' => $row['deskripsi'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
