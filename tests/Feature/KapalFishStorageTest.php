<?php

namespace Tests\Feature;

use App\Models\Kapal;
use App\Models\MasterIkan;
use App\Models\Pelayaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KapalFishStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_kapal_also_creates_fish_storage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/kapal', [
            'nama_kapal' => 'KM Storage Test',
            'tahun_dibangun' => 2020,
            'gross_tonnage' => 10,
            'deadweight_tonnage' => 12,
            'panjang_meter' => 15,
            'lebar_meter' => 4,
        ])->assertRedirect(route('kapal.index'));

        $kapal = Kapal::query()->where('nama_kapal', 'KM Storage Test')->firstOrFail();

        $this->assertDatabaseHas('storage_ikan', [
            'id_kapal' => $kapal->id_kapal,
        ]);
    }

    public function test_closing_trip_moves_catch_into_the_vessel_storage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $kapal = Kapal::query()->create([
            'nama_kapal' => 'KM Hasil Simpan',
            'tahun_dibangun' => 2021,
            'gross_tonnage' => 11,
            'deadweight_tonnage' => 13,
            'panjang_meter' => 16,
            'lebar_meter' => 4,
        ]);

        $storageId = (int) DB::table('storage_ikan')->where('id_kapal', $kapal->id_kapal)->value('id_storage');

        $ikan = MasterIkan::query()->create([
            'nama_ikan' => 'Tuna Sirip Kuning',
        ]);

        $pelayaran = Pelayaran::query()->forceCreate([
            'id_kapal' => $kapal->id_kapal,
            'tanggal_berangkat' => '2026-04-01',
            'tanggal_tiba' => '2026-04-05',
            'jumlah_trip' => 1,
            'keterangan' => 'Trip untuk uji storage',
            'status_pelayaran' => 'aktif',
        ]);

        DB::table('ikan_hasil_pelayaran')->insert([
            'id_pelayaran' => $pelayaran->id_pelayaran,
            'id_ikan' => $ikan->id_ikan,
            'kategori_tangkapan' => 'pancingan_pribadi',
            'berat_hasil' => 125.5,
            'harga_per_kg' => 20000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('pelayaran.sisa.close'), [
            'id_pelayaran' => $pelayaran->id_pelayaran,
        ])->assertRedirect(route('pelayaran.index'));

        $this->assertDatabaseHas('stok_ikan_storage', [
            'id_storage' => $storageId,
            'id_ikan' => $ikan->id_ikan,
            'stok_aktual' => 125.5,
        ]);
    }
}
