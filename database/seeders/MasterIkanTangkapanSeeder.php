<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterIkanTangkapanSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/data/master_ikan_tangkapan.csv');

        if (! file_exists($filePath)) {
            return;
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Gagal membuka file master_ikan_tangkapan.csv');
        }

        $header = fgetcsv($handle, 0, ';');
        if (is_array($header) && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        }

        $now = now();
        $records = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if ($row === [null] || count(array_filter($row, fn ($value) => $value !== null && $value !== '')) === 0) {
                continue;
            }

            $records[] = [
                'id_ikan_tangkapan' => (int) ($row[0] ?? 0),
                'nama_ikan_tangkapan' => trim((string) ($row[1] ?? '')),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($handle);

        if ($records === []) {
            return;
        }

        DB::table('master_ikan_tangkapan')->upsert(
            $records,
            ['id_ikan_tangkapan'],
            ['nama_ikan_tangkapan', 'updated_at']
        );
    }
}
