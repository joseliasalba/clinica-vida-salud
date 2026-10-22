<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        config(['view.paths' => [base_path('../frontend/views')]]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login-ip', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('recovery', function (Request $request): array {
            $email = $request->input('correo');

            return [Limit::perMinute(10)->by('ip:'.$request->ip()),
                Limit::perMinute(1)->by('correo:'.hash('sha256', is_string($email) ? mb_strtolower($email) : 'invalid'))];
        });
        RateLimiter::for('reset', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        ResetPassword::toMailUsing(function (User $user, string $token): MailMessage {
            return (new MailMessage)->subject('Restablece tu contraseña — Clínica Vida Salud')
                ->greeting('Hola')->line('Recibimos una solicitud para restablecer tu contraseña.')
                ->action('Restablecer contraseña', route('password.reset', ['token' => $token, 'correo' => $user->correo]))
                ->line('El enlace vence en 15 minutos. Si no lo solicitaste, puedes ignorar este correo.');
        });
        Auth::provider('usuarios_activos', function ($app, array $config): EloquentUserProvider {
            return (new EloquentUserProvider($app['hash'], $config['model']))
                ->withQuery(fn (Builder $query) => $query->where('activo', true));
        });

        foreach (config('permisos.catalogo', []) as $codigo => $descripcion) {
            Gate::define($codigo, fn (User $user): bool => $user->tienePermiso($codigo));
        }
    }
}
