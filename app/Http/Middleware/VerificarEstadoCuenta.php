<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Verifica que la cuenta del usuario autenticado siga activa (estado = 1).
 *
 * Si un admin desactiva una cuenta mientras el usuario tiene un token válido,
 * este middleware rechaza las siguientes peticiones con 403.
 *
 * Registrado en bootstrap/app.php como middleware de API.
 */
class VerificarEstadoCuenta
{
    public function handle(Request $request, Closure $next): mixed
    {
        $usuario = $request->user();

        if ($usuario) {
            $estado = DB::table('usuario')
                ->where('id_usuario', $usuario->id_usuario)
                ->value('estado');

            if ((int) $estado !== 1) {
                // Revocar el token actual para forzar re-login
                $usuario->currentAccessToken()->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta ha sido desactivada. Contacta al administrador.',
                ], 403);
            }
        }

        return $next($request);
    }
}
