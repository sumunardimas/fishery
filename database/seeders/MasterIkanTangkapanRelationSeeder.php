<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterIkanTangkapanRelationSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/data/ikan_relasi_tangkapan.csv');

        if (! file_exists($filePath)) {
            return;
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Gagal membuka file ikan_relasi_tangkapan.csv');
        }

        $header = fgetcsv($handle, 0, ';');
        if (is_array($header) && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        }

        $now = now();

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if ($row === [null] || count(array_filter($row, fn ($value) => $value !== null && $value !== '')) === 0) {
                continue;
            }

            $idIkan = isset($row[0]) && trim((string) $row[0]) !== '' ? (int) $row[0] : null;
            $namaIkan = trim((string) ($row[1] ?? ''));
            $idIkanTangkapan = isset($row[2]) && trim((string) $row[2]) !== '' ? (int) $row[2] : null;

            $query = DB::table('master_ikan');

            if ($idIkan !== null) {
                $query->where('id_ikan', $idIkan);
            } elseif ($namaIkan !== '') {
                $query->where('nama_ikan', $namaIkan);
            } else {
                continue;
            }

            $query->update([
                'id_ikan_tangkapan' => $idIkanTangkapan,
                'updated_at' => $now,
            ]);
        }

        fclose($handle);
    }
}
