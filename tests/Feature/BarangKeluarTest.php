<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarangKeluarTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_items_are_issued_with_fifo_cost_in_one_submission(): void
    {
        $user = User::factory()->create();
        $itemPembelianId = $this->createItemPembelian();
        $perbekalanId = $this->createPerbekalan();

        $this->seedIncomingLayers('pembelian_transaction', 'id_item_pembelian', $itemPembelianId, [[3, 10], [4, 20]]);
        $this->seedIncomingLayers('perbekalan_transaction', 'id_barang', $perbekalanId, [[5, 100], [5, 200]]);
        $this->seedStock('item_pembelian_stock', 'id_item_pembelian', $itemPembelianId, 7);
        $this->seedStock('perbekalan_stock', 'id_barang', $perbekalanId, 10);

        $this->actingAs($user)
            ->get(route('barang-keluar.index'))
            ->assertOk()
            ->assertSee('7,00 pack')
            ->assertSee('10,00 liter');

        $this->actingAs($user)
            ->post(route('barang-keluar.store'), [
                'tanggal_transaksi' => '2026-07-19',
                'sumber_tujuan' => 'Gudang Produksi',
                'items' => [
                    ['item' => 'pembelian:'.$itemPembelianId, 'jumlah' => 5],
                    ['item' => 'perbekalan:'.$perbekalanId, 'jumlah' => 6],
                ],
            ])
            ->assertRedirect(route('barang-keluar.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pembelian_transaction', [
            'id_item_pembelian' => $itemPembelianId,
            'jenis_transaksi' => 'out',
            'jumlah' => 5,
            'harga_satuan' => 14,
            'total_harga' => 70,
        ]);
        $this->assertDatabaseHas('perbekalan_transaction', [
            'id_barang' => $perbekalanId,
            'jenis_transaksi' => 'out',
            'jumlah' => 6,
            'total_harga' => 700,
        ]);
        $perbekalanOut = DB::table('perbekalan_transaction')
            ->where('id_barang', $perbekalanId)
            ->where('jenis_transaksi', 'out')
            ->first();
        $this->assertEqualsWithDelta(116.67, (float) $perbekalanOut->harga_satuan, 0.01);
        $this->assertDatabaseHas('item_pembelian_stock', ['id_item_pembelian' => $itemPembelianId, 'stok_aktual' => 2]);
        $this->assertDatabaseHas('perbekalan_stock', ['id_barang' => $perbekalanId, 'stok_aktual' => 4]);
    }

    public function test_all_rows_are_rolled_back_when_one_fifo_layer_is_insufficient(): void
    {
        $user = User::factory()->create();
        $firstItemId = $this->createItemPembelian('Item Cukup');
        $secondItemId = $this->createItemPembelian('Item Kurang');

        $this->seedIncomingLayers('pembelian_transaction', 'id_item_pembelian', $firstItemId, [[5, 10]]);
        $this->seedIncomingLayers('pembelian_transaction', 'id_item_pembelian', $secondItemId, [[1, 20]]);
        $this->seedStock('item_pembelian_stock', 'id_item_pembelian', $firstItemId, 5);
        $this->seedStock('item_pembelian_stock', 'id_item_pembelian', $secondItemId, 1);

        $this->actingAs($user)
            ->from(route('barang-keluar.index'))
            ->post(route('barang-keluar.store'), [
                'tanggal_transaksi' => '2026-07-19',
                'items' => [
                    ['item' => 'pembelian:'.$firstItemId, 'jumlah' => 2],
                    ['item' => 'pembelian:'.$secondItemId, 'jumlah' => 2],
                ],
            ])
            ->assertRedirect(route('barang-keluar.index'))
            ->assertSessionHasErrors('items');

        $this->assertDatabaseMissing('pembelian_transaction', [
            'id_item_pembelian' => $firstItemId,
            'jenis_transaksi' => 'out',
        ]);
        $this->assertDatabaseHas('item_pembelian_stock', [
            'id_item_pembelian' => $firstItemId,
            'stok_aktual' => 5,
        ]);
    }

    private function createItemPembelian(string $name = 'Amplop Coklat'): int
    {
        return (int) DB::table('master_item_pembelian')->insertGetId([
            'nama_item' => $name,
            'kategori' => 'ATK',
            'satuan' => 'pack',
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createPerbekalan(): int
    {
        return (int) DB::table('master_perbekalan')->insertGetId([
            'nama_barang' => 'Air Aki',
            'satuan' => 'liter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array<int, array{int, int}> $layers */
    private function seedIncomingLayers(string $table, string $idColumn, int $itemId, array $layers): void
    {
        foreach ($layers as $index => [$quantity, $price]) {
            DB::table($table)->insert([
                'tanggal_transaksi' => '2026-07-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                $idColumn => $itemId,
                'jenis_transaksi' => 'in',
                'akun_pembayaran' => 'kas',
                'jumlah' => $quantity,
                'harga_satuan' => $price,
                'total_harga' => $quantity * $price,
                'nominal_terbayar_hutang' => 0,
                'sumber_tujuan' => 'Supplier',
                'keterangan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedStock(string $table, string $idColumn, int $itemId, float $quantity): void
    {
        DB::table($table)->insert([
            $idColumn => $itemId,
            'stok_aktual' => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
