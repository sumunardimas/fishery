<?php

namespace Tests\Feature;

use App\Models\Kapal;
use App\Models\Pelayaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PelayaranSisaRekapTest extends TestCase
{
    use RefreshDatabase;

    public function test_rekap_tab_shows_supply_spent_cost_and_grand_total(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $kapal = Kapal::query()->create([
            'nama_kapal' => 'Kapal Rekap',
            'tahun_dibangun' => 2020,
            'gross_tonnage' => 10,
            'deadweight_tonnage' => 12,
            'panjang_meter' => 15,
            'lebar_meter' => 4,
        ]);

        $pelayaran = Pelayaran::query()->forceCreate([
            'id_kapal' => $kapal->id_kapal,
            'tanggal_berangkat' => '2026-04-01',
            'tanggal_tiba' => '2026-04-05',
            'jumlah_trip' => 1,
            'keterangan' => 'Trip rekap',
            'status_pelayaran' => 'aktif',
        ]);

        DB::table('master_perbekalan')->insert([
            'id_barang' => 1,
            'nama_barang' => 'Solar',
            'satuan' => 'liter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_ikan')->insert([
            [
                'id_ikan' => 1,
                'nama_ikan' => 'Ikan A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_ikan' => 2,
                'nama_ikan' => 'Ikan B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_ikan' => 3,
                'nama_ikan' => 'Ikan C',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('perbekalan_transaction')->insert([
            'tanggal_transaksi' => '2026-03-30',
            'id_barang' => 1,
            'jenis_transaksi' => 'in',
            'jumlah' => 1000,
            'harga_satuan' => 12000,
            'total_harga' => 12000000,
            'sumber_tujuan' => 'Supplier',
            'keterangan' => 'Pembelian solar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('perbekalan_pelayaran')->insert([
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'id_barang' => 1,
            'jumlah' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sisa_trip')->insert([
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'id_barang' => 1,
            'jumlah_sisa' => 100,
            'satuan' => 'liter',
            'keterangan' => 'Sisa solar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ikan_hasil_pelayaran')->insert([
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 1,
                'kategori_tangkapan' => 'pancingan_pribadi',
                'berat_hasil' => 10,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 2,
                'kategori_tangkapan' => 'pancingan_bersama',
                'berat_hasil' => 20,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 3,
                'kategori_tangkapan' => 'jaringan',
                'berat_hasil' => 30,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('operasional')->insert([
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'id_master_operasional' => null,
            'tanggal' => '2026-04-02',
            'jenis_biaya' => 'Es batu',
            'deskripsi' => 'Operasional test',
            'jumlah' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/pelayaran/sisa?pelayaran_id=' . $pelayaran->id_pelayaran . '&tab=rekap')
            ->assertOk()
            ->assertSee('Solar')
            ->assertSee('900,00')
            ->assertSee('Rp 10.800.000,00')
            ->assertSee('Grand Total Semua Komponen')
            ->assertSee('Rp 10.865.000,00');
    }
}
