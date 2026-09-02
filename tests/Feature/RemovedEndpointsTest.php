<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reconocimientos / Foro / Buzón de Ideas / Capacitaciones perdieron su superficie
 * HTTP en F2 (sus modelos, migraciones y datos se conservan). Ningún endpoint
 * antiguo debe seguir respondiendo.
 */
class RemovedEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_endpoints_no_longer_exist(): void
    {
        $user = User::factory()->create();

        $requests = [
            ['GET', '/api/reconocimientos'],
            ['POST', '/api/reconocimientos'],
            ['GET', '/api/forum/posts'],
            ['POST', '/api/forum/posts'],
            ['POST', '/api/forum/posts/1/vote'],
            ['GET', '/api/ideas'],
            ['POST', '/api/ideas'],
            ['POST', '/api/ideas/1/vote'],
            ['PATCH', '/api/ideas/1'],
            ['GET', '/api/capacitaciones'],
            ['POST', '/api/capacitaciones'],
            ['POST', '/api/capacitaciones/1/inscripcion'],
        ];

        foreach ($requests as [$method, $uri]) {
            $this->actingAs($user)->json($method, $uri)->assertNotFound();
        }
    }
}
