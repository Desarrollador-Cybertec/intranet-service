<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Avisa a quienes pueden activar cuentas (usuarios.editar) que hay una nueva solicitud. */
class RegistrationPendingNotification extends Notification
{
    public function __construct(private readonly User $pending) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva cuenta pendiente de activación — Insumma Intranet')
            ->greeting('Hola:')
            ->line("{$this->pending->name} ({$this->pending->email}) se registró en la intranet y está pendiente de activación.")
            ->line('Actívala desde Usuarios en la intranet y asígnale un rol.');
    }
}
