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
        $this->seed();

        $this->assertSame(0, SumateParticipant::whereNull('user_id')->count());
        $this->assertSame(0, ForumPost::whereNull('author_id')->count());

        $laura = User::where('email', 'laura.pena@insumma.co')->first();
        $this->assertSame($laura->id, Idea::find(2)->author_id);
    }
}
