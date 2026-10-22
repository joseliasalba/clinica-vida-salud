<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Tu contraseña fue actualizada')->greeting('Hola')
            ->line('La contraseña de tu cuenta en Clínica Vida Salud fue actualizada.')
            ->line('Si no realizaste este cambio, contacta a la administración de la clínica.');
    }
}
