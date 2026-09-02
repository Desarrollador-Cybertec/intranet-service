<?php

namespace App\Mail;

use App\Forms\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Notificación al área dueña (RH/SST) de una nueva solicitud. Responder va directo al solicitante. */
class FormSubmissionMail extends Mailable implements ShouldQueue
{
    use Queueable;

    /** Adjuntar en el correo solo si el total no supera esto; si no, solo queda el enlace en la intranet. */
    private const MAX_INLINE_ATTACHMENTS_BYTES = 8 * 1024 * 1024;

    public function __construct(public FormSubmission $submission, public FormDefinition $form) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->form->label()}] {$this->submission->user->name}",
            replyTo: [$this->submission->user->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.form-submission',
            with: [
                'submission' => $this->submission,
                'form' => $this->form,
                'frontendUrl' => rtrim(config('insumma.frontend_url'), '/'),
            ],
        );
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $attachments = $this->submission->attachments;
        if ($attachments->sum('size') > self::MAX_INLINE_ATTACHMENTS_BYTES) {
            return [];
        }

        return $attachments
            ->map(fn ($a) => Attachment::fromStorageDisk($a->disk, $a->path)->as($a->original_name))
            ->all();
    }
}
