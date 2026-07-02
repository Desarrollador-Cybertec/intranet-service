<?php

namespace Tests\Feature;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdeaAnonymityTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_idea_masks_author_but_persists_real_author(): void
    {
        $user = User::create(['name' => 'Juan Díaz', 'email' => 'j@x.co', 'password' => 'secret123', 'role_type' => 'user']);

        $res = $this->actingAs($user)->postJson('/api/ideas', [
            'category' => 'Bienestar laboral',
            'title' => 'Una idea válida de prueba',
            'description' => 'Descripción con más de veinte caracteres para pasar la validación.',
            'anonymous' => true,
        ])->assertCreated();

        $id = $res->json('id');

        // author_id real se persiste (auditoría)
        $this->assertSame($user->id, Idea::find($id)->author_id);

        // La respuesta pública para un usuario normal muestra "Anónimo"
        $this->actingAs($user)->getJson('/api/ideas')
            ->assertOk()
            ->assertJsonPath('items.0.author', 'Anónimo')
            ->assertJsonMissingPath('items.0.realAuthor');
    }

    public function test_admin_sees_real_author_of_anonymous_idea(): void
    {
        $author = User::create(['name' => 'Juan Díaz', 'email' => 'j@x.co', 'password' => 'secret123', 'role_type' => 'user']);
        $admin = User::create(['name' => 'Admin', 'email' => 'a@x.co', 'password' => 'secret123', 'role_type' => 'admin']);

        Idea::create([
            'category' => 'Bienestar laboral', 'title' => 'Idea anónima', 'description' => str_repeat('x', 30),
            'anonymous' => true, 'votes' => 0, 'date' => 'hoy', 'author_id' => $author->id, 'author' => 'Juan Díaz',
        ]);

        $this->actingAs($admin)->getJson('/api/ideas')
            ->assertOk()
            ->assertJsonPath('items.0.author', 'Anónimo')
            ->assertJsonPath('items.0.realAuthor', 'Juan Díaz');
    }
}
