<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_user_shape(): void
    {
        User::create([
            'name' => 'Juan Díaz', 'email' => 'demo@insumma.co', 'password' => 'Insumma2026!',
            'role' => 'Colaborador', 'role_type' => 'user', 'initials' => 'JD', 'area' => 'Comercial', 'phone' => 'Ext. 305',
        ]);

        $res = $this->postJson('/api/auth/login', [
            'email' => 'demo@insumma.co', 'password' => 'Insumma2026!',
        ]);

        $res->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'role', 'roleType', 'initials', 'email', 'area', 'phone']])
            ->assertJsonPath('user.roleType', 'user')
            ->assertJsonPath('user.id', fn ($id) => is_string($id));
    }

    public function test_login_with_wrong_password_is_401(): void
    {
        User::create(['name' => 'A', 'email' => 'a@x.co', 'password' => 'secret123', 'role_type' => 'user']);

        $this->postJson('/api/auth/login', ['email' => 'a@x.co', 'password' => 'nope'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Correo o contraseña incorrectos.');
    }

    public function test_register_forces_user_role_and_returns_201(): void
    {
        $res = $this->postJson('/api/auth/register', [
            'name' => 'Ana Gómez', 'email' => 'ana@insumma.co', 'password' => 'Secreta123', 'area' => 'Comercial',
        ]);

        $res->assertCreated()
            ->assertJsonPath('user.roleType', 'user')
            ->assertJsonPath('user.initials', 'AG');
    }

    public function test_register_duplicate_email_is_409(): void
    {
        User::create(['name' => 'A', 'email' => 'dup@x.co', 'password' => 'secret123', 'role_type' => 'user']);

        $this->postJson('/api/auth/register', [
            'name' => 'B', 'email' => 'dup@x.co', 'password' => 'secret123', 'area' => 'X',
        ])->assertStatus(409);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_me_returns_profile_shape(): void
    {
        $user = User::create([
            'name' => 'Juan', 'email' => 'j@x.co', 'password' => 'secret123', 'role_type' => 'user',
            'color' => '#2E7D32', 'joined_at' => '2023-02-15', 'extension' => '305',
        ]);

        $this->actingAs($user)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'roleType', 'color', 'joinedAt', 'extension'])
            ->assertJsonPath('joinedAt', '2023-02-15');
    }
}
