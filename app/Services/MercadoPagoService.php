<?php
// app/Services/MercadoPagoService.php

namespace App\Services;

use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use Illuminate\Support\Facades\Log;

class MercadoPagoService
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
        // Forzar entorno sandbox cuando APP_ENV != production
        if (app()->environment('production')) {
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);
        }
    }

    /**
     * Crea una preferencia de pago en MercadoPago.
     *
     * @param  array{
     *   id_pago:      int,
     *   monto:        float,
     *   motivo:       string,
     *   alumno_email: string,
     *   alumno_nombre: string,
     * } $datos
     * @return array{ id: string, init_point: string, sandbox_init_point: string }
     */
    public function crearPreferencia(array $datos): array
    {
        $base = config('app.url');

        $client = new PreferenceClient();

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
            // URLs de retorno para el navegador (web y móvil)
            'back_urls' => [
                'success' => "{$base}/pagos/resultado?estado=success&id_pago={$datos['id_pago']}",
                'failure' => "{$base}/pagos/resultado?estado=failure&id_pago={$datos['id_pago']}",
                'pending' => "{$base}/pagos/resultado?estado=pending&id_pago={$datos['id_pago']}",
            ],
            'auto_return'          => 'approved',
            // Webhook — MP enviará aquí notificaciones POST
            'notification_url'     => "{$base}/api/pagos/webhook",
            // Referencia interna para identificar el pago en el webhook
            'external_reference'   => (string) $datos['id_pago'],
            'statement_descriptor' => 'Academia Karate-Do',
            // Para pruebas locales con ngrok cambia este valor
            'expires'              => false,
        ]);

        return [
            'id'                  => $preferencia->id,
            'init_point'          => $preferencia->init_point,          // producción
            'sandbox_init_point'  => $preferencia->sandbox_init_point,  // sandbox
        ];
    }
}