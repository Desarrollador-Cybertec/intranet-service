<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'user'): User
    {
        return User::factory()->create([
            'name' => 'U', 'email' => uniqid().'@x.co', 'password' => 'secret123', 'role_type' => $role,
        ]);
    }

    private function articlePayload(): array
    {
        return [
            'type' => 'noticias', 'tag' => 'x', 'tagBg' => '#fff', 'tagColor' => '#000',
            'title' => 't', 'excerpt' => 'e', 'date' => 'hoy', 'author' => 'a', 'body' => '<p>b</p>',
        ];
    }

    public function test_user_cannot_create_news(): void
    {
        $this->actingAs($this->user('user'))
            ->postJson('/api/news', $this->articlePayload())
            ->assertStatus(403);
    }

    public function test_admin_can_create_news(): void
    {
        $this->actingAs($this->user('admin'))
            ->postJson('/api/news', $this->articlePayload())
            ->assertCreated()
            ->assertJsonPath('type', 'noticias');
    }

    public function test_guest_cannot_list_news(): void
    {
        $this->getJson('/api/news')->assertStatus(401);
    }
}
