<?php

namespace Tests\Feature;

use App\Forms\FormRegistry;
use App\Models\FormSubmission;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Services\FormRecipientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormSubmissionTest extends TestCase
{
    use RefreshDatabase;

    // rrhh trae rh.* completo pero solo sst.ver; sigsst trae sst.* completo pero solo rh.ver
    // (matriz real sembrada por QaSeeder) — sirven para probar la separación por sección
    // sin recurrir a superadmin.
    private function rrhh(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'rrhh')->firstOrFail());

        return $user;
    }

    private function sigsst(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'sig-sst')->firstOrFail());

        return $user;
    }

    public function test_index_lists_the_registered_forms(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->getJson('/api/forms')->assertOk();
        $slugs = collect($res->json('items'))->pluck('slug')->all();

        $this->assertEqualsCanonicalizing(FormRegistry::slugs(), $slugs);
    }

    public function test_show_returns_field_definitions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/forms/certificado-laboral')
            ->assertOk()
            ->assertJsonPath('slug', 'certificado-laboral')
            ->assertJsonPath('section', 'rh')
            ->assertJsonPath('allowsAttachments', false);
    }

    public function test_unknown_form_slug_is_a_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/forms/no-existe')->assertNotFound();
        $this->actingAs($user)->postJson('/api/forms/no-existe/submissions', [])->assertNotFound();
    }

    public function test_submitting_a_form_persists_only_the_declared_fields(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $res = $this->actingAs($user)->postJson('/api/forms/certificado-laboral/submissions', [
            'dirigidoA' => 'Banco XYZ',
            'conSalario' => 'si',
            'motivo' => 'Trámite de crédito hipotecario',
            'extraCampoQueNoExiste' => 'esto no debe guardarse',
        ])->assertCreated();

        $submission = FormSubmission::findOrFail($res->json('id'));
        $this->assertSame('recibida', $submission->status);
        $this->assertSame($user->id, $submission->user_id);
        $this->assertArrayNotHasKey('extraCampoQueNoExiste', $submission->payload);
        $this->assertSame('Banco XYZ', $submission->payload['dirigidoA']);
    }

    public function test_missing_required_field_is_a_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/forms/certificado-laboral/submissions', [
            'conSalario' => 'si', 'motivo' => 'Un motivo cualquiera con largo suficiente',
        ])->assertStatus(422)->assertJsonValidationErrors('dirigidoA');
    }

    public function test_select_field_rejects_a_value_outside_its_options(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/forms/certificado-laboral/submissions', [
            'dirigidoA' => 'Banco XYZ', 'conSalario' => 'tal-vez', 'motivo' => 'Un motivo cualquiera con largo suficiente',
        ])->assertStatus(422)->assertJsonValidationErrors('conSalario');
    }

    public function test_textarea_min_length_is_enforced(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/forms/condiciones-inseguras/submissions', [
            'fecha' => '2026-09-02', 'lugar' => 'Bodega 3', 'area' => 'Producción', 'riesgo' => 'locativo',
            'descripcion' => 'muy corta',
        ])->assertStatus(422)->assertJsonValidationErrors('descripcion');
    }

    public function test_a_form_without_attachments_support_silently_ignores_uploaded_files(): void
    {
        // Mismo criterio que con cualquier otra clave del payload que no está en fields():
        // no se rechaza la solicitud completa por un campo extra, simplemente no se guarda.
        $user = User::factory()->create();

        $res = $this->actingAs($user)->post('/api/forms/certificado-laboral/submissions', [
            'dirigidoA' => 'Banco XYZ', 'conSalario' => 'si', 'motivo' => 'Un motivo cualquiera con largo suficiente',
            'attachments' => [UploadedFile::fake()->image('foto.jpg')],
        ])->assertCreated();

        $this->assertSame([], $res->json('attachments'));
    }

    public function test_attachments_are_stored_on_the_private_disk_and_returned_in_the_response(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $res = $this->actingAs($user)->post('/api/forms/condiciones-inseguras/submissions', [
            'fecha' => '2026-09-02', 'lugar' => 'Bodega 3', 'area' => 'Producción', 'riesgo' => 'locativo',
            'descripcion' => 'Piso mojado sin señalización cerca de la báscula.',
            'attachments' => [UploadedFile::fake()->image('evidencia.jpg')],
        ])->assertCreated();

        $this->assertCount(1, $res->json('attachments'));
        $submission = FormSubmission::findOrFail($res->json('id'));
        $path = $submission->attachments->first()->path;
        Storage::disk('local')->assertExists($path);
    }

    public function test_more_than_the_allowed_attachments_is_a_422(): void
    {
        $user = User::factory()->create();
        $files = array_map(fn ($i) => UploadedFile::fake()->image("evidencia{$i}.jpg"), range(1, 5));

        $this->actingAs($user)->post('/api/forms/condiciones-inseguras/submissions', [
            'fecha' => '2026-09-02', 'lugar' => 'Bodega 3', 'area' => 'Producción', 'riesgo' => 'locativo',
            'descripcion' => 'Piso mojado sin señalización cerca de la báscula.',
            'attachments' => $files,
        ])->assertStatus(422)->assertJsonValidationErrors('attachments');
    }

    public function test_a_disallowed_mime_type_is_a_422(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/api/forms/condiciones-inseguras/submissions', [
            'fecha' => '2026-09-02', 'lugar' => 'Bodega 3', 'area' => 'Producción', 'riesgo' => 'locativo',
            'descripcion' => 'Piso mojado sin señalización cerca de la báscula.',
            'attachments' => [UploadedFile::fake()->create('reporte.exe', 100)],
        ])->assertStatus(422)->assertJsonValidationErrors('attachments.0');
    }

    public function test_mine_lists_only_the_current_users_submissions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        FormSubmission::create(['form_slug' => 'certificado-laboral', 'user_id' => $user->id, 'payload' => ['x' => 1]]);
        FormSubmission::create(['form_slug' => 'certificado-laboral', 'user_id' => $other->id, 'payload' => ['x' => 2]]);

        $res = $this->actingAs($user)->getJson('/api/forms/submissions/mine')->assertOk();

        $this->assertCount(1, $res->json('items'));
    }

    public function test_the_owner_can_see_their_own_submission_without_any_section_permission(): void
    {
        $user = User::factory()->create();
        $submission = FormSubmission::create(['form_slug' => 'certificado-laboral', 'user_id' => $user->id, 'payload' => ['x' => 1]]);

        $this->actingAs($user)->getJson("/api/forms/submissions/{$submission->id}")
            ->assertOk()
            ->assertJsonMissing(['user'])
            ->assertJsonPath('id', $submission->id);
    }

    public function test_a_baseline_user_with_only_section_ver_cannot_list_or_view_others_submissions(): void
    {
        // "Cualquiera" ya trae rh.ver/sst.ver de línea base (para que el grid de módulos
        // se vea) — eso NO debe alcanzar para leer las solicitudes de otra persona.
        $reporter = User::factory()->create();
        $bystander = User::factory()->create();
        $submission = FormSubmission::create(['form_slug' => 'condiciones-inseguras', 'user_id' => $reporter->id, 'payload' => ['x' => 1]]);

        $this->assertContains('ver', $bystander->permissions()['sst'] ?? []);
        $this->assertNotContains('editar', $bystander->permissions()['sst'] ?? []);

        $this->actingAs($bystander)->getJson('/api/forms/condiciones-inseguras/submissions')->assertForbidden();
        $this->actingAs($bystander)->getJson("/api/forms/submissions/{$submission->id}")->assertForbidden();
    }

    public function test_sst_editar_can_list_and_view_sst_submissions_but_not_rh_ones(): void
    {
        $sigsst = $this->sigsst();
        $reporter = User::factory()->create();
        $sst = FormSubmission::create(['form_slug' => 'condiciones-inseguras', 'user_id' => $reporter->id, 'payload' => ['x' => 1]]);
        $rh = FormSubmission::create(['form_slug' => 'certificado-laboral', 'user_id' => $reporter->id, 'payload' => ['x' => 1]]);

        $this->actingAs($sigsst)->getJson('/api/forms/condiciones-inseguras/submissions')->assertOk();
        $this->actingAs($sigsst)->getJson("/api/forms/submissions/{$sst->id}")->assertOk();
        $this->actingAs($sigsst)->getJson("/api/forms/submissions/{$rh->id}")->assertForbidden();
    }

    public function test_updating_status_stamps_handled_by_and_handled_at(): void
    {
        $sigsst = $this->sigsst();
        $reporter = User::factory()->create();
        $submission = FormSubmission::create(['form_slug' => 'condiciones-inseguras', 'user_id' => $reporter->id, 'payload' => ['x' => 1]]);

        $this->actingAs($sigsst)->patchJson("/api/forms/submissions/{$submission->id}", [
            'status' => 'en_proceso', 'notes' => 'Se programó inspección.',
        ])->assertOk()->assertJsonPath('status', 'en_proceso');

        $submission->refresh();
        $this->assertSame($sigsst->id, $submission->handled_by);
        $this->assertNotNull($submission->handled_at);
    }

    public function test_a_baseline_user_cannot_update_a_submissions_status(): void
    {
        $bystander = User::factory()->create();
        $reporter = User::factory()->create();
        $submission = FormSubmission::create(['form_slug' => 'condiciones-inseguras', 'user_id' => $reporter->id, 'payload' => ['x' => 1]]);

        $this->actingAs($bystander)->patchJson("/api/forms/submissions/{$submission->id}", ['status' => 'en_proceso'])
            ->assertForbidden();
    }

    public function test_owner_can_download_their_attachment_but_a_bystander_cannot(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $bystander = User::factory()->create();
        $submission = FormSubmission::create(['form_slug' => 'condiciones-inseguras', 'user_id' => $owner->id, 'payload' => ['x' => 1]]);
        $attachment = $submission->attachments()->create([
            'disk' => 'local', 'path' => 'form-submissions/1/evidencia.jpg',
            'original_name' => 'evidencia.jpg', 'mime' => 'image/jpeg', 'size' => 10,
        ]);
        Storage::disk('local')->put($attachment->path, 'contenido');

        $this->actingAs($owner)->get("/api/forms/submissions/{$submission->id}/attachments/{$attachment->id}")->assertOk();
        $this->actingAs($bystander)->get("/api/forms/submissions/{$submission->id}/attachments/{$attachment->id}")->assertForbidden();
    }

    public function test_an_attachment_id_belonging_to_a_different_submission_is_a_404(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $a = FormSubmission::create(['form_slug' => 'condiciones-inseguras', 'user_id' => $owner->id, 'payload' => ['x' => 1]]);
        $b = FormSubmission::create(['form_slug' => 'condiciones-inseguras', 'user_id' => $owner->id, 'payload' => ['x' => 1]]);
        $attachmentOfA = $a->attachments()->create([
            'disk' => 'local', 'path' => 'form-submissions/1/evidencia.jpg',
            'original_name' => 'evidencia.jpg', 'mime' => 'image/jpeg', 'size' => 10,
        ]);

        $this->actingAs($owner)->get("/api/forms/submissions/{$b->id}/attachments/{$attachmentOfA->id}")->assertNotFound();
    }

    public function test_a_registered_module_recipient_overrides_the_config_default(): void
    {
        config(['insumma.forms.destinatarios.certificado-laboral' => ['config-default@insumma.co']]);
        Module::factory()->create([
            'section' => 'rh', 'slug' => 'certificado-laboral', 'type' => 'formulario',
            'config' => ['formSlug' => 'certificado-laboral', 'recipients' => ['modulo@insumma.co']],
        ]);

        $resolver = app(FormRecipientResolver::class);
        $this->assertSame(['modulo@insumma.co'], $resolver->resolve('certificado-laboral'));
    }

    public function test_recipient_resolution_falls_back_from_slug_config_to_default(): void
    {
        config([
            'insumma.forms.destinatarios.certificado-laboral' => [],
            'insumma.forms.destinatarios.default' => ['default@insumma.co'],
        ]);

        $resolver = app(FormRecipientResolver::class);
        $this->assertSame(['default@insumma.co'], $resolver->resolve('certificado-laboral'));
    }

    public function test_a_form_slug_not_registered_in_form_registry_is_rejected_on_module_creation(): void
    {
        $admin = User::factory()->superadmin()->create();

        $this->actingAs($admin)->postJson('/api/rh/modules', [
            'slug' => 'qa-bogus', 'label' => 'QA Bogus', 'icon' => '📄', 'color' => '#000', 'bg' => '#fff',
            'desc' => 'Descripción larga', 'type' => 'formulario', 'config' => ['formSlug' => 'no-existe'],
        ])->assertStatus(422)->assertJsonValidationErrors('config');
    }
}
