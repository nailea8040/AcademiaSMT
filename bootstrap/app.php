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

        // Confiar en el proxy de Railway (HTTPS detrás de proxy)
        $middleware->trustProxies(at: '*');

        // Middleware web adicional
        $middleware->web(append: [
            \App\Http\Middleware\StorageLinkCheck::class,
        ]);

        // Alias de roles
        $middleware->alias([
            'rol' => \App\Http\Middleware\VerificarRol::class,
        ]);

        // Sanctum con cookies
        $middleware->statefulApi();

        // Excluir webhook de MP del CSRF
        $middleware->validateCsrfTokens(except: [
            'api/pagos/webhook',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {

                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Datos inválidos.',
                        'errors'  => $e->errors(),
                    ], 422);
                }

                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No autenticado. Por favor inicia sesión.',
                    ], 401);
                }

                $status = ($e instanceof HttpExceptionInterface) ? $e->getStatusCode() : 500;

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