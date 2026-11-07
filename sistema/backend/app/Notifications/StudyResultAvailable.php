<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudyResultAvailable extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tienes una actualización en tu cuenta')
            ->greeting('Hola')
            ->line('Hay información nueva disponible en tu cuenta de Clínica Vida Salud.')
            ->action('Ingresar a mi cuenta', route('inicio'))
            ->line('Inicia sesión para consultar los detalles.')
            ->salutation('Clínica Vida Salud');
    }
}
