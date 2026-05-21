<?php
// app/Services/MercadoPagoService.php — REEMPLAZA COMPLETO

namespace App\Services;

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    public function __construct()
    {
        $token = config('services.mercadopago.access_token');

        if (empty($token)) {
            throw new \RuntimeException('MP_ACCESS_TOKEN no está configurado en .env');
        }

        MercadoPagoConfig::setAccessToken($token);

        // En producción no se llama setRuntimeEnviroment → MP usa producción por defecto.
        // En sandbox (token TEST-) no hace falta forzar nada; el token define el entorno.
    }

    /**
     * Crea una preferencia de pago en MercadoPago.
     *
     * @param  array{
     *   id_pago:       int,
     *   monto:         float,
     *   motivo:        string,
     *   alumno_email:  string,
     *   alumno_nombre: string,
     * } $datos
     * @return array{ id: string, init_point: string, sandbox_init_point: string }
     */
    public function crearPreferencia(array $datos): array
    {
        $base = rtrim((string) config('app.url', 'http://localhost'), '/');
        $client     = new PreferenceClient();
        $preferencia = $client->create([
            'items' => [
                [
                    'id'          => (string) $datos['id_pago'],
                    'title'       => $datos['motivo'] ?: 'Pago Academia Karate-Do SMT',
                    'quantity'    => 1,
                    'unit_price'  => (float) $datos['monto'],
                    'currency_id' => 'MXN',
                ],
            ],
            'payer' => [
                'email' => $datos['alumno_email'],
                'name'  => $datos['alumno_nombre'],
            ],
            // URLs de retorno después de que MP redirige al navegador
            'back_urls' => [
                'success' => "{$base}/pagos/resultado?estado=success&id_pago={$datos['id_pago']}",
                'failure' => "{$base}/pagos/resultado?estado=failure&id_pago={$datos['id_pago']}",
                'pending' => "{$base}/pagos/resultado?estado=pending&id_pago={$datos['id_pago']}",
            ],
            'auto_return'          => 'approved',
            // Webhook — MP notifica aquí cuando el pago cambia de estado
            'notification_url'     => "{$base}/api/pagos/webhook",
            'external_reference'   => (string) $datos['id_pago'],
            'statement_descriptor' => 'Academia Karate-Do',
            'expires'              => false,
        ]);

        Log::info("MP preferencia creada: {$preferencia->id} para pago #{$datos['id_pago']}");

        return [
            'id'                 => $preferencia->id,
            'init_point'         => $preferencia->init_point,         // producción
            'sandbox_init_point' => $preferencia->sandbox_init_point, // sandbox
        ];
    }
}
