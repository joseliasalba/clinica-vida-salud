<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcesarAvisosController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('seguridad.avisos_resultados_secret');
        $token = $request->bearerToken();
        if (! is_string($secret) || strlen($secret) < 32 || ! is_string($token) || ! hash_equals($secret, $token)) {
            return response()->json(['error' => 'No autorizado.'], 401);
        }
        $lock = Cache::lock('cu20:avisos', 55);
        if (! $lock->get()) {
            return response()->json(['status' => 'en_proceso'], 409);
        }
        try {
            if (! config('seguridad.avisos_resultados_habilitados')) {
                return response()->json(['status' => 'desactivado'], 503);
            }
            $result = Artisan::call('clinica:enviar-avisos-resultados', ['--limit' => 1]);

            return $result === 0 ? response()->json(['status' => 'ok']) : response()->json(['status' => 'pendiente'], 503);
        } catch (Throwable $error) {
            Log::warning('aviso_resultado.proceso_no_disponible', ['tipo' => $error::class]);

            return response()->json(['status' => 'pendiente'], 503);
        } finally {
            $lock->release();
        }
    }
}
