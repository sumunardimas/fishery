<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterItemPembelianSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $items = [
            ['nama_item' => 'Kertas A4 80gsm', 'kategori' => 'ATK', 'satuan' => 'rim', 'keterangan' => 'Kertas print/dokumen kantor'],
            ['nama_item' => 'Kertas F4', 'kategori' => 'ATK', 'satuan' => 'rim', 'keterangan' => 'Kertas arsip dan formulir'],
            ['nama_item' => 'Pulpen Biru', 'kategori' => 'ATK', 'satuan' => 'pcs', 'keterangan' => 'Alat tulis harian'],
            ['nama_item' => 'Pulpen Hitam', 'kategori' => 'ATK', 'satuan' => 'pcs', 'keterangan' => 'Alat tulis harian'],
            ['nama_item' => 'Pensil HB', 'kategori' => 'ATK', 'satuan' => 'pcs', 'keterangan' => 'Alat tulis cadangan'],
            ['nama_item' => 'Spidol Whiteboard', 'kategori' => 'ATK', 'satuan' => 'pcs', 'keterangan' => 'Untuk papan tulis'],
            ['nama_item' => 'Tinta Printer Hitam', 'kategori' => 'Tinta', 'satuan' => 'botol', 'keterangan' => 'Isi ulang printer hitam'],
            ['nama_item' => 'Tinta Printer Warna', 'kategori' => 'Tinta', 'satuan' => 'botol', 'keterangan' => 'Isi ulang printer warna'],
            ['nama_item' => 'Toner Printer', 'kategori' => 'Tinta', 'satuan' => 'pcs', 'keterangan' => 'Cartridge/toner printer'],
            ['nama_item' => 'Map Snelhecter', 'kategori' => 'ATK', 'satuan' => 'pcs', 'keterangan' => 'Berkas dokumen'],
            ['nama_item' => 'Ordner', 'kategori' => 'ATK', 'satuan' => 'pcs', 'keterangan' => 'Penyimpanan arsip'],
            ['nama_item' => 'Stapler', 'kategori' => 'Peralatan Kantor', 'satuan' => 'pcs', 'keterangan' => 'Peralatan meja kantor'],
            ['nama_item' => 'Isi Staples', 'kategori' => 'ATK', 'satuan' => 'box', 'keterangan' => 'Bahan habis pakai stapler'],
            ['nama_item' => 'Gunting', 'kategori' => 'Peralatan Kantor', 'satuan' => 'pcs', 'keterangan' => 'Peralatan meja kantor'],
            ['nama_item' => 'Lem Kertas', 'kategori' => 'ATK', 'satuan' => 'pcs', 'keterangan' => 'Bahan habis pakai'],
            ['nama_item' => 'Lakban', 'kategori' => 'ATK', 'satuan' => 'roll', 'keterangan' => 'Pengepakan dokumen/barang'],
            ['nama_item' => 'Amplop Coklat', 'kategori' => 'ATK', 'satuan' => 'pack', 'keterangan' => 'Pengiriman dokumen'],
            ['nama_item' => 'Buku Nota', 'kategori' => 'ATK', 'satuan' => 'pcs', 'keterangan' => 'Pencatatan transaksi'],
            ['nama_item' => 'Kalkulator', 'kategori' => 'Peralatan Kantor', 'satuan' => 'pcs', 'keterangan' => 'Peralatan hitung'],
            ['nama_item' => 'Flashdisk', 'kategori' => 'Peralatan Kantor', 'satuan' => 'pcs', 'keterangan' => 'Media penyimpanan data'],
        ];

        foreach ($items as $item) {
            $exists = DB::table('master_item_pembelian')
                ->where('nama_item', $item['nama_item'])
                ->first();

            if ($exists) {
                DB::table('master_item_pembelian')
                    ->where('id_item_pembelian', $exists->id_item_pembelian)
                    ->update([
                        'kategori' => $item['kategori'],
                        'satuan' => $item['satuan'],
                        'keterangan' => $item['keterangan'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('master_item_pembelian')->insert([
                    'nama_item' => $item['nama_item'],
                    'kategori' => $item['kategori'],
                    'satuan' => $item['satuan'],
                    'keterangan' => $item['keterangan'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
