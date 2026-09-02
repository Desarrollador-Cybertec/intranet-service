<?php

namespace Tests\Feature;

use App\Models\DirectoryPerson;
use App\Models\User;
use App\Services\DirectoryImportService;
use App\Services\DirectoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->withRoles('asistente-gerencia')->create();
    }

    public function test_public_index_keeps_the_same_shape_and_only_lists_active(): void
    {
        $viewer = User::factory()->create();
        DirectoryPerson::factory()->create(['name' => 'Laura Peña', 'active' => true]);
        DirectoryPerson::factory()->create(['name' => 'Inactiva', 'active' => false]);

        $res = $this->actingAs($viewer)->getJson('/api/directory')->assertOk();

        $items = $res->json('items');
        $this->assertCount(1, $items);
        $this->assertSame('Laura Peña', $items[0]['name']);
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'role', 'area', 'image', 'initials', 'color', 'email', 'phone'],
            array_keys($items[0]),
        );
    }

    public function test_public_index_filters_by_search_and_area(): void
    {
        $viewer = User::factory()->create();
        DirectoryPerson::factory()->create(['name' => 'Laura Peña', 'area' => 'Bioseguridad']);
        DirectoryPerson::factory()->create(['name' => 'Otro Nombre', 'area' => 'Comercial']);

        $res = $this->actingAs($viewer)->getJson('/api/directory?search=laura')->assertOk();
        $this->assertCount(1, $res->json('items'));

        $res = $this->actingAs($viewer)->getJson('/api/directory?area=Comercial')->assertOk();
        $this->assertCount(1, $res->json('items'));
        $this->assertSame('Otro Nombre', $res->json('items.0.name'));
    }

    public function test_phone_shows_extension_when_present(): void
    {
        $viewer = User::factory()->create();
        DirectoryPerson::factory()->create(['phone' => '3000000000', 'extension' => '210']);

        $res = $this->actingAs($viewer)->getJson('/api/directory')->assertOk();
        $this->assertSame('Ext. 210', $res->json('items.0.phone'));
    }

    public function test_write_actions_require_more_than_the_base_directorio_permission(): void
    {
        // "Cualquiera" ya concede directorio.ver (para que todos puedan hojear el
        // directorio), así que la frontera real de seguridad está en las escrituras.
        $plain = User::factory()->create();
        $person = DirectoryPerson::factory()->create();

        $this->actingAs($plain)->postJson('/api/directory/entries', ['name' => 'Nueva'])->assertForbidden();
        $this->actingAs($plain)->patchJson("/api/directory/entries/{$person->id}", ['name' => 'X'])->assertForbidden();
        $this->actingAs($plain)->deleteJson("/api/directory/entries/{$person->id}")->assertForbidden();
    }

    public function test_admin_creates_updates_and_deletes_an_entry(): void
    {
        $admin = $this->admin();

        $created = $this->actingAs($admin)->postJson('/api/directory/entries', [
            'name' => 'Nueva Persona', 'area' => 'TI', 'role' => 'Analista', 'email' => 'nueva@insumma.co',
        ])->assertCreated()->json();

        $this->actingAs($admin)->patchJson("/api/directory/entries/{$created['id']}", ['role' => 'Coordinador'])
            ->assertOk()
            ->assertJsonPath('role', 'Coordinador');

        $this->actingAs($admin)->deleteJson("/api/directory/entries/{$created['id']}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('directory_people', ['id' => $created['id']]);
    }

    public function test_entries_filters_by_status(): void
    {
        $admin = $this->admin();
        DirectoryPerson::factory()->create(['active' => true]);
        DirectoryPerson::factory()->create(['active' => false]);

        $res = $this->actingAs($admin)->getJson('/api/directory/entries?status=inactive')->assertOk();
        $this->assertCount(1, $res->json('items'));
        $this->assertFalse($res->json('items.0.active'));
    }

    public function test_reorder_updates_positions(): void
    {
        $admin = $this->admin();
        $a = DirectoryPerson::factory()->create(['position' => 0]);
        $b = DirectoryPerson::factory()->create(['position' => 1]);

        $this->actingAs($admin)->patchJson('/api/directory/entries/reorder', ['ids' => [$b->id, $a->id]])
            ->assertOk();

        $this->assertSame(0, $b->fresh()->position);
        $this->assertSame(1, $a->fresh()->position);
    }

    public function test_curated_fields_are_not_overwritten_by_a_profile_sync_but_identity_is(): void
    {
        $user = User::factory()->create(['name' => 'Nombre Viejo', 'area' => null, 'phone' => null]);
        $service = app(DirectoryService::class);

        // Alguien cura el cargo/área a mano (p. ej. un caso como Asistente Gerencia).
        $person = $service->syncFromUser($user);
        $person->update(['area' => 'Área Curada a Mano', 'role' => 'Cargo Curado']);

        // El usuario luego actualiza su propio nombre; el sync no debe pisar lo curado.
        $user->update(['name' => 'Nombre Nuevo']);
        $synced = $service->syncFromUser($user->fresh());

        $this->assertSame('Nombre Nuevo', $synced->name); // identidad: siempre se actualiza
        $this->assertSame('Área Curada a Mano', $synced->area); // curado: no se pisa
        $this->assertSame('Cargo Curado', $synced->role);
    }

    public function test_sync_matches_an_existing_row_by_email_when_there_is_no_user_id_yet(): void
    {
        // Fila sembrada sin cuenta (p. ej. desde un CSV), que luego se registra.
        DirectoryPerson::factory()->create(['email' => 'nueva@insumma.co', 'user_id' => null, 'area' => 'Comercial']);
        $user = User::factory()->create(['email' => 'nueva@insumma.co', 'area' => null]);

        $person = app(DirectoryService::class)->syncFromUser($user);

        $this->assertSame(1, DirectoryPerson::count());
        $this->assertSame($user->id, $person->user_id);
        $this->assertSame('Comercial', $person->area); // seguía curada, no se pierde al reclamar la fila
    }

    public function test_csv_import_creates_updates_and_skips_invalid_rows(): void
    {
        DirectoryPerson::factory()->create(['email' => 'existe@insumma.co', 'name' => 'Nombre Viejo']);

        $csv = "Nombre,Área,Cargo,Teléfono,Extensión,Correo\n"
            ."Persona Nueva,TI,Analista,3000000000,101,nueva@insumma.co\n"
            ."Nombre Actualizado,TI,Analista,3000000001,102,existe@insumma.co\n"
            .",TI,Analista,,,sin.nombre@insumma.co\n";

        $path = tempnam(sys_get_temp_dir(), 'dir_csv');
        file_put_contents($path, $csv);

        $importer = app(DirectoryImportService::class);
        $result = $importer->import($importer->readCsv($path));
        unlink($path);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertCount(1, $result['skipped']);
        $this->assertSame('Nombre Actualizado', DirectoryPerson::where('email', 'existe@insumma.co')->value('name'));
    }

    public function test_csv_import_dry_run_writes_nothing(): void
    {
        $csv = "Nombre,Área,Cargo,Teléfono,Extensión,Correo\nPersona Nueva,TI,Analista,,,nueva@insumma.co\n";
        $path = tempnam(sys_get_temp_dir(), 'dir_csv');
        file_put_contents($path, $csv);

        $importer = app(DirectoryImportService::class);
        $result = $importer->import($importer->readCsv($path), dryRun: true);
        unlink($path);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, DirectoryPerson::count());
    }
}
