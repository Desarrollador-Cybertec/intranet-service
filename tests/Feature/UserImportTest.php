<?php

namespace Tests\Feature;

use App\Models\SumateParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserImportTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = storage_path('framework/testing/nomina.csv');
        @mkdir(dirname($this->path), 0777, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    private function writeCsv(string $body): void
    {
        file_put_contents($this->path, "\u{FEFF}Nombre Mostrado,Correo Electronico,Contraseña\n".$body);
    }

    public function test_imports_users_with_incomplete_profile(): void
    {
        $this->writeCsv("Camila Altamar,aux.equipos@insumma.co,Insumma\$341311\n");

        $this->artisan('users:import', ['file' => $this->path])->assertSuccessful();

        $user = User::where('email', 'aux.equipos@insumma.co')->first();

        $this->assertNotNull($user);
        $this->assertSame('Camila Altamar', $user->name);
        $this->assertSame('CA', $user->initials);
        $this->assertSame('user', $user->role_type);
        $this->assertTrue(Hash::check('Insumma$341311', $user->password));

        // La hoja no trae cargo/área/teléfono/ingreso: el perfil queda incompleto.
        $this->assertFalse($user->isProfileComplete());
        $this->assertSame(['role', 'area', 'phone', 'joinedAt'], $user->missingProfileFields());
    }

    public function test_assigns_admin_by_email_domain(): void
    {
        $this->writeCsv(
            "Juan Suarez,juan.suarez@cybertec.com.co,Cyb3rtec928540%\n".
            "Camila Altamar,aux.equipos@insumma.co,Insumma\$341311\n"
        );

        $this->artisan('users:import', ['file' => $this->path])->assertSuccessful();

        $this->assertSame('admin', User::where('email', 'juan.suarez@cybertec.com.co')->first()->role_type);
        $this->assertSame('user', User::where('email', 'aux.equipos@insumma.co')->first()->role_type);
    }

    public function test_creates_a_sumate_participant_per_user(): void
    {
        $this->writeCsv("Camila Altamar,aux.equipos@insumma.co,Insumma\$341311\n");

        $this->artisan('users:import', ['file' => $this->path])->assertSuccessful();

        $user = User::where('email', 'aux.equipos@insumma.co')->first();
        $participant = SumateParticipant::where('user_id', $user->id)->first();

        $this->assertNotNull($participant);
        $this->assertSame('Camila Altamar', $participant->name);
    }

    public function test_is_idempotent_and_never_overwrites_existing_accounts(): void
    {
        $existing = User::factory()->create([
            'email' => 'aux.equipos@insumma.co',
            'password' => 'mi-clave-real',
            'role' => 'Coordinadora',
            'area' => 'Logística',
        ]);

        $this->writeCsv("Camila Altamar,aux.equipos@insumma.co,Insumma\$341311\n");

        $this->artisan('users:import', ['file' => $this->path])->assertSuccessful();
        $this->artisan('users:import', ['file' => $this->path])->assertSuccessful();

        $this->assertSame(1, User::where('email', 'aux.equipos@insumma.co')->count());

        $existing->refresh();
        $this->assertTrue(Hash::check('mi-clave-real', $existing->password));
        $this->assertSame('Coordinadora', $existing->role);
        $this->assertSame('Logística', $existing->area);
        $this->assertTrue($existing->isProfileComplete());
    }

    public function test_cleans_up_messy_rows(): void
    {
        $this->writeCsv(
            // el nombre es el propio correo → se humaniza la parte local
            "analista.comercial2@insumma.co,analista.comercial2@insumma.co,Insumma\$341379\n".
            // espacios sobrantes y mayúsculas en el correo
            " Deiber Blanco , AnalistaDeCompras2@insumma.co ,Insumma\$341289\n"
        );

        $this->artisan('users:import', ['file' => $this->path])->assertSuccessful();

        $this->assertSame(
            'Analista Comercial2',
            User::where('email', 'analista.comercial2@insumma.co')->first()->name,
        );
        $this->assertNotNull(User::where('email', 'analistadecompras2@insumma.co')->first());
    }

    public function test_skips_invalid_rows_without_aborting(): void
    {
        $this->writeCsv(
            "Sin Correo,no-es-un-email,Insumma\$1\n".
            "Sin Clave,sin.clave@insumma.co,\n".
            "Camila Altamar,aux.equipos@insumma.co,Insumma\$341311\n".
            "Duplicada,aux.equipos@insumma.co,Insumma\$999\n"
        );

        $this->artisan('users:import', ['file' => $this->path])->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->assertNotNull(User::where('email', 'aux.equipos@insumma.co')->first());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->writeCsv("Camila Altamar,aux.equipos@insumma.co,Insumma\$341311\n");

        $this->artisan('users:import', ['file' => $this->path, '--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, User::count());
    }
}
