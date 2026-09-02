<?php

namespace App\Http\Controllers\Api;

use App\Forms\FormDefinition;
use App\Forms\FormRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormSubmissionRequest;
use App\Http\Requests\UpdateFormSubmissionRequest;
use App\Http\Resources\FormSubmissionAdminResource;
use App\Http\Resources\FormSubmissionResource;
use App\Mail\FormSubmissionMail;
use App\Mail\FormSubmissionReceiptMail;
use App\Models\FormSubmission;
use App\Models\FormSubmissionAttachment;
use App\Services\FormRecipientResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormController extends Controller
{
    public function index(): JsonResponse
    {
        $forms = collect(FormRegistry::all())->map(fn (FormDefinition $f) => $f->toArray())->values();

        return $this->items($forms);
    }

    public function show(string $slug): JsonResponse
    {
        $form = FormRegistry::find($slug);
        abort_if(! $form, 404, 'Formulario no encontrado.');

        return response()->json($form->toArray());
    }

    public function store(StoreFormSubmissionRequest $request, string $slug): JsonResponse
    {
        $form = FormRegistry::find($slug);
        abort_if(! $form, 404, 'Formulario no encontrado.');

        $fieldNames = array_column($form->fields(), 'name');

        $submission = DB::transaction(function () use ($request, $form, $slug, $fieldNames) {
            $submission = FormSubmission::create([
                'form_slug' => $slug,
                'user_id' => $request->user()->id,
                'payload' => $request->safe()->only($fieldNames),
                'status' => FormSubmission::STATUS_RECIBIDA,
            ]);

            if ($form->allowsAttachments()) {
                foreach ($request->file('attachments', []) as $file) {
                    $path = $file->store('form-submissions/'.$submission->id, 'local');
                    $submission->attachments()->create([
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            return $submission;
        });

        DB::afterCommit(fn () => $this->dispatchMail($submission, $form));

        return (new FormSubmissionResource($submission->load('attachments')))->response()->setStatusCode(201);
    }

    public function mine(Request $request): JsonResponse
    {
        $submissions = FormSubmission::with('attachments')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return $this->items(FormSubmissionResource::collection($submissions));
    }

    public function forSection(Request $request, string $slug): JsonResponse
    {
        $form = FormRegistry::find($slug);
        abort_if(! $form, 404, 'Formulario no encontrado.');
        // .editar, no .ver: "Cualquiera" ya trae {section}.ver de línea base (para que el
        // grid de módulos sea visible a todos) — la bandeja de solicitudes ajenas es una
        // capacidad de gestión, no de lectura general.
        $this->authorizeSection($request, $form->section(), 'editar');

        $query = FormSubmission::with(['user', 'attachments'])->forForm($slug);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $this->items(FormSubmissionAdminResource::collection($query->latest()->get()));
    }

    public function showSubmission(Request $request, FormSubmission $submission): FormSubmissionResource|FormSubmissionAdminResource
    {
        $form = FormRegistry::find($submission->form_slug);
        $isOwner = $submission->user_id === $request->user()->id;
        if (! $isOwner) {
            $this->authorizeSection($request, $form->section(), 'editar');

            return new FormSubmissionAdminResource($submission->load(['attachments', 'user', 'handledBy']));
        }

        return new FormSubmissionResource($submission->load('attachments'));
    }

    public function updateSubmission(UpdateFormSubmissionRequest $request, FormSubmission $submission): FormSubmissionAdminResource
    {
        $form = FormRegistry::find($submission->form_slug);
        $this->authorizeSection($request, $form->section(), 'editar');

        $data = $request->validated();
        if (array_key_exists('status', $data) && $data['status'] !== $submission->status) {
            $data['handled_by'] = $request->user()->id;
            $data['handled_at'] = now();
        }
        $submission->update($data);

        return new FormSubmissionAdminResource($submission->fresh(['attachments', 'user', 'handledBy']));
    }

    public function downloadAttachment(Request $request, FormSubmission $submission, FormSubmissionAttachment $attachment): StreamedResponse
    {
        abort_if($attachment->form_submission_id !== $submission->id, 404);

        $isOwner = $submission->user_id === $request->user()->id;
        if (! $isOwner) {
            $form = FormRegistry::find($submission->form_slug);
            $this->authorizeSection($request, $form->section(), 'editar');
        }

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function dispatchMail(FormSubmission $submission, FormDefinition $form): void
    {
        $submission = $submission->fresh(['attachments', 'user']);
        $recipients = app(FormRecipientResolver::class)->resolve($form->slug());

        if (empty($recipients)) {
            Log::warning("Sin destinatarios configurados para el formulario [{$form->slug()}]; solicitud #{$submission->id} guardada sin enviar correo.");

            return;
        }

        Mail::to($recipients)->queue(new FormSubmissionMail($submission, $form));
        Mail::to($submission->user->email)->queue(new FormSubmissionReceiptMail($submission, $form));

        $submission->update(['mailed_at' => now(), 'recipients' => $recipients]);
    }

    private function authorizeSection(Request $request, string $section, string $action = 'ver'): void
    {
        $user = $request->user();
        if ($user->isSuperadmin()) {
            return;
        }
        if (! $user->hasPermission($section, $action)) {
            abort(403, $action === 'ver'
                ? 'Esta sección no está habilitada para tu rol.'
                : 'No tienes permiso para realizar esta acción.');
        }
    }
}
