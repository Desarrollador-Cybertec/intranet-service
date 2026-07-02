<?php

namespace Tests\Feature;

use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_vote_is_authoritative_and_idempotent(): void
    {
        $user = User::create(['name' => 'U', 'email' => 'u@x.co', 'password' => 'secret123', 'role_type' => 'user']);
        $post = ForumPost::create([
            'title' => 't', 'body' => 'b', 'tag' => 'x', 'tags' => ['x'], 'votes' => 24,
            'author' => 'A', 'date' => 'hoy', 'replies' => 0,
        ]);

        // up → +1
        $this->actingAs($user)->postJson("/api/forum/posts/{$post->id}/vote", ['direction' => 'up'])
            ->assertOk()->assertJsonPath('votes', 25);

        // up otra vez → idempotente
        $this->actingAs($user)->postJson("/api/forum/posts/{$post->id}/vote", ['direction' => 'up'])
            ->assertOk()->assertJsonPath('votes', 25);

        // cambiar a down → -2
        $this->actingAs($user)->postJson("/api/forum/posts/{$post->id}/vote", ['direction' => 'down'])
            ->assertOk()->assertJsonPath('votes', 23);
    }

    public function test_vote_requires_valid_direction(): void
    {
        $user = User::create(['name' => 'U', 'email' => 'u@x.co', 'password' => 'secret123', 'role_type' => 'user']);
        $post = ForumPost::create(['title' => 't', 'body' => 'b', 'tag' => 'x', 'tags' => [], 'votes' => 0, 'author' => 'A', 'date' => 'hoy', 'replies' => 0]);

        $this->actingAs($user)->postJson("/api/forum/posts/{$post->id}/vote", ['direction' => 'sideways'])
            ->assertStatus(422);
    }
}
