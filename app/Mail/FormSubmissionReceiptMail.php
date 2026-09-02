<?php

namespace App\Mail;

use App\Forms\FormDefinition;
use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Confirmación de recibido para quien envió el formulario. */
class FormSubmissionReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(public FormSubmission $submission, public FormDefinition $form) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recibimos tu solicitud: {$this->form->label()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.form-submission-receipt',
            with: [
                'submission' => $this->submission,
                'form' => $this->form,
            ],
        );
    }
}
