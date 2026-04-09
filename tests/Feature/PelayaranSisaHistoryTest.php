<?php

namespace Tests\Feature;

use App\Models\Kapal;
use App\Models\Pelayaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelayaranSisaHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_closed_pelayaran_history_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $trip = $this->createClosedTrip();

        $this->get('/pelayaran/sisa/history')
            ->assertOk()
            ->assertSee('Riwayat Pelayaran Selesai')
            ->assertSee((string) $trip->id_pelayaran)
            ->assertSee('Kapal History Test')
            ->assertSee('Lihat Detail');
    }

    public function test_authenticated_user_can_view_closed_pelayaran_detail_in_read_only_mode(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $trip = $this->createClosedTrip();

        $this->get('/pelayaran/sisa/history/' . $trip->id_pelayaran)
            ->assertOk()
            ->assertSee('Detail Riwayat Pelayaran')
            ->assertSee('Mode Lihat Detail')
            ->assertSee('Kapal History Test');
    }

    private function createClosedTrip(): Pelayaran
    {
        $kapal = Kapal::query()->create([
            'nama_kapal' => 'Kapal History Test',
            'tahun_dibangun' => 2020,
            'gross_tonnage' => 10,
            'deadweight_tonnage' => 12,
            'panjang_meter' => 15,
            'lebar_meter' => 4,
        ]);

        return Pelayaran::query()->forceCreate([
            'id_kapal' => $kapal->id_kapal,
            'tanggal_berangkat' => '2026-04-01',
            'tanggal_tiba' => '2026-04-05',
            'tanggal_selesai' => '2026-04-06',
            'jumlah_trip' => 1,
            'keterangan' => 'Trip yang sudah selesai',
            'status_pelayaran' => 'selesai',
        ]);
    }
}
