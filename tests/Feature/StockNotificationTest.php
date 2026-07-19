<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StockNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_feed_classifies_stock_limits_and_excludes_healthy_stock(): void
    {
        $criticalId = $this->createItemPembelian('Kertas Kritis', 100, 90);
        $warningId = $this->createItemPembelian('Kertas Warning', 100, 110);
        $this->createItemPembelian('Kertas Aman', 100, 121);
        $perbekalanId = $this->createPerbekalan('Solar Warning', 50, 60);

        $notifications = app(StockNotificationService::class)->notifications();

        $this->assertCount(3, $notifications);
        $this->assertSame('danger', $notifications->firstWhere('item_id', $criticalId)->severity);
        $this->assertSame('warning', $notifications->firstWhere('item_id', $warningId)->severity);
        $perbekalanNotification = $notifications->first(
            fn ($notification) => $notification->category === 'Perbekalan' && $notification->item_id === $perbekalanId
        );
        $this->assertSame('warning', $perbekalanNotification->severity);
        $this->assertFalse($notifications->contains('item_name', 'Kertas Aman'));
    }

    public function test_notifications_appear_in_navbar_and_dedicated_page(): void
    {
        $user = User::factory()->create();
        $this->createItemPembelian('Amplop Hampir Habis', 100, 110);

        $this->actingAs($user)
            ->get(route('pemberitahuan.index'))
            ->assertOk()
            ->assertSee('Pemberitahuan')
            ->assertSee('Barang mendekati limit')
            ->assertSee('Amplop Hampir Habis')
            ->assertSee('110,00')
            ->assertSee('100,00')
            ->assertSee('Lihat Semua Pemberitahuan');
    }

    private function createItemPembelian(string $name, float $limit, float $stock): int
    {
        $id = (int) DB::table('master_item_pembelian')->insertGetId([
            'nama_item' => $name,
            'kategori' => 'ATK',
            'satuan' => 'pack',
            'limit_minimal' => $limit,
            'keterangan' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('item_pembelian_stock')->insert([
            'id_item_pembelian' => $id,
            'stok_aktual' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function createPerbekalan(string $name, float $limit, float $stock): int
    {
        $id = (int) DB::table('master_perbekalan')->insertGetId([
            'nama_barang' => $name,
            'satuan' => 'liter',
            'limit_minimal' => $limit,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('perbekalan_stock')->insert([
            'id_barang' => $id,
            'stok_aktual' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
