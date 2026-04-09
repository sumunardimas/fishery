<?php

namespace Tests\Feature;

use App\Models\Kapal;
use App\Models\MasterIkan;
use App\Models\MasterIkanTangkapan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StokIkanPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_stok_ikan_page_uses_master_ikan_tangkapan_names_and_dynamic_storage_columns(): void
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

        $kapalA = Kapal::query()->create([
            'nama_kapal' => 'KM Alpha',
            'tahun_dibangun' => 2020,
            'gross_tonnage' => 10,
            'deadweight_tonnage' => 12,
            'panjang_meter' => 15,
            'lebar_meter' => 4,
        ]);

        $kapalB = Kapal::query()->create([
            'nama_kapal' => 'KM Beta',
            'tahun_dibangun' => 2021,
            'gross_tonnage' => 11,
            'deadweight_tonnage' => 13,
            'panjang_meter' => 16,
            'lebar_meter' => 5,
        ]);

        $storageA = (int) DB::table('storage_ikan')->where('id_kapal', $kapalA->id_kapal)->value('id_storage');
        $storageB = (int) DB::table('storage_ikan')->where('id_kapal', $kapalB->id_kapal)->value('id_storage');

        DB::table('stok_ikan_storage')->insert([
            [
                'id_storage' => $storageA,
                'id_ikan' => $ikanA->id_ikan,
                'stok_aktual' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_storage' => $storageB,
                'id_ikan' => $ikanB->id_ikan,
                'stok_aktual' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->get(route('stok.ikan.index'))
            ->assertOk()
            ->assertSee('Stok Ikan Aktual')
            ->assertSee('Storage KM Alpha (KM Alpha)')
            ->assertSee('Storage KM Beta (KM Beta)')
            ->assertSee('TONGKOL SEGAR')
            ->assertSee('10,00')
            ->assertSee('15,00')
            ->assertSee('25,00')
            ->assertDontSee('Tongkol A')
            ->assertDontSee('Tongkol B');
    }
}
