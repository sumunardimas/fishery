<?php

namespace Database\Seeders;

use App\Models\Institusi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InstitusiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data institusi menurut PDDIKTI.
        $institusi = [
            [
                'nama' => 'Universitas Indonesia',
                'alamat' => 'Kampus Baru Depok, Universitas Indonesia, Jawa Barat, 16424, Indonesia',
                'email' => 'sipp@ui.ac.id',
                'telepon' => '0211500002',
                'website' => 'https://www.ui.ac.id/',
            ],
            [
                'nama' => 'Universitas Syiah Kuala',
                'alamat' => 'Jln. Teuku Nyak Arief, Darussalam, Banda Aceh, Aceh, 23111, Indonesia',
                'telepon' => '08116752000',
                'email' => 'humas@usk.ac.id',
                'website' => 'https://usk.ac.id/',
            ],
            [
                'nama' => 'Universitas Sumatera Utara',
                'alamat' => 'Jl. Dr. T. Mansur No. 9, Kampus Padang Bulan, Medan, 20155, Sumatera Utara',
                'telepon' => '082168889060',
                'email' => 'info@usu.ac.id',
                'website' => 'https://www.usu.ac.id/',
            ],
            [
                'nama' => 'Universitas Prima Indonesia',
                'alamat' => 'Jl. Sampul No.3, Sei Putih Bar., Kec. Medan Petisah, Kota Medan, Sumatera Utara 20118',
                'telepon' => '08116207513',
                'email' => 'contact@unprimdn.ac.id',
                'website' => 'https://unprimdn.ac.id/',
            ],
            [
                'nama' => 'Universitas Muhammadiyah Sumatera Utara',
                'alamat' => 'Jl. Kapten Muchtar Basri No.3, Glugur Darat II, Kec. Medan Tim., Kota Medan, Sumatera Utara 20238',
                'telepon' => '0616619056',
                'email' => 'rektor@umsu.ac.id',
                'website' => 'https://umsu.ac.id/',
            ],
            [
                'nama' => 'Universitas Riau',
                'alamat' => 'JKampus Bina Widya KM. 12,5, Kota Pekanbaru, Riau 28293',
                'telepon' => '076163266',
                'email' => 'rektor@unri.ac.id',
                'website' => 'https://www.unri.ac.id',
            ],
            [
                'nama' => 'Universitas YARSI',
                'alamat' => 'Menara YARSI, Kav.13 Jl. Letjend Suprapto No.1, RT.10/RW.5 Kelurahan Cempaka Putih Timur, Kecamatan Cempaka Putih, Kota Jakarta Pusat Daerah Khusus Ibukota Jakarta 10510, Kota Jakarta Pusat, Prov. D.K.I. Jakarta',
                'telepon' => '0214206675',
                'email' => 'registrar@yarsi.ac.id',
                'website' => 'https://www.yarsi.ac.id/',
            ],
            [
                'nama' => 'Universitas Pelita Harapan',
                'alamat' => 'Gedung Veteran RI, Jalan Jenderal Sudirman Kav. 50, Kec. Setia Budi, Kota Jakarta Selatan, Prov. D.K.I. Jakarta',
                'telepon' => '02125535163',
                'email' => 'registrar@uph.edu',
                'website' => 'https://www.uph.edu/',
            ],
            [
                'nama' => 'Universitas Katolik Indonesia Atma Jaya',
                'alamat' => 'Jalan Jenderal Sudirman 51 , Kota Jakarta Selatan, Prov. D.K.I. Jakarta',
                'telepon' => '0215734354',
                'email' => 'rek@atmajaya.ac.id',
                'website' => 'https://www.atmajaya.ac.id/',
            ],
            [
                'nama' => 'Universitas Padjadjaran',
                'alamat' => 'Gedung Rektorat Unpad Kampus Jatinangor Jln. Ir. Soekarno km. 21 Jatinangor, Kab. Sumedang 45363 Jawa Barat',
                'telepon' => '02284288888',
                'email' => 'humas@unpad.ac.id',
                'website' => 'https://www.unpad.ac.id/',
            ],
            [
                'nama' => 'Universitas Gadjah Mada',
                'alamat' => 'Bulaksumur, Caturtunggal, Kec. Depok, Kabupaten Sleman, Daerah Istimewa Yogyakarta 55281',
                'telepon' => '0274588688',
                'email' => 'info@ugm.ac.id',
                'website' => 'https://ugm.ac.id/',
            ],
        ];

        foreach ($institusi as $item) {
            Institusi::firstOrCreate($item);
        }
    }
}
