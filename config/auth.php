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
    // Le dice a Laravel qué modelo usar y cómo encontrar al usuario
    'providers' => [
        'usuarios' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Usuario::class,
        ],
    ],

    // ── Passwords ─────────────────────────────────────────────────────────────
    // Configuración del broker de reset de contraseñas.
    // NOTA: Tu proyecto NO usa la tabla password_resets ni este broker.
    // El token se guarda directamente en la tabla usuario
    // (columnas: token_recuperacion, token_expiracion, ultima_solicitud_token).
    // Este bloque se mantiene para que Laravel no lance errores de configuración.
    'passwords' => [
        'usuarios' => [
            'provider' => 'usuarios',
            'table'    => 'password_resets', // ← tabla ya no existe, pero el broker no se usa
            'expire'   => 10,
            'throttle' => 60,
        ],
    ],

    // ── Timeout de confirmación de contraseña ─────────────────────────────────
    'password_timeout' => 10800,
];