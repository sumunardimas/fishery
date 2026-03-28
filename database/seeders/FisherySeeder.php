<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FisherySeeder extends Seeder
{
    /**
     * Seed fishery-related master and transaction tables.
     */
    public function run(): void
    {
        $now = now();

        // 1) Master data first (no foreign keys).
        $idKapalA = DB::table('kapal')->insertGetId([
            'nama_kapal' => 'KM Laut Sejahtera',
            'tahun_dibangun' => 2016,
            'gross_tonnage' => 120.50,
            'deadweight_tonnage' => 98.30,
            'panjang_meter' => 28.40,
            'lebar_meter' => 6.20,
            'created_at' => $now,
            'updated_at' => $now,
        ], 'id_kapal');

        $idKapalB = DB::table('kapal')->insertGetId([
            'nama_kapal' => 'KM Samudra Makmur',
            'tahun_dibangun' => 2019,
            'gross_tonnage' => 145.00,
            'deadweight_tonnage' => 110.75,
            'panjang_meter' => 31.00,
            'lebar_meter' => 6.80,
            'created_at' => $now,
            'updated_at' => $now,
        ], 'id_kapal');

        $idBarangSolar = (int) (DB::table('master_perbekalan')
            ->whereRaw('LOWER(nama_barang) = ?', ['solar'])
            ->value('id_barang') ?? 0);

        if ($idBarangSolar <= 0) {
            $idBarangSolar = DB::table('master_perbekalan')->insertGetId([
                'nama_barang' => 'Solar',
                'satuan' => 'liter',
                'created_at' => $now,
                'updated_at' => $now,
            ], 'id_barang');
        }

        $idBarangEs = (int) (DB::table('master_perbekalan')
            ->whereRaw('LOWER(nama_barang) = ?', ['es'])
            ->value('id_barang') ?? 0);

        if ($idBarangEs <= 0) {
            $idBarangEs = DB::table('master_perbekalan')->insertGetId([
                'nama_barang' => 'Es Balok',
                'satuan' => 'balok',
                'created_at' => $now,
                'updated_at' => $now,
            ], 'id_barang');
        }

        $idGudangUtama = DB::table('master_gudang')->insertGetId([
            'nama_gudang' => 'Gudang Utama Pelabuhan',
            'lokasi' => 'Belawan',
            'penanggung_jawab' => 'Andi Pratama',
            'keterangan' => 'Gudang logistik utama',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'id_gudang');

        $idGudangDingin = DB::table('master_gudang')->insertGetId([
            'nama_gudang' => 'Cold Storage A',
            'lokasi' => 'Belawan',
            'penanggung_jawab' => 'Budi Santoso',
            'keterangan' => 'Penyimpanan hasil bongkar',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'id_gudang');

        $idIkanTuna = (int) (DB::table('master_ikan')
            ->whereRaw('LOWER(nama_ikan) LIKE ?', ['tuna%'])
            ->orderBy('id_ikan')
            ->value('id_ikan') ?? 0);

        $idIkanTongkol = (int) (DB::table('master_ikan')
            ->whereRaw('LOWER(nama_ikan) LIKE ?', ['tongkol%'])
            ->orderBy('id_ikan')
            ->value('id_ikan') ?? 0);

        if ($idIkanTuna <= 0 || $idIkanTongkol <= 0) {
            throw new \RuntimeException('MasterIkanSeeder belum menyediakan data tuna/tongkol yang diperlukan FisherySeeder.');
        }

        // 2) Pelayaran depends on kapal.
        $idPelayaranA = DB::table('pelayaran')->insertGetId([
            'id_kapal' => $idKapalA,
            'tanggal_berangkat' => '2026-02-01',
            'tanggal_tiba' => '2026-02-10',
            'pelabuhan_asal' => 'Belawan',
            'pelabuhan_tujuan' => 'Sabang',
            'jumlah_trip' => 1,
            'keterangan' => 'Trip awal Februari',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'id_pelayaran');

        $idPelayaranB = DB::table('pelayaran')->insertGetId([
            'id_kapal' => $idKapalB,
            'tanggal_berangkat' => '2026-02-12',
            'tanggal_tiba' => '2026-02-22',
            'pelabuhan_asal' => 'Belawan',
            'pelabuhan_tujuan' => 'Meulaboh',
            'jumlah_trip' => 1,
            'keterangan' => 'Trip kedua Februari',
            'created_at' => $now,
            'updated_at' => $now,
        ], 'id_pelayaran');

        // 3) Child transaction/report tables.
        DB::table('perbekalan')->insert([
            [
                'id_pelayaran' => $idPelayaranA,
                'id_barang' => $idBarangSolar,
                'jumlah' => 2500.00,
                'satuan' => 'liter',
                'harga_satuan' => 11800.00,
                'total_harga' => 29500000.00,
                'tanggal' => '2026-01-31',
                'keterangan' => 'Pengisian sebelum berangkat',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_pelayaran' => $idPelayaranA,
                'id_barang' => $idBarangEs,
                'jumlah' => 80.00,
                'satuan' => 'balok',
                'harga_satuan' => 25000.00,
                'total_harga' => 2000000.00,
                'tanggal' => '2026-01-31',
                'keterangan' => 'Stok pendingin ikan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('bongkaran')->insert([
            [
                'id_pelayaran' => $idPelayaranA,
                'id_ikan' => $idIkanTuna,
                'berat_timbangan' => 5200.50,
                'berat_tercatat' => 5150.00,
                'selisih_berat' => 50.50,
                'harga_per_kg' => 42000.00,
                'total_nilai' => 218421000.00,
                'tanggal_bongkar' => '2026-02-10',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_pelayaran' => $idPelayaranB,
                'id_ikan' => $idIkanTongkol,
                'berat_timbangan' => 4100.00,
                'berat_tercatat' => 4050.00,
                'selisih_berat' => 50.00,
                'harga_per_kg' => 28000.00,
                'total_nilai' => 114800000.00,
                'tanggal_bongkar' => '2026-02-22',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('operasional')->insert([
            [
                'id_pelayaran' => $idPelayaranA,
                'jenis_biaya' => 'Perawatan Mesin',
                'deskripsi' => 'Service berkala mesin utama',
                'jumlah' => 3500000.00,
                'tanggal' => '2026-02-03',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_pelayaran' => $idPelayaranB,
                'jenis_biaya' => 'Tambat Labuh',
                'deskripsi' => 'Biaya pelabuhan tujuan',
                'jumlah' => 1850000.00,
                'tanggal' => '2026-02-22',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('pendapatan')->insert([
            [
                'id_pelayaran' => $idPelayaranA,
                'sumber_pendapatan' => 'Penjualan Tuna',
                'jumlah' => 218421000.00,
                'tanggal' => '2026-02-11',
                'keterangan' => 'Pembayaran dari pembeli utama',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_pelayaran' => $idPelayaranB,
                'sumber_pendapatan' => 'Penjualan Tongkol',
                'jumlah' => 114800000.00,
                'tanggal' => '2026-02-23',
                'keterangan' => 'Pembayaran transfer',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('sisa_trip')->insert([
            [
                'id_pelayaran' => $idPelayaranA,
                'id_barang' => $idBarangSolar,
                'jumlah_sisa' => 140.00,
                'satuan' => 'liter',
                'keterangan' => 'Sisa pemakaian trip A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_pelayaran' => $idPelayaranA,
                'id_barang' => $idBarangEs,
                'jumlah_sisa' => 5.00,
                'satuan' => 'balok',
                'keterangan' => 'Sisa pendingin trip A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('penjualan')->insert([
            [
                'tanggal_penjualan' => '2026-02-11',
                'id_ikan' => $idIkanTuna,
                'berat' => 5200.50,
                'harga_per_kg' => 42000.00,
                'total_harga' => 218421000.00,
                'pembeli' => 'PT Laut Nusantara',
                'keterangan' => 'Penjualan tunai',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tanggal_penjualan' => '2026-02-23',
                'id_ikan' => $idIkanTongkol,
                'berat' => 4100.00,
                'harga_per_kg' => 28000.00,
                'total_harga' => 114800000.00,
                'pembeli' => 'CV Samudra Jaya',
                'keterangan' => 'Penjualan transfer',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('pembelian_barang')->insert([
            [
                'tanggal_pembelian' => '2026-01-30',
                'id_barang' => $idBarangSolar,
                'id_gudang' => $idGudangUtama,
                'jumlah' => 5000.00,
                'satuan' => 'liter',
                'harga_satuan' => 11750.00,
                'total_harga' => 58750000.00,
                'supplier' => 'PT Energi Bahari',
                'keterangan' => 'Pembelian stok awal bulan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'tanggal_pembelian' => '2026-01-30',
                'id_barang' => $idBarangEs,
                'id_gudang' => $idGudangDingin,
                'jumlah' => 200.00,
                'satuan' => 'balok',
                'harga_satuan' => 24000.00,
                'total_harga' => 4800000.00,
                'supplier' => 'UD Es Sejuk',
                'keterangan' => 'Stok pendingin bulanan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('operasional_kantor')->insert([
            [
                'jenis_biaya' => 'Listrik',
                'deskripsi' => 'Tagihan kantor bulan Februari',
                'jumlah' => 2250000.00,
                'tanggal' => '2026-02-28',
                'keterangan' => 'Pembayaran rutin',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'jenis_biaya' => 'Internet',
                'deskripsi' => 'Tagihan internet kantor',
                'jumlah' => 750000.00,
                'tanggal' => '2026-02-28',
                'keterangan' => 'Paket business',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('pemakaian_barang_kantor')->insert([
            [
                'id_barang' => $idBarangSolar,
                'id_gudang' => $idGudangUtama,
                'jumlah' => 45.00,
                'satuan' => 'liter',
                'tanggal' => '2026-02-05',
                'keterangan' => 'Pemakaian genset kantor',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_barang' => $idBarangEs,
                'id_gudang' => $idGudangDingin,
                'jumlah' => 10.00,
                'satuan' => 'balok',
                'tanggal' => '2026-02-08',
                'keterangan' => 'Operasional penyimpanan ikan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('arus_kas')->insert([
            'akun' => 'kas',
            'tanggal' => '2026-03-01',
            'jenis_transaksi' => 'Masuk',
            'kategori' => 'Modal',
            'deskripsi' => 'Modal awal operasional',
            'uang_masuk' => 200000000.00,
            'uang_keluar' => 0.00,
            'saldo' => 200000000.00,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('laba')->insert([
            'periode' => '2026-02',
            'total_pendapatan' => 333221000.00,
            'total_pengeluaran' => 44900000.00,
            'laba_bersih' => 288321000.00,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('laporan_penjualan')->insert([
            'periode' => '2026-02',
            'total_penjualan' => 2.00,
            'total_berat' => 9300.50,
            'total_pendapatan' => 333221000.00,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('laporan_selisih_bongkaran')->insert([
            [
                'id_pelayaran' => $idPelayaranA,
                'total_berat_timbangan' => 5200.50,
                'total_berat_catatan' => 5150.00,
                'total_selisih' => 50.50,
                'tanggal_laporan' => '2026-02-10',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_pelayaran' => $idPelayaranB,
                'total_berat_timbangan' => 4100.00,
                'total_berat_catatan' => 4050.00,
                'total_selisih' => 50.00,
                'tanggal_laporan' => '2026-02-22',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
