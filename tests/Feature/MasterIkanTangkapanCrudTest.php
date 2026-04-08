<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterIkanTangkapanCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_manage_master_ikan_tangkapan(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('master.ikan-tangkapan.index'))
            ->assertOk()
            ->assertSee('Master Ikan Tangkapan');

        $this->post(route('master.ikan-tangkapan.store'), [
            'nama_ikan_tangkapan' => 'IKAN UJI',
        ])->assertRedirect(route('master.ikan-tangkapan.index'));

        $this->assertDatabaseHas('master_ikan_tangkapan', [
            'nama_ikan_tangkapan' => 'IKAN UJI',
        ]);

        $itemId = (int) \DB::table('master_ikan_tangkapan')
            ->where('nama_ikan_tangkapan', 'IKAN UJI')
            ->value('id_ikan_tangkapan');

        $this->put(route('master.ikan-tangkapan.update', $itemId), [
            'nama_ikan_tangkapan' => 'IKAN UJI EDIT',
        ])->assertRedirect(route('master.ikan-tangkapan.index'));

        $this->assertDatabaseHas('master_ikan_tangkapan', [
            'id_ikan_tangkapan' => $itemId,
            'nama_ikan_tangkapan' => 'IKAN UJI EDIT',
        ]);

        $this->delete(route('master.ikan-tangkapan.destroy', $itemId))
            ->assertRedirect(route('master.ikan-tangkapan.index'));

        $this->assertDatabaseMissing('master_ikan_tangkapan', [
            'id_ikan_tangkapan' => $itemId,
        ]);
    }
}
