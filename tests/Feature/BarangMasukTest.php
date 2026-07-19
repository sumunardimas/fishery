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

    public function test_import_stock_can_be_received_for_both_item_sources(): void
    {
        $user = User::factory()->create();
        $itemPembelianId = $this->createItemPembelian();
        $perbekalanId = $this->createPerbekalan();

        foreach ([
            ['item' => 'pembelian:'.$itemPembelianId, 'quantity' => 4.5],
            ['item' => 'perbekalan:'.$perbekalanId, 'quantity' => 12],
        ] as $input) {
            $this->actingAs($user)
                ->post(route('barang-masuk.store'), [
                    'tanggal_transaksi' => '2026-07-19',
                    'item' => $input['item'],
                    'jenis_transaksi' => 'in',
                    'mode_transaksi' => 'import_awal',
                    'jumlah' => $input['quantity'],
                ])
                ->assertRedirect(route('barang-masuk.index'))
                ->assertSessionHasNoErrors();
        }

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
