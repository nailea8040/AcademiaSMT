<?php
// app/Services/MercadoPagoService.php — REEMPLAZA COMPLETO
//
// back_url inteligente:
//   • Si la llamada llega por la API (header Authorization: Bearer …)
//     → back_url usa el deep link de la app móvil  (APP_SCHEME://pagos/resultado)
//   • Si la llamada llega por la web (sesión Laravel)
//     → back_url usa la ruta web  (/pagos/resultado)
//
// El método crearPreferencia acepta un parámetro opcional $origenMovil (bool).
// PagoApiController lo llama con true; PagoController (web) con false (default).

namespace App\Services;

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(
            (string) config('services.mercadopago.access_token', '')
        );
    }

    /**
     * Crea una preferencia de pago en MercadoPago.
     *
     * @param array $datos {
     *   id_pago       int     — ID del registro en tabla `pago`
     *   monto         float   — Monto a cobrar
     *   motivo        string  — Descripción que verá el pagador
     *   alumno_email  string  — Email del pagador
     *   alumno_nombre string  — Nombre del pagador
     * }
     * @param bool $origenMovil  true → deep link; false → URL web (default)
     *
     * @return array {
     *   id                  string — preference_id de MercadoPago
     *   init_point          string — URL de producción
     *   sandbox_init_point  string — URL de sandbox/test
     * }
     */
    public function crearPreferencia(array $datos, bool $origenMovil = false): array
    {
        $idPago = $datos['id_pago'];

        // ── Back URLs ─────────────────────────────────────────────────
        if ($origenMovil) {
            // Deep link → la app intercepta el esquema y navega internamente.
            // Formato: SCHEME://pagos/resultado?status=STATUS&id_pago=ID
            $scheme = config('app.mobile_scheme', env('APP_MOBILE_SCHEME', 'miapp'));

            $backUrls = [
                'success' => "{$scheme}://pagos/resultado?status=success&id_pago={$idPago}",
                'failure' => "{$scheme}://pagos/resultado?status=failure&id_pago={$idPago}",
                'pending' => "{$scheme}://pagos/resultado?status=pending&id_pago={$idPago}",
            ];
            // auto_return no se usa en móvil (requiere redirect web)
            $autoReturn = null;
        } else {
            // Rutas web de Laravel
            $base = rtrim((string) config('app.url', ''), '/');

            $backUrls = [
                'success' => "{$base}/pagos/resultado?status=success&id_pago={$idPago}",
                'failure' => "{$base}/pagos/resultado?status=failure&id_pago={$idPago}",
                'pending' => "{$base}/pagos/resultado?status=pending&id_pago={$idPago}",
            ];
            $autoReturn = 'approved';
        }

        // ── Payload de la preferencia ──────────────────────────────────
        $payload = [
            'items' => [
                [
                    'id'          => (string) $idPago,
                    'title'       => $datos['motivo'] ?? 'Pago Academia',
                    'quantity'    => 1,
                    'unit_price'  => (float) $datos['monto'],
                    'currency_id' => 'MXN',
                ],
            ],
            'payer' => [
                'name'  => $datos['alumno_nombre'] ?? '',
                'email' => $datos['alumno_email']  ?? 'alumno@academia.com',
            ],
            'back_urls'        => $backUrls,
            'external_reference' => (string) $idPago,
            'statement_descriptor' => 'Academia Karate-Do SMT',
        ];

        // auto_return solo para web
        if ($autoReturn) {
            $payload['auto_return'] = $autoReturn;
        }

        // Webhook de notificaciones (opcional — descomenta si tienes HTTPS público)
        // $payload['notification_url'] = rtrim(config('app.url'), '/') . '/api/pagos/webhook';

        $client     = new PreferenceClient();
        $preferencia = $client->create($payload);

        Log::info("MP preferencia creada: #{$idPago} | origen: " . ($origenMovil ? 'móvil' : 'web'));

        return [
            'id'                 => $preferencia->id,
            'init_point'         => $preferencia->init_point,
            'sandbox_init_point' => $preferencia->sandbox_init_point,
        ];
    }
}