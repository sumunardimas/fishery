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
            'nahkoda' => 'Nahkoda Rekap',
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

        $this->get('/pelayaran/sisa?pelayaran_id='.$pelayaran->id_pelayaran.'&tab=rekap')
            ->assertOk()
            ->assertSee('Solar')
            ->assertSee('900,00')
            ->assertSee('Rp 10.800.000,00')
            ->assertSee('Grand Total Semua Komponen')
            ->assertSeeText('10.850.000,00');
    }

    public function test_rekap_tab_uses_fifo_cost_for_supply_spent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $kapal = Kapal::query()->create([
            'nama_kapal' => 'Kapal FIFO',
            'nahkoda' => 'Nahkoda FIFO',
        ]);

        $pelayaran = Pelayaran::query()->forceCreate([
            'id_kapal' => $kapal->id_kapal,
            'tanggal_berangkat' => '2026-04-01',
            'tanggal_tiba' => '2026-04-05',
            'jumlah_trip' => 1,
            'keterangan' => 'Trip FIFO',
            'status_pelayaran' => 'aktif',
        ]);

        DB::table('master_perbekalan')->insert([
            'id_barang' => 11,
            'nama_barang' => 'Solar FIFO',
            'satuan' => 'liter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_ikan_tangkapan')->insert([
            'id_ikan_tangkapan' => 99,
            'nama_ikan_tangkapan' => 'Ikan FIFO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_ikan')->insert([
            'id_ikan' => 99,
            'id_ikan_tangkapan' => 99,
            'nama_ikan' => 'Ikan FIFO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('perbekalan_transaction')->insert([
            [
                'tanggal_transaksi' => '2026-03-25',
                'id_barang' => 11,
                'id_pelayaran' => null,
                'jenis_transaksi' => 'in',
                'akun_pembayaran' => 'kas',
                'jumlah' => 3000,
                'harga_satuan' => 5000,
                'total_harga' => 15000000,
                'sumber_tujuan' => 'Supplier A',
                'keterangan' => 'Layer 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tanggal_transaksi' => '2026-03-28',
                'id_barang' => 11,
                'id_pelayaran' => null,
                'jenis_transaksi' => 'in',
                'akun_pembayaran' => 'kas',
                'jumlah' => 1000,
                'harga_satuan' => 6000,
                'total_harga' => 6000000,
                'sumber_tujuan' => 'Supplier B',
                'keterangan' => 'Layer 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('perbekalan_stock')->insert([
            'id_barang' => 11,
            'stok_aktual' => 4000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('perbekalan_pelayaran')->insert([
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'id_barang' => 11,
            'jumlah' => 3500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sisa_trip')->insert([
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'id_barang' => 11,
            'jumlah_sisa' => 150,
            'satuan' => 'liter',
            'keterangan' => 'Sisa solar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ikan_hasil_pelayaran')->insert([
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 99,
                'kategori_tangkapan' => 'pancingan_pribadi',
                'berat_hasil' => 1,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 99,
                'kategori_tangkapan' => 'pancingan_bersama',
                'berat_hasil' => 1,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 99,
                'kategori_tangkapan' => 'jaringan',
                'berat_hasil' => 1,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 99,
                'kategori_tangkapan' => 'tangkapan_3_ton',
                'berat_hasil' => 1,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('operasional')->insert([
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'id_master_operasional' => null,
            'tanggal' => '2026-04-03',
            'jenis_biaya' => 'Test',
            'deskripsi' => 'Operasional FIFO',
            'jumlah' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/pelayaran/sisa?pelayaran_id='.$pelayaran->id_pelayaran.'&tab=rekap')
            ->assertOk()
            ->assertSee('Solar FIFO')
            ->assertSee('3.350,00')
            ->assertSee('(3.000,00')
            ->assertSee('liter x Rp')
            ->assertSee('5.000,00)')
            ->assertSee('(350,00')
            ->assertSee('6.000,00)')
            ->assertSee('Rp 17.100.000,00');
    }

    public function test_close_pelayaran_creates_fifo_stock_out_and_reduces_stock(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $kapal = Kapal::query()->create([
            'nama_kapal' => 'Kapal Close FIFO',
            'nahkoda' => 'Nahkoda Close FIFO',
        ]);

        $pelayaran = Pelayaran::query()->forceCreate([
            'id_kapal' => $kapal->id_kapal,
            'tanggal_berangkat' => '2026-04-01',
            'tanggal_tiba' => '2026-04-05',
            'jumlah_trip' => 1,
            'keterangan' => 'Trip close FIFO',
            'status_pelayaran' => 'aktif',
        ]);

        DB::table('master_perbekalan')->insert([
            'id_barang' => 21,
            'nama_barang' => 'Solar Close',
            'satuan' => 'liter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_ikan_tangkapan')->insert([
            'id_ikan_tangkapan' => 121,
            'nama_ikan_tangkapan' => 'Ikan Close',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_ikan')->insert([
            'id_ikan' => 121,
            'id_ikan_tangkapan' => 121,
            'nama_ikan' => 'Ikan Close',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('perbekalan_transaction')->insert([
            [
                'tanggal_transaksi' => '2026-03-20',
                'id_barang' => 21,
                'id_pelayaran' => null,
                'jenis_transaksi' => 'in',
                'akun_pembayaran' => 'kas',
                'jumlah' => 3000,
                'harga_satuan' => 5000,
                'total_harga' => 15000000,
                'sumber_tujuan' => 'Supplier A',
                'keterangan' => 'Layer 1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tanggal_transaksi' => '2026-03-22',
                'id_barang' => 21,
                'id_pelayaran' => null,
                'jenis_transaksi' => 'in',
                'akun_pembayaran' => 'kas',
                'jumlah' => 1000,
                'harga_satuan' => 6000,
                'total_harga' => 6000000,
                'sumber_tujuan' => 'Supplier B',
                'keterangan' => 'Layer 2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('perbekalan_stock')->insert([
            'id_barang' => 21,
            'stok_aktual' => 4000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('master_operasional')->insert([
            'id_master_operasional' => 1,
            'nama_operasional' => 'Solar test',
            'deskripsi' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('perbekalan_pelayaran')->insert([
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'id_barang' => 21,
            'jumlah' => 3500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sisa_trip')->insert([
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'id_barang' => 21,
            'jumlah_sisa' => 150,
            'satuan' => 'liter',
            'keterangan' => 'Sisa solar',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('ikan_hasil_pelayaran')->insert([
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 121,
                'kategori_tangkapan' => 'pancingan_pribadi',
                'berat_hasil' => 1,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 121,
                'kategori_tangkapan' => 'pancingan_bersama',
                'berat_hasil' => 1,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 121,
                'kategori_tangkapan' => 'jaringan',
                'berat_hasil' => 1,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_pelayaran' => $pelayaran->id_pelayaran,
                'id_ikan' => 121,
                'kategori_tangkapan' => 'tangkapan_3_ton',
                'berat_hasil' => 1,
                'harga_per_kg' => 1000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('operasional')->insert([
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'id_master_operasional' => 1,
            'tanggal' => '2026-04-03',
            'jenis_biaya' => 'Solar test',
            'deskripsi' => 'Operasional',
            'jumlah' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('arus_kas')->insert([
            'akun' => 'kas',
            'tanggal' => '2026-03-01',
            'jenis_transaksi' => 'Masuk',
            'kategori' => 'Modal',
            'deskripsi' => 'Modal awal',
            'uang_masuk' => 50000000,
            'uang_keluar' => 0,
            'saldo' => 50000000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post('/pelayaran/sisa/close', [
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'payment_method' => 'cash',
            'bayar_tunai' => 17102500,
            'bayar_transfer' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('perbekalan_stock', [
            'id_barang' => 21,
            'stok_aktual' => 650.00,
        ]);

        $this->assertDatabaseHas('perbekalan_transaction', [
            'id_barang' => 21,
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'jenis_transaksi' => 'out',
            'jumlah' => 3350.00,
            'total_harga' => 17100000.00,
        ]);

        $this->assertDatabaseHas('arus_kas', [
            'kategori' => 'Penutupan Trip - Perbekalan Terpakai',
            'uang_keluar' => 17100000.00,
        ]);
    }
}
