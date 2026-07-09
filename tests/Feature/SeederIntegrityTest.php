<?php

namespace Tests\Feature;

use App\Models\ForumPost;
use App\Models\Idea;
use App\Models\SumateParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_database_seeder_links_all_mock_data_to_real_users(): void
    {
        User::factory()->create(['email' => 'user@cybertec.com.co', 'name' => 'Usuario Cybertec', 'role_type' => 'user', 'initials' => 'UC', 'area' => 'Comercial']);
        User::factory()->create(['email' => 'admin@cybertec.com.co', 'name' => 'Administrador Cybertec', 'role_type' => 'admin', 'initials' => 'AC', 'area' => 'TI']);

        $this->seed();

        $this->assertSame(0, SumateParticipant::whereNull('user_id')->count());
        $this->assertSame(0, ForumPost::whereNull('author_id')->count());

        $admin = User::where('email', 'admin@cybertec.com.co')->first();
        $this->assertSame($admin->id, Idea::find(2)->author_id);
    }
}
