<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function article(array $overrides = []): Article
    {
        return Article::create(array_merge([
            'type' => 'eventos',
            'tag' => 'Evento',
            'tag_bg' => '#E8F5E9',
            'tag_color' => '#2E7D32',
            'title' => 'Un evento',
            'excerpt' => 'Resumen',
            'date' => '12 jul, 2026',
            'author' => 'RH',
            'imgs' => [],
            'body' => '<p>cuerpo</p>',
        ], $overrides));
    }

    public function test_summary_returns_real_stats(): void
    {
        $viewer = User::factory()->create(); // activo + perfil completo
        User::factory()->count(2)->create();
        User::factory()->inactive()->create();   // no cuenta
        User::factory()->imported()->create();   // perfil incompleto: no cuenta

        // Cumpleaños: uno hoy, otro este mes (otro día), otro en otro mes.
        $today = Carbon::today();
        $otherDay = $today->day === 1 ? 2 : 1;
        $otherMonth = ($today->month % 12) + 1;
        User::factory()->create(['name' => 'Cumple Hoy', 'birthday' => $today->copy()]);
        User::factory()->create(['birthday' => Carbon::create(1990, $today->month, $otherDay)]);
        User::factory()->create(['birthday' => Carbon::create(1990, $otherMonth, 15)]);

        // Eventos: uno futuro (cuenta), uno pasado (no).
        $this->article(['event_date' => $today->copy()->addWeek()]);
        $this->article(['event_date' => $today->copy()->subWeek()]);

        $res = $this->actingAs($viewer)->getJson('/api/dashboard')->assertOk();

        $res->assertJsonPath('stats.eventosProximos', 1);
        $this->assertSame(1, count($res->json('birthdaysToday')));
        $this->assertSame('Cumple Hoy', $res->json('birthdaysToday.0.name'));

        // colaboradores = activos + perfil completo (viewer + 2 + 3 con cumpleaños = 6).
        $this->assertSame(6, $res->json('stats.colaboradores'));
    }

    public function test_summary_counts_birthdays_this_month(): void
    {
        $viewer = User::factory()->create();
        $month = Carbon::today()->month;

        User::factory()->create(['birthday' => Carbon::create(1990, $month, 5)]);
        User::factory()->create(['birthday' => Carbon::create(1985, $month, 20)]);
        User::factory()->create(['birthday' => Carbon::create(1992, ($month % 12) + 1, 10)]); // otro mes

        $this->actingAs($viewer)->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.cumpleanosMes', 2);
    }

    public function test_notifications_feed_includes_birthdays_and_articles_sorted(): void
    {
        $viewer = User::factory()->create();
        User::factory()->create(['name' => 'Festejado', 'birthday' => Carbon::today()]);

        $this->article(['type' => 'noticias', 'title' => 'Noticia reciente', 'event_date' => null]);
        $this->article(['type' => 'reconocimientos', 'title' => 'Reco', 'event_date' => null]);

        $res = $this->actingAs($viewer)->getJson('/api/dashboard/notifications')->assertOk();

        $items = $res->json('items');
        $this->assertNotEmpty($items);

        $types = collect($items)->pluck('type')->all();
        $this->assertContains('birthday', $types);
        $this->assertContains('noticias', $types);

        // Cada item lleva una sección de navegación.
        $this->assertNotEmpty($res->json('items.0.section'));
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_admin_can_create_an_event_with_a_real_date(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/api/events', [
            'type' => 'eventos',
            'tag' => 'Evento',
            'tagBg' => '#E8F5E9',
            'tagColor' => '#2E7D32',
            'title' => 'Jornada de bienestar',
            'excerpt' => 'Resumen',
            'date' => '12 ago, 2026 · 9:00 am',
            'eventDate' => '2026-08-12',
            'author' => 'RH',
            'body' => '<p>cuerpo</p>',
        ])->assertCreated()->assertJsonPath('eventDate', '2026-08-12');
    }
}
