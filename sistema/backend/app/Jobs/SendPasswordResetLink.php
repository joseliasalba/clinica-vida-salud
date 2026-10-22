<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLink implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public string $correo) {}

    public function handle(): void
    {
        DB::transaction(function (): void {
            $users = User::query()->whereRaw('lower(correo) = ?', [$this->correo])->where('activo', true)->lockForUpdate()->get();
            if ($users->count() === 1) {
                Password::sendResetLink(['correo' => $users->first()->correo]);
            }
        });
    }
}
