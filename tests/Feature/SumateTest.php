<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SumateSeeder;
use Database\Seeders\TestUsersSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SumateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);
        $this->seed(TestUsersSeeder::class);
        $this->seed(SumateSeeder::class);
    }

    private function demo(): User
    {
        return User::where('email', 'demo@insumma.co')->first();
    }

    public function test_points_and_eligibility_are_computed_server_side(): void
    {
        $res = $this->actingAs($this->demo())->getJson('/api/sumate/participants')->assertOk();

        $byName = collect($res->json('participantes'))->keyBy('name');

        // Laura Peña: todas las acciones al tope → 100 pts, elegible.
        $this->assertSame(100, $byName['Laura Peña']['pts']);
        $this->assertTrue($byName['Laura Peña']['eligible']);

        // Felipe Castro: 100 pts pero puntualidad=false → NO elegible.
        $this->assertSame(100, $byName['Felipe Castro']['pts']);
        $this->assertFalse($byName['Felipe Castro']['eligible']);

        // Juan Díaz (usuario actual): capacitaciones=false → NO elegible.
        $this->assertFalse($byName['Juan Díaz']['eligible']);
        $this->assertSame(10, $res->json('myParticipantId'));
    }

    public function test_own_participant_is_linked_when_logged_in_as_laura(): void
    {
        $laura = User::where('email', 'laura.pena@insumma.co')->first();

        $res = $this->actingAs($laura)->getJson('/api/sumate/participants')->assertOk();

        $res->assertJsonPath('myParticipantId', 1);
        $this->assertSame(100, $res->json('leaderboard.myPoints'));
        $this->assertNotNull($res->json('leaderboard.myRank'));
    }

    public function test_register_action_respects_max_and_recomputes(): void
    {
        $demo = $this->demo();

        // Juan Díaz tiene yoAporto=2 (max 3). +1 → 3. Otro +1 no supera el max.
        $this->actingAs($demo)->postJson('/api/sumate/acciones', ['accionId' => 'yoAporto', 'delta' => 1])
            ->assertOk()->assertJsonPath('acc.yoAporto', 3);

        $this->actingAs($demo)->postJson('/api/sumate/acciones', ['accionId' => 'yoAporto', 'delta' => 1])
            ->assertOk()->assertJsonPath('acc.yoAporto', 3); // tope respetado
    }
}
