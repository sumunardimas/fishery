<?php

namespace Tests\Feature;

use App\Models\Kapal;
use App\Models\Pelayaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PelayaranStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_prefills_departure_and_estimate_dates(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $today = Carbon::today();
        $oneWeekLater = Carbon::today()->addWeek();

        $this->get('/pelayaran/create')
            ->assertOk()
            ->assertSee('value="' . $today->format('Y-m-d') . '"', false)
            ->assertSee('value="' . $oneWeekLater->format('Y-m-d') . '"', false)
            ->assertDontSee('Pelabuhan Asal')
            ->assertDontSee('Pelabuhan Tujuan');
    }

    public function test_create_form_only_shows_kapal_that_are_not_currently_sailing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $availableKapal = Kapal::query()->create([
            'nama_kapal' => 'Kapal Tersedia',
            'tahun_dibangun' => 2022,
            'gross_tonnage' => 12,
            'deadweight_tonnage' => 14,
            'panjang_meter' => 16,
            'lebar_meter' => 5,
        ]);

        $activeKapal = Kapal::query()->create([
            'nama_kapal' => 'Kapal Masih Berlayar',
            'tahun_dibangun' => 2020,
            'gross_tonnage' => 13,
            'deadweight_tonnage' => 15,
            'panjang_meter' => 17,
            'lebar_meter' => 5,
        ]);

        Pelayaran::query()->create([
            'id_kapal' => $activeKapal->id_kapal,
            'tanggal_berangkat' => '2026-04-01',
            'tanggal_tiba' => '2026-04-10',
            'jumlah_trip' => 1,
            'keterangan' => 'Masih aktif',
            'status_pelayaran' => 'aktif',
        ]);

        $this->get('/pelayaran/create')
            ->assertOk()
            ->assertSee($availableKapal->nama_kapal)
            ->assertDontSee($activeKapal->nama_kapal);
    }

    public function test_user_can_store_pelayaran_without_keterangan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $kapal = Kapal::query()->create([
            'nama_kapal' => 'Kapal Uji Store',
            'tahun_dibangun' => 2021,
            'gross_tonnage' => 15,
            'deadweight_tonnage' => 17,
            'panjang_meter' => 18,
            'lebar_meter' => 5,
        ]);

        $response = $this->post('/pelayaran', [
            'id_kapal' => $kapal->id_kapal,
            'tanggal_berangkat' => '2026-04-09',
            'tanggal_tiba' => '2026-04-16',
        ]);

        $response->assertRedirect(route('pelayaran.index'));

        $pelayaran = Pelayaran::query()->firstOrFail();

        $this->assertSame(1, (int) $pelayaran->jumlah_trip);
        $this->assertSame('', (string) $pelayaran->keterangan);
        $this->assertSame('aktif', $pelayaran->status_pelayaran);
    }
}
