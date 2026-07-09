<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SumateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SumateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['email' => 'user@cybertec.com.co', 'name' => 'Usuario Cybertec', 'role_type' => 'user', 'initials' => 'UC', 'area' => 'Comercial']);
        User::factory()->create(['email' => 'admin@cybertec.com.co', 'name' => 'Administrador Cybertec', 'role_type' => 'admin', 'initials' => 'AC', 'area' => 'TI']);

        $this->seed(SumateSeeder::class);
    }

    private function user(): User
    {
        return User::where('email', 'user@cybertec.com.co')->first();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@cybertec.com.co')->first();
    }

    public function test_points_and_eligibility_are_computed_server_side(): void
    {
        $res = $this->actingAs($this->user())->getJson('/api/sumate/participants')->assertOk();

        $byName = collect($res->json('participantes'))->keyBy('name');

        // Usuario Cybertec: todas las acciones al tope → 100 pts, elegible.
        $this->assertSame(100, $byName['Usuario Cybertec']['pts']);
        $this->assertTrue($byName['Usuario Cybertec']['eligible']);

        // Administrador Cybertec: capacitaciones=false → NO elegible.
        $this->assertFalse($byName['Administrador Cybertec']['eligible']);

        $this->assertSame(1, $res->json('myParticipantId'));
    }

    public function test_own_participant_is_linked_when_logged_in_as_user(): void
    {
        $res = $this->actingAs($this->user())->getJson('/api/sumate/participants')->assertOk();

        $res->assertJsonPath('myParticipantId', 1);
        $this->assertSame(100, $res->json('leaderboard.myPoints'));
        $this->assertNotNull($res->json('leaderboard.myRank'));
    }

    public function test_register_action_respects_max_and_recomputes(): void
    {
        $admin = $this->admin();

        // Administrador Cybertec tiene yoAporto=2 (max 3). +1 → 3. Otro +1 no supera el max.
        $this->actingAs($admin)->postJson('/api/sumate/acciones', ['accionId' => 'yoAporto', 'delta' => 1])
            ->assertOk()->assertJsonPath('acc.yoAporto', 3);

        $this->actingAs($admin)->postJson('/api/sumate/acciones', ['accionId' => 'yoAporto', 'delta' => 1])
            ->assertOk()->assertJsonPath('acc.yoAporto', 3); // tope respetado
    }
}
