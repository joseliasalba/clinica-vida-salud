<?php

namespace App\Notifications;

use App\Models\CitaMedica;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification
{
    public function __construct(public CitaMedica $cita, public bool $incluirDetalles) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject('Recordatorio de tu cita — Clínica Vida Salud')
            ->greeting('Hola')
            ->line('Tienes una cita en Clínica Vida Salud el '.$this->cita->inicio->timezone('America/La_Paz')->format('d/m/Y').' a las '.$this->cita->inicio->timezone('America/La_Paz')->format('H:i').' (hora de Bolivia).');
        if ($this->incluirDetalles) {
            $mail->line('Médico: '.$this->cita->medico->nombres.' '.$this->cita->medico->apellidos)
                ->line('Especialidad: '.$this->cita->especialidad->nombre);
        }

        return $mail->action('Consultar mi cita', route('citas.receipt', $this->cita))
            ->line('Los horarios pueden cambiar. Inicia sesión para consultar el estado actual de tu cita.')
            ->line('Puedes cambiar tus preferencias de recordatorios desde Mi cuenta.')
            ->salutation('Clínica Vida Salud');
    }
}
