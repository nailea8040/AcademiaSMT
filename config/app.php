<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | CORRECCIÓN: cambiado de 'UTC' a 'America/Mexico_City' para que las
    | fechas de asistencia, pagos y registros coincidan con la hora local
    | de la academia en San Martín Texmelucan, Puebla.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'America/Mexico_City'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    */

    'locale' => env('APP_LOCALE', 'es'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'es'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'es_MX'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store'  => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin Email
    |--------------------------------------------------------------------------
    |
    | Correo del superusuario cuyo rol no puede modificarse desde la interfaz.
    | Se lee desde el .env para que nunca quede expuesto en el código fuente
    | ni en el HTML renderizado al cliente.
    |
    | En Railway: agrega la variable SUPER_ADMIN_EMAIL en el panel de Variables.
    |
    */

    'super_admin_email' => env('SUPER_ADMIN_EMAIL', ''),

    /*
    |--------------------------------------------------------------------------
    | Contacto Email
    |--------------------------------------------------------------------------
    |
    | Correo destino del formulario de contacto (landing page y API).
    | Centralizado aquí para no hardcodear en controladores.
    |
    */

    'contacto_email' => env('CONTACTO_EMAIL', 'academiacentralkaratedosmt@gmail.com'),

    /*
    |--------------------------------------------------------------------------
    | Storage Link Check
    |--------------------------------------------------------------------------
    |
    | Controla si el middleware StorageLinkCheck debe ejecutarse.
    | Poner en false si el proyecto usa Supabase Storage exclusivamente
    | y no necesita el symlink public/storage en absoluto.
    |
    | En Railway: agrega STORAGE_LINK_CHECK=false para desactivarlo.
    |
    */

    'storage_link_check' => env('STORAGE_LINK_CHECK', true),

];