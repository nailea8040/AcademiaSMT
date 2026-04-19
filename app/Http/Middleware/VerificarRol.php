<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware para verificar roles en la API.
 *
 * Registro en bootstrap/app.php:
 *   $middleware->alias(['rol' => \App\Http\Middleware\VerificarRol::class]);
 *
 * Uso en rutas:
 *   ->middleware('rol:admin')
 *   ->middleware('rol:admin,sensei')
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'No autenticado. Incluye el header: Authorization: Bearer {token}',
            ], 401);
        }

        if (!in_array($usuario->rol, $roles)) {
            return response()->json([
                'ok'               => false,
                'mensaje'          => 'No tienes permisos para esta acción.',
                'tu_rol'           => $usuario->rol,
                'roles_permitidos' => $roles,
            ], 403);
        }

        return $next($request);
    }
}