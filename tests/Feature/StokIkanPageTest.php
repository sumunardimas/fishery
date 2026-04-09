<?php

namespace Tests\Feature;

use App\Models\MasterIkan;
use App\Models\MasterIkanTangkapan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StokIkanPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_stok_ikan_page_uses_master_ikan_tangkapan_names_for_linked_stock(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tangkapan = MasterIkanTangkapan::query()->create([
            'nama_ikan_tangkapan' => 'TONGKOL SEGAR',
        ]);

        $ikanA = MasterIkan::query()->create([
            'nama_ikan' => 'Tongkol A',
            'id_ikan_tangkapan' => $tangkapan->id_ikan_tangkapan,
        ]);

        $ikanB = MasterIkan::query()->create([
            'nama_ikan' => 'Tongkol B',
            'id_ikan_tangkapan' => $tangkapan->id_ikan_tangkapan,
        ]);

        $now = now();

        DB::table('stok_ikan')->insert([
            [
                'id_ikan' => $ikanA->id_ikan,
                'periode' => '2026-03',
                'total_tangkapan' => 20,
                'total_penjualan' => 5,
                'stok_akhir' => 15,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_ikan' => $ikanA->id_ikan,
                'periode' => '2026-04',
                'total_tangkapan' => 30,
                'total_penjualan' => 5,
                'stok_akhir' => 25,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id_ikan' => $ikanB->id_ikan,
                'periode' => '2026-04',
                'total_tangkapan' => 30,
                'total_penjualan' => 5,
                'stok_akhir' => 25,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $this->get(route('stok.ikan.index'))
            ->assertOk()
            ->assertSee('Stok Ikan Aktual')
            ->assertSee('TONGKOL SEGAR')
            ->assertSee('25,00')
            ->assertDontSee('Tongkol A')
            ->assertDontSee('Tongkol B');
    }
}
