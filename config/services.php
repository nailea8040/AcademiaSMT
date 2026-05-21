<?php
// config/services.php — REEMPLAZA COMPLETO

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'supabase' => [
        'url'        => env('SUPABASE_URL'),
        'secret_key' => env('SUPABASE_SECRET_KEY'),
    ],

    'mailgun' => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme'   => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // ── MercadoPago ──────────────────────────────────────────────────────────
    'mercadopago' => [
        // Access Token: úsalo SOLO en el backend (PHP). Nunca lo expongas en JS.
        'access_token' => env('MP_ACCESS_TOKEN'),

        // Public Key: se usa en el frontend (Payment Brick / JS SDK).
        // También se expone en Blade para pasarla al JS — es seguro hacerlo.
        'public_key'   => env('MP_PUBLIC_KEY'),
    ],

];
