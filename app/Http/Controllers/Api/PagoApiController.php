<?php
// app/Http/Controllers/Api/PagoApiController.php
// Reemplaza tu archivo existente con este.

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// ══════════════════════════════════════════════════════════════════════════════
//  app/Http/Controllers/Api/PagoApiController.php
//  AGREGA estos imports al inicio del archivo (junto a los use existentes)
// ══════════════════════════════════════════════════════════════════════════════

use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

// ══════════════════════════════════════════════════════════════════════════════
//  También agrega en config/services.php — ya lo tienes del paso anterior,
//  solo verifica que esté:
// ══════════════════════════════════════════════════════════════════════════════
/*
'mercadopago' => [
    'access_token' => env('MP_ACCESS_TOKEN'),
    'public_key'   => env('MP_PUBLIC_KEY'),
],
*/

// ══════════════════════════════════════════════════════════════════════════════
//  routes/api.php — ASEGÚRATE de excluir /pagos/procesar de rate limiting
//  agresivo si tienes throttle configurado, ya que el Brick hace la llamada
//  desde el navegador del usuario.
//  El bloque dentro de auth:sanctum ya lo protege.
// ══════════════════════════════════════════════════════════════════════════════

// ══════════════════════════════════════════════════════════════════════════════
//  PRUEBAS SANDBOX — Tarjetas de prueba México
// ══════════════════════════════════════════════════════════════════════════════
/*
  ✅ APROBADA:
     Número: 4509 9535 6623 3704
     CVV:    123
     Fecha:  11/25
     Nombre: APRO

  ❌ RECHAZADA:
     Número: 4000 0000 0000 0002
     CVV:    123
     Fecha:  11/25
     Nombre: OTHE

  ⏳ PENDIENTE:
     Número: 4509 9535 6623 3704
     CVV:    123
     Fecha:  11/25
     Nombre: CONT

  OXXO (ticket):
     El Brick genera un código de barras de prueba automáticamente.

  SPEI:
     El Brick genera una CLABE de prueba automáticamente.
*/
class PagoApiController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────
    //  GET /api/pagos
    //  Admin/sensei: todos | Alumno/tutor: solo los suyos
    // ──────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        try {
            $user  = $request->user();
            $query = DB::table('pago as p')
                ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->select(
                    'p.id_pago', 'p.monto', 'p.motivo_pago', 'p.fecha_pago',
                    'p.referencia_pago', 'p.estado_pago', 'p.id_usuario',
                    'p.id_tipo_pago', 'p.mp_preference_id', 'p.mp_payment_id', 'p.mp_status',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                    'tp.nombre_tipo'
                )
                ->orderBy('p.fecha_pago', 'desc');

            if (in_array($user->rol, ['alumno', 'tutor'])) {
                $query->where('p.id_usuario', $user->id_usuario);
            }

            return response()->json(['success' => true, 'data' => $query->get()]);

        } catch (\Exception $e) {
            Log::error('PagoApi@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener los pagos.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  POST /api/pagos
    //  Registra el pago en BD y devuelve URL de MercadoPago para pagar.
    //  Solo admin y sensei pueden registrar pagos a otros alumnos.
    //  Un alumno puede iniciar su propio pago.
    // ──────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $user = $request->user();

        // Alumnos solo pueden pagar a su propio id
        if (
            in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $request->input('id_alumno') !== (int) $user->id_usuario
        ) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para registrar pagos de otros alumnos.'], 403);
        }

        $validated = $request->validate([
            'id_alumno'      => 'required|exists:usuario,id_usuario',
            'id_tipo_pago'   => 'required|exists:tipo_pago,id_tipo_pago',
            'monto'          => 'required|numeric|min:0',
            'fechaPago'      => 'required|date',
            'estadoPago'     => 'required|string|max:20',
            'motivoPago'     => 'nullable|string|max:100',
            'referenciaPago' => 'nullable|string|max:100',
            // Si viene true, se genera la preferencia de MercadoPago
            'pagar_en_linea' => 'boolean',
        ]);

        try {
            // 1. Insertar en BD con estado inicial
            $id = DB::table('pago')->insertGetId([
                'id_usuario'      => $validated['id_alumno'],
                'id_tipo_pago'    => $validated['id_tipo_pago'],
                'monto'           => $validated['monto'],
                'motivo_pago'     => $validated['motivoPago']    ?? null,
                'fecha_pago'      => $validated['fechaPago'],
                'referencia_pago' => $validated['referenciaPago'] ?? null,
                // Si va a pagar en línea lo dejamos Pendiente hasta confirmar webhook
                'estado_pago'     => ($validated['pagar_en_linea'] ?? false) ? 'Pendiente' : $validated['estadoPago'],
            ]);

            $respuesta = ['success' => true, 'message' => 'Pago registrado.', 'id' => $id];

            // 2. Si solicita pago en línea, crear preferencia en MercadoPago
            if ($validated['pagar_en_linea'] ?? false) {
                // Obtener email del alumno para enviarlo a MP
                $alumno = DB::table('usuario')
                    ->where('id_usuario', $validated['id_alumno'])
                    ->select('nombre', 'apaterno', 'correo')
                    ->first();

                $mpService    = new MercadoPagoService();
                $preferencia  = $mpService->crearPreferencia([
                    'id_pago'       => $id,
                    'monto'         => $validated['monto'],
                    'motivo'        => $validated['motivoPago'] ?? 'Pago Academia',
                    'alumno_email'  => $alumno->correo ?? 'alumno@academia.com',
                    'alumno_nombre' => "{$alumno->nombre} {$alumno->apaterno}",
                ]);

                // Guardar preference_id en BD
                DB::table('pago')->where('id_pago', $id)->update([
                    'mp_preference_id' => $preferencia['id'],
                ]);

                $respuesta['mercadopago'] = [
                    'preference_id'       => $preferencia['id'],
                    // La app móvil abre esta URL en el navegador o webview
                    'init_point'          => $preferencia['init_point'],
                    // En sandbox usa esta URL para pruebas
                    'sandbox_init_point'  => $preferencia['sandbox_init_point'],
                ];
            }

            return response()->json($respuesta, 201);

        } catch (\Exception $e) {
            Log::error('PagoApi@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar el pago.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  POST /api/pagos/webhook   (sin auth:sanctum — MP no envía token)
    //  MercadoPago llama aquí al aprobar / rechazar un pago.
    //  IMPORTANTE: agrega esta ruta FUERA del grupo auth:sanctum en api.php
    // ──────────────────────────────────────────────────────────────────────
    public function webhook(Request $request)
    {
        // MP envía type=payment y data.id con el payment_id
        $type      = $request->input('type');
        $paymentId = $request->input('data.id');

        if ($type !== 'payment' || ! $paymentId) {
            // Responder 200 siempre para que MP no reintente
            return response()->json(['ok' => true]);
        }

        try {
            // Consultar el estado real del pago a la API de MP
            $accessToken = config('services.mercadopago.access_token');
            $response    = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if (! $response->ok()) {
                Log::warning("MP Webhook: no se pudo consultar payment {$paymentId}");
                return response()->json(['ok' => false], 200); // 200 igual para evitar reintentos
            }

            $pago       = $response->json();
            $mpStatus   = $pago['status']             ?? null;  // approved, pending, rejected
            $externalRef = $pago['external_reference'] ?? null; // id_pago interno

            if (! $externalRef) {
                return response()->json(['ok' => true]);
            }

            // Mapear estado de MP al estado interno
            $estadoInterno = match ($mpStatus) {
                'approved'    => 'Completado',
                'pending', 'in_process', 'authorized' => 'Pendiente',
                default       => 'Fallido',
            };

            DB::table('pago')
                ->where('id_pago', (int) $externalRef)
                ->update([
                    'mp_payment_id' => (string) $paymentId,
                    'mp_status'     => $mpStatus,
                    'estado_pago'   => $estadoInterno,
                    // Guardar el id del pago de MP como referencia
                    'referencia_pago' => "MP-{$paymentId}",
                ]);

            Log::info("MP Webhook: pago #{$externalRef} → {$estadoInterno} (MP: {$mpStatus})");

        } catch (\Exception $e) {
            Log::error('MP Webhook error: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  GET /api/pagos/historial/{idUsuario}
    // ──────────────────────────────────────────────────────────────────────
    public function historialAlumno(Request $request, int $id_usuario)
    {
        $user = $request->user();

        if (in_array($user->rol, ['alumno', 'tutor']) && (int) $user->id_usuario !== (int) $id_usuario) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        try {
            $pagos = DB::table('pago as p')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->where('p.id_usuario', $id_usuario)
                ->select(
                    'p.id_pago', 'p.monto', 'p.motivo_pago', 'p.fecha_pago',
                    'p.referencia_pago', 'p.estado_pago', 'p.mp_status',
                    'tp.nombre_tipo'
                )
                ->orderBy('p.fecha_pago', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $pagos]);

        } catch (\Exception $e) {
            Log::error('PagoApi@historialAlumno: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener historial.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  GET /api/tipos-pago
    // ──────────────────────────────────────────────────────────────────────
    public function tiposPago()
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => DB::table('tipo_pago')->orderBy('id_tipo_pago')->get(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

// ══════════════════════════════════════════════════════════════════════════════
//  AGREGA estos dos métodos a tu PagoApiController.php existente
//  (el que generamos en la entrega anterior)
// ══════════════════════════════════════════════════════════════════════════════

// ── NUEVO IMPORT al inicio del archivo (junto a los demás use) ────────────────
// use MercadoPago\Client\Payment\PaymentClient;
// use MercadoPago\MercadoPagoConfig;

// ──────────────────────────────────────────────────────────────────────────────
//  POST /api/pagos/procesar
//  Recibe el formData del Payment Brick y crea el pago en MP vía Checkout API.
//  El Brick ya tokenizó la tarjeta — NUNCA llegan datos de tarjeta en texto plano.
// ──────────────────────────────────────────────────────────────────────────────
public function procesar(Request $request)
{
    $validated = $request->validate([
        'id_pago'  => 'required|integer|exists:pago,id_pago',
        'formData' => 'required|array',
    ]);

    // Obtener el registro de pago para verificar monto y alumno
    $pagoRegistro = DB::table('pago as p')
        ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
        ->where('p.id_pago', $validated['id_pago'])
        ->select('p.*', DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"), 'u.correo')
        ->first();

    if (! $pagoRegistro) {
        return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
    }

    // Solo procesar pagos que estén en Pendiente
    if ($pagoRegistro->estado_pago !== 'Pendiente') {
        return response()->json([
            'success' => false,
            'message' => 'Este pago ya fue procesado anteriormente.',
        ], 422);
    }

    try {
        // Configurar MP con el Access Token
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

        $formData = $validated['formData'];

        // Construir el objeto de pago para la API de MP
        // El Brick ya incluye: token (tarjeta tokenizada), payment_method_id,
        // installments, issuer_id, payer, etc.
        $paymentData = array_merge($formData, [
            // Asegurarse de que el monto sea el correcto de la BD (no del cliente)
            'transaction_amount' => (float) $pagoRegistro->monto,
            'description'        => $pagoRegistro->motivo_pago ?? 'Pago Academia Karate-Do SMT',
            'external_reference' => (string) $pagoRegistro->id_pago,
            // El email del pagador (requerido por MP)
            'payer' => array_merge($formData['payer'] ?? [], [
                'email' => $formData['payer']['email'] ?? $pagoRegistro->correo ?? 'alumno@academia.com',
            ]),
        ]);

        // Llamar a la API de pagos de MercadoPago
        $client  = new PaymentClient();
        $payment = $client->create($paymentData);

        // Mapear estado de MP al estado interno
        $estadoInterno = match ($payment->status) {
            'approved'                        => 'Completado',
            'pending', 'in_process', 'authorized' => 'Pendiente',
            default                           => 'Fallido',
        };

        // Actualizar el registro en BD
        DB::table('pago')->where('id_pago', $pagoRegistro->id_pago)->update([
            'mp_payment_id'   => (string) $payment->id,
            'mp_status'       => $payment->status,
            'estado_pago'     => $estadoInterno,
            'referencia_pago' => "MP-{$payment->id}",
        ]);

        // Respuesta para el Brick
        // El Brick espera: si rechazado → Promise.reject() ya fue manejado
        // Si aprobado/pendiente → el onSubmit resuelve la promesa
        return response()->json([
            'success'              => true,
            'status'               => $payment->status,               // approved|pending|rejected
            'status_detail'        => $payment->status_detail,
            'id'                   => $payment->id,
            'estado_interno'       => $estadoInterno,
        ]);

    } catch (\MercadoPago\Exceptions\MPApiException $e) {
        // Error de la API de MP (tarjeta rechazada, fondos insuficientes, etc.)
        Log::warning('MP procesar - API error: ' . $e->getMessage(), [
            'id_pago'    => $validated['id_pago'],
            'api_response' => $e->getApiResponse()?->getContent(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $this->traducirErrorMP($e->getMessage()),
        ], 422);

    } catch (\Exception $e) {
        Log::error('MP procesar - Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al procesar el pago. Intenta de nuevo.',
        ], 500);
    }
}

// ──────────────────────────────────────────────────────────────────────────────
//  GET /api/pagos/{id}/preference
//  Devuelve (o crea) el preference_id para inicializar el Payment Brick.
//  El Brick necesita el preference_id para mostrar el monto y habilitar MP Wallet.
// ──────────────────────────────────────────────────────────────────────────────
public function getPreference(Request $request, int $idPago)
{
    $user = $request->user();

    $pago = DB::table('pago as p')
        ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
        ->where('p.id_pago', $idPago)
        ->select('p.*', DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"), 'u.correo')
        ->first();

    if (! $pago) {
        return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
    }

    // Alumno solo puede ver su propia preferencia
    if (in_array($user->rol, ['alumno', 'tutor']) && (int) $user->id_usuario !== (int) $pago->id_usuario) {
        return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
    }

    try {
        // Si ya tiene preference_id, reutilizarlo
        $preferenceId = $pago->mp_preference_id;

        if (! $preferenceId) {
            $mpService    = new \App\Services\MercadoPagoService();
            $preferencia  = $mpService->crearPreferencia([
                'id_pago'       => $pago->id_pago,
                'monto'         => $pago->monto,
                'motivo'        => $pago->motivo_pago ?? 'Pago Academia',
                'alumno_email'  => $pago->correo ?? 'alumno@academia.com',
                'alumno_nombre' => $pago->nombre_alumno,
            ]);

            $preferenceId = $preferencia['id'];

            DB::table('pago')->where('id_pago', $idPago)->update([
                'mp_preference_id' => $preferenceId,
            ]);
        }

        return response()->json([
            'success'       => true,
            'preference_id' => $preferenceId,
            'monto'         => $pago->monto,
            'motivo'        => $pago->motivo_pago,
        ]);

    } catch (\Exception $e) {
        Log::error('MP getPreference: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Error al obtener preferencia.'], 500);
    }
}

// ──────────────────────────────────────────────────────────────────────────────
//  Helper privado: traduce mensajes de error de MP al español
// ──────────────────────────────────────────────────────────────────────────────
private function traducirErrorMP(string $mensaje): string
{
    $traducciones = [
        'cc_rejected_insufficient_amount' => 'Fondos insuficientes en la tarjeta.',
        'cc_rejected_bad_filled_card_number' => 'Número de tarjeta incorrecto.',
        'cc_rejected_bad_filled_date'     => 'Fecha de vencimiento incorrecta.',
        'cc_rejected_bad_filled_security_code' => 'Código de seguridad incorrecto.',
        'cc_rejected_blacklist'           => 'Tarjeta no habilitada para este tipo de pago.',
        'cc_rejected_call_for_authorize'  => 'Debes autorizar el pago con tu banco.',
        'cc_rejected_card_disabled'       => 'Tarjeta deshabilitada. Contacta a tu banco.',
        'cc_rejected_duplicated_payment'  => 'Pago duplicado. Ya realizaste este pago.',
        'cc_rejected_high_risk'           => 'Pago rechazado por seguridad.',
        'cc_rejected_max_attempts'        => 'Alcanzaste el límite de intentos. Usa otra tarjeta.',
    ];

    foreach ($traducciones as $key => $texto) {
        if (str_contains($mensaje, $key)) return $texto;
    }

    return 'El pago no pudo procesarse. Verifica los datos e intenta de nuevo.';
}
}