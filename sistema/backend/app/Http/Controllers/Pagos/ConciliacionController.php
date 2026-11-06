<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ConciliacionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('pagos.conciliacion_secret');
        $token = $request->bearerToken();
        if (! is_string($secret) || strlen($secret) < 32 || ! is_string($token) || ! hash_equals($secret, $token)) {
            return response()->json(['error' => 'No autorizado.'], 401);
        }

        $lock = Cache::lock('cu18:conciliacion', 55);
        if (! $lock->get()) {
            return response()->json(['status' => 'en_proceso'], 409);
        }

        try {
            $result = Artisan::call('clinica:conciliar-pagos', ['--limit' => 1]);

            return $result === 0
                ? response()->json(['status' => 'ok'])
                : response()->json(['status' => 'pendiente'], 503);
        } catch (Throwable $error) {
            Log::warning('pago.conciliacion_no_disponible', ['tipo' => $error::class]);

            return response()->json(['status' => 'pendiente'], 503);
        } finally {
            $lock->release();
        }
    }
}
