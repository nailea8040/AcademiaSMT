<?php

return [

    // ── Guard por defecto ─────────────────────────────────────────────────────
    // 'web'  → sesión para el navegador (vistas Blade)
    // 'api'  → Sanctum token para la app móvil
    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'usuarios',
    ],

    // ── Guards ────────────────────────────────────────────────────────────────
    'guards' => [
        // Guard web: usa sesión + cookie, para los controllers web y las vistas
        'web' => [
            'driver'   => 'session',
            'provider' => 'usuarios',
        ],

        // Guard api: usa Sanctum tokens, para los ApiControllers
        // La app móvil manda: Authorization: Bearer {token}
        'api' => [
            'driver'   => 'sanctum',
            'provider' => 'usuarios',
        ],
    ],

    // ── Providers ─────────────────────────────────────────────────────────────
    // Le dice a Laravel qué modelo usar y cómo encontrar al usuario.
    'providers' => [
        'usuarios' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Usuario::class,
        ],
    ],

    // ── Passwords ─────────────────────────────────────────────────────────────
    // IMPORTANTE: este proyecto NO usa el broker de reset de contraseñas de
    // Laravel ni la tabla 'password_resets'.
    //
    // El flujo de recuperación es completamente custom:
    //   - Token guardado en usuario.token_recuperacion
    //   - Expiración en usuario.token_expiracion
    //   - Lógica en ResetPasswordController
    //
    // Este bloque existe únicamente para que Laravel no lance un error de
    // configuración. La tabla 'password_resets' NO necesita existir.
    // No uses Password::broker() ni route('password.*') de Laravel en este
    // proyecto — las rutas de recuperación son propias (ver routes/web.php).
    'passwords' => [
        'usuarios' => [
            'provider' => 'usuarios',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_resets'),
            'expire'   => 10,
            'throttle' => 60,
        ],
    ],

    // ── Timeout de confirmación de contraseña ─────────────────────────────────
    'password_timeout' => 10800,

];