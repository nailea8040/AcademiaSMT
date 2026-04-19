<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Combinamos los middleware en un solo bloque
        $middleware->alias([
            'rol' => \App\Http\Middleware\VerificarRol::class,
        ]);
        
        // Importante para Sanctum en Laravel 11
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        // Manejador genérico para API
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                
                // 1. Manejo específico de Validación
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Datos inválidos.',
                        'errors'  => $e->errors(),
                    ], 422);
                }

                // 2. Manejo de Autenticación
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No autenticado. Por favor inicia sesión.',
                    ], 401);
                }

                // 3. Determinar el status code para el resto
                // Usamos la interfaz de Symfony para evitar el error de getStatusCode
                $status = ($e instanceof HttpExceptionInterface) ? $e->getStatusCode() : 500;
                
                // Mensaje amigable
                $message = $e->getMessage();
                if ($status == 500 && app()->environment('production')) {
                    $message = 'Error interno del servidor.';
                }

                return response()->json([
                    'success' => false,
                    'message' => $message ?: 'Ocurrió un error inesperado.',
                ], $status);
            }
        });
    })->create();