<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Reemplaza la notificación de reseteo de Laravel (contenido en inglés y enlace a
 * una ruta web) por una en español que apunta al SPA.
 */
class ResetPasswordNotification extends Notification
{
    public function __construct(private readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('insumma.frontend_url'), '/').'/restablecer?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Restablece tu contraseña — Insumma Intranet')
            ->greeting('Hola'.($notifiable->name ? ", {$notifiable->name}" : '').':')
            ->line('Recibimos una solicitud para restablecer tu contraseña.')
            ->action('Restablecer contraseña', $url)
            ->line('Este enlace vence en 60 minutos.')
            ->line('Si no solicitaste esto, puedes ignorar este correo.');
    }
}
