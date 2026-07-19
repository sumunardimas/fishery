<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarangMasukTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_lists_item_pembelian_and_perbekalan_in_one_field(): void
    {
        $user = User::factory()->create();
        $itemPembelianId = $this->createItemPembelian();
        $perbekalanId = $this->createPerbekalan();

        $this->actingAs($user)
            ->get(route('barang-masuk.index'))
            ->assertOk()
            ->assertSee('Pembelian Barang')
            ->assertSee('Perbekalan')
            ->assertSee('Kertas Nota (rim) - Barang')
            ->assertSee('Solar Kapal (liter) - Perbekalan')
            ->assertSee('value="pembelian:'.$itemPembelianId.'"', false)
            ->assertSee('value="perbekalan:'.$perbekalanId.'"', false);
    }

    public function test_multiple_items_from_both_sources_are_saved_in_one_submission(): void
    {
        $user = User::factory()->create();
        $itemPembelianId = $this->createItemPembelian();
        $perbekalanId = $this->createPerbekalan();

        $this->actingAs($user)
            ->post(route('barang-masuk.store'), [
                'tanggal_transaksi' => '2026-07-19',
                'mode_transaksi' => 'import_awal',
                'items' => [
                    [
                        'item' => 'pembelian:'.$itemPembelianId,
                        'jumlah' => 4.5,
                        'harga_satuan' => null,
                    ],
                    [
                        'item' => 'perbekalan:'.$perbekalanId,
                        'jumlah' => 12,
                        'harga_satuan' => null,
                    ],
                ],
            ])
            ->assertRedirect(route('barang-masuk.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', '2 item barang masuk berhasil dicatat.');

        $this->assertDatabaseHas('item_pembelian_stock', [
            'id_item_pembelian' => $itemPembelianId,
            'stok_aktual' => 4.5,
        ]);
        $this->assertDatabaseHas('perbekalan_stock', [
            'id_barang' => $perbekalanId,
            'stok_aktual' => 12,
        ]);
        $this->assertDatabaseHas('pembelian_transaction', [
            'id_item_pembelian' => $itemPembelianId,
            'jenis_transaksi' => 'in',
            'jumlah' => 4.5,
        ]);
        $this->assertDatabaseHas('perbekalan_transaction', [
            'id_barang' => $perbekalanId,
            'jenis_transaksi' => 'in',
            'jumlah' => 12,
        ]);
    }

    private function createItemPembelian(): int
    {
        return (int) DB::table('master_item_pembelian')->insertGetId([
            'nama_item' => 'Kertas Nota',
            'kategori' => 'ATK',
            'satuan' => 'rim',
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPerbekalan(): int
    {
        return (int) DB::table('master_perbekalan')->insertGetId([
            'nama_barang' => 'Solar Kapal',
            'satuan' => 'liter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
