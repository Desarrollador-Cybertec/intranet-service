<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Avisa al colaborador que su cuenta ya fue activada y puede iniciar sesión. */
class AccountActivatedNotification extends Notification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('insumma.frontend_url'), '/').'/login';

        return (new MailMessage)
            ->subject('Tu cuenta ya está activa — Insumma Intranet')
            ->greeting("Hola, {$notifiable->name}:")
            ->line('Tu cuenta en la intranet corporativa ya fue activada.')
            ->action('Iniciar sesión', $url);
    }
}
