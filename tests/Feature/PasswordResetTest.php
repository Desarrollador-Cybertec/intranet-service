<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_always_responds_success_even_for_an_unknown_email(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'no-existe@insumma.co'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_forgot_password_creates_a_reset_token_and_notifies_the_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/auth/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_password_changes_the_password_and_revokes_all_tokens(): void
    {
        $user = User::factory()->create();
        $oldToken = $user->createToken('old')->plainTextToken;
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token, 'email' => $user->email, 'password' => 'NuevaClave123',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NuevaClave123', $user->fresh()->password));
        $this->assertSame(0, $user->fresh()->tokens()->count());
        $this->assertNotEmpty($oldToken); // referenciado solo para dejar explícito qué se revoca
    }

    public function test_reset_password_rejects_an_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/reset-password', [
            'token' => 'token-invalido', 'email' => $user->email, 'password' => 'NuevaClave123',
        ])->assertStatus(422);
    }

    public function test_reset_password_requires_at_least_8_characters(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token, 'email' => $user->email, 'password' => 'corta1',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }
}
