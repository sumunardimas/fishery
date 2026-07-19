<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryMinimumLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_configure_and_view_minimum_limit_for_both_inventory_masters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('master.item-pembelian.store'), [
            'nama_item' => 'Amplop Limit',
            'kategori' => 'ATK',
            'satuan' => 'pack',
            'limit_minimal' => 12.5,
        ])->assertRedirect(route('master.item-pembelian.index'));

        $this->post(route('master.perbekalan.store'), [
            'nama_barang' => 'Air Aki Limit',
            'satuan' => 'liter',
            'limit_minimal' => 20,
        ])->assertRedirect(route('master.perbekalan.index'));

        $itemId = (int) DB::table('master_item_pembelian')->where('nama_item', 'Amplop Limit')->value('id_item_pembelian');
        $perbekalanId = (int) DB::table('master_perbekalan')->where('nama_barang', 'Air Aki Limit')->value('id_barang');

        $this->assertDatabaseHas('master_item_pembelian', [
            'id_item_pembelian' => $itemId,
            'limit_minimal' => 12.5,
        ]);
        $this->assertDatabaseHas('master_perbekalan', [
            'id_barang' => $perbekalanId,
            'limit_minimal' => 20,
        ]);

        $this->get(route('master.item-pembelian.index'))
            ->assertOk()
            ->assertSee('Limit Minimal')
            ->assertSee('12,50')
            ->assertSee('class="table-danger"', false);

        $this->get(route('master.perbekalan.index'))
            ->assertOk()
            ->assertSee('Limit Minimal')
            ->assertSee('20,00')
            ->assertSee('class="table-danger"', false);

        DB::table('item_pembelian_stock')->insert([
            'id_item_pembelian' => $itemId,
            'stok_aktual' => 13.75,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('perbekalan_stock')->insert([
            'id_barang' => $perbekalanId,
            'stok_aktual' => 22,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('master.item-pembelian.index'))
            ->assertOk()
            ->assertSee('class="table-warning"', false);
        $this->get(route('master.perbekalan.index'))
            ->assertOk()
            ->assertSee('class="table-warning"', false);

        $this->put(route('master.item-pembelian.update', $itemId), [
            'nama_item' => 'Amplop Limit',
            'kategori' => 'ATK',
            'satuan' => 'pack',
            'limit_minimal' => 15,
        ])->assertRedirect(route('master.item-pembelian.index'));

        $this->put(route('master.perbekalan.update', $perbekalanId), [
            'nama_barang' => 'Air Aki Limit',
            'satuan' => 'liter',
            'limit_minimal' => 25.5,
        ])->assertRedirect(route('master.perbekalan.index'));

        $this->assertDatabaseHas('master_item_pembelian', ['id_item_pembelian' => $itemId, 'limit_minimal' => 15]);
        $this->assertDatabaseHas('master_perbekalan', ['id_barang' => $perbekalanId, 'limit_minimal' => 25.5]);
    }
}
