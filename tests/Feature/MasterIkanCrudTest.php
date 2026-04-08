<?php

namespace Tests\Feature;

use App\Models\MasterIkanTangkapan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterIkanCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_manage_master_ikan_with_tangkapan_relation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $groupA = MasterIkanTangkapan::query()->create([
            'nama_ikan_tangkapan' => 'GRUP A',
        ]);

        $groupB = MasterIkanTangkapan::query()->create([
            'nama_ikan_tangkapan' => 'GRUP B',
        ]);

        $this->get(route('master.ikan.index'))
            ->assertOk()
            ->assertSee('Master Ikan')
            ->assertSee('GRUP A');

        $this->post(route('master.ikan.store'), [
            'nama_ikan' => 'IKAN UJI RELASI',
            'id_ikan_tangkapan' => $groupA->id_ikan_tangkapan,
        ])->assertRedirect(route('master.ikan.index'));

        $this->assertDatabaseHas('master_ikan', [
            'nama_ikan' => 'IKAN UJI RELASI',
            'id_ikan_tangkapan' => $groupA->id_ikan_tangkapan,
        ]);

        $itemId = (int) \DB::table('master_ikan')
            ->where('nama_ikan', 'IKAN UJI RELASI')
            ->value('id_ikan');

        $this->put(route('master.ikan.update', $itemId), [
            'nama_ikan' => 'IKAN UJI RELASI EDIT',
            'id_ikan_tangkapan' => $groupB->id_ikan_tangkapan,
        ])->assertRedirect(route('master.ikan.index'));

        $this->assertDatabaseHas('master_ikan', [
            'id_ikan' => $itemId,
            'nama_ikan' => 'IKAN UJI RELASI EDIT',
            'id_ikan_tangkapan' => $groupB->id_ikan_tangkapan,
        ]);
    }
}
