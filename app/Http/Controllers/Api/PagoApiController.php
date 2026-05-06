<?php
// app/Http/Controllers/Api/PagoApiController.php — REEMPLAZA COMPLETO

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class PagoApiController extends Controller
{
    // ──────────────────────────────────────────────────────────────────
    //  GET /api/pagos
    //  Admin/sensei → todos los pagos
    //  Alumno/tutor → solo los suyos
    // ──────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        try {
            $user  = $request->user();
            $query = DB::table('pago as p')
                ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->select(
                    'p.id_pago',
                    'p.monto',
                    'p.monto_total',
                    'p.monto_pagado',
                    'p.motivo_pago',
                    'p.fecha_pago',
                    'p.referencia_pago',
                    'p.estado_pago',
                    'p.id_usuario',
                    'p.id_tipo_pago',
                    'p.mp_preference_id',
                    'p.mp_payment_id',
                    'p.mp_status',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                    'tp.nombre_tipo',
                    DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
                )
                ->orderBy('p.fecha_pago', 'desc');

            // Alumno y tutor solo ven sus propios pagos
            if (in_array($user->rol, ['alumno', 'tutor'])) {
                $query->where('p.id_usuario', $user->id_usuario);
            }

            return response()->json([
                'success' => true,
                'data'    => $query->get(),
            ]);

        } catch (\Exception $e) {
            Log::error('PagoApi@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pagos.',
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /api/pagos
    //  Solo admin y sensei pueden registrar pagos nuevos.
    //  Estado por defecto: Pendiente.
    //  Si estadoPago=Completado y tipo efectivo → abono automático.
    // ──────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->rol, ['admin', 'sensei'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para registrar pagos.',
            ], 403);
        }

        $validated = $request->validate([
            'id_alumno'      => 'required|exists:usuario,id_usuario',
            'id_tipo_pago'   => 'required|exists:tipo_pago,id_tipo_pago',
            'monto'          => 'required|numeric|min:0',
            'fechaPago'      => 'required|date',
            'estadoPago'     => 'required|string|max:20',
            'motivoPago'     => 'nullable|string|max:100',
            'referenciaPago' => 'nullable|string|max:100',
            'pagar_en_linea' => 'boolean',
        ]);

        $pagarEnLinea = (bool) ($validated['pagar_en_linea'] ?? false);

        try {
            $id = DB::table('pago')->insertGetId([
                'id_usuario'      => $validated['id_alumno'],
                'id_tipo_pago'    => $validated['id_tipo_pago'],
                'monto'           => $validated['monto'],
                'monto_total'     => $validated['monto'],
                'monto_pagado'    => 0.00,
                'motivo_pago'     => $validated['motivoPago']    ?? null,
                'fecha_pago'      => $validated['fechaPago'],
                'referencia_pago' => $validated['referenciaPago'] ?? null,
                'estado_pago'     => $pagarEnLinea ? 'Pendiente' : $validated['estadoPago'],
            ]);

            // Si admin registra efectivo como Completado → abono automático
            if (!$pagarEnLinea && $validated['estadoPago'] === 'Completado') {
                DB::table('abono')->insert([
                    'id_pago'        => $id,
                    'id_usuario'     => $validated['id_alumno'],
                    'monto_abono'    => $validated['monto'],
                    'fecha_abono'    => $validated['fechaPago'],
                    'tipo_abono'     => 'efectivo',
                    'referencia'     => $validated['referenciaPago'] ?? null,
                    'registrado_por' => $user->id_usuario,
                ]);

                DB::table('pago')->where('id_pago', $id)->update([
                    'monto_pagado' => $validated['monto'],
                ]);
            }

            $respuesta = [
                'success' => true,
                'message' => 'Pago registrado.',
                'id'      => $id,
            ];

            // Si solicita pago en línea → crear preferencia MP
            if ($pagarEnLinea) {
                $alumno = DB::table('usuario')
                    ->where('id_usuario', $validated['id_alumno'])
                    ->select('nombre', 'apaterno', 'correo')
                    ->first();

                $mpService   = new MercadoPagoService();
                $preferencia = $mpService->crearPreferencia([
                    'id_pago'       => $id,
                    'monto'         => $validated['monto'],
                    'motivo'        => $validated['motivoPago'] ?? 'Pago Academia',
                    'alumno_email'  => $alumno->correo ?? 'alumno@academia.com',
                    'alumno_nombre' => "{$alumno->nombre} {$alumno->apaterno}",
                ]);

                DB::table('pago')->where('id_pago', $id)->update([
                    'mp_preference_id' => $preferencia['id'],
                ]);

                $respuesta['mercadopago'] = [
                    'preference_id'      => $preferencia['id'],
                    'init_point'         => $preferencia['init_point'],
                    'sandbox_init_point' => $preferencia['sandbox_init_point'],
                ];
            }

            return response()->json($respuesta, 201);

        } catch (\Exception $e) {
            Log::error('PagoApi@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago.',
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /api/pagos/{id}/completar
    //  Admin/sensei marcan pago como Completado tras verificar
    // ──────────────────────────────────────────────────────────────────
    public function completar(Request $request, int $id)
    {
        $user = $request->user();

        if (!in_array($user->rol, ['admin', 'sensei'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para completar pagos.',
            ], 403);
        }

        $pago = DB::table('pago')->where('id_pago', $id)->first();

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
        }

        if ($pago->estado_pago === 'Completado') {
            return response()->json([
                'success' => false,
                'message' => 'Este pago ya estaba completado.',
            ], 422);
        }

        $montoTotal = $pago->monto_total ?? $pago->monto;

        DB::table('pago')->where('id_pago', $id)->update([
            'estado_pago'  => 'Completado',
            'monto_pagado' => $montoTotal,
        ]);

        // Registrar abono de verificación si no había ninguno
        $tieneAbonos = DB::table('abono')->where('id_pago', $id)->count();
        if ($tieneAbonos === 0) {
            DB::table('abono')->insert([
                'id_pago'        => $id,
                'id_usuario'     => $pago->id_usuario,
                'monto_abono'    => $montoTotal,
                'fecha_abono'    => now(),
                'tipo_abono'     => $pago->mp_payment_id ? 'en_linea' : 'efectivo',
                'referencia'     => $pago->mp_payment_id ? "MP-{$pago->mp_payment_id}" : 'Verificado por admin',
                'registrado_por' => $user->id_usuario,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago marcado como completado.',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /api/pagos/{id}/abono
    //  Admin/sensei → efectivo o en línea
    //  Alumno/tutor → solo en línea
    // ──────────────────────────────────────────────────────────────────
    public function abono(Request $request, int $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'monto_abono' => 'required|numeric|min:1',
            'tipo_abono'  => 'required|in:efectivo,en_linea',
            'referencia'  => 'nullable|string|max:100',
        ]);

        $pago = DB::table('pago')->where('id_pago', $id)->first();

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
        }

        // Alumno solo puede abonar a sus propios pagos
        if (
            in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $pago->id_usuario
        ) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        // Alumno no puede registrar abono en efectivo
        if (
            in_array($user->rol, ['alumno', 'tutor']) &&
            $validated['tipo_abono'] === 'efectivo'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Solo el administrador puede registrar abonos en efectivo.',
            ], 403);
        }

        $montoTotal  = $pago->monto_total  ?? $pago->monto;
        $montoPagado = $pago->monto_pagado ?? 0;
        $saldo       = $montoTotal - $montoPagado;

        if ($validated['monto_abono'] > $saldo) {
            return response()->json([
                'success' => false,
                'message' => "El abono no puede ser mayor al saldo restante ($" . number_format($saldo, 2) . ").",
            ], 422);
        }

        try {
            // ── Abono en línea ────────────────────────────────────────
            if ($validated['tipo_abono'] === 'en_linea') {
                $alumno = DB::table('usuario')
                    ->where('id_usuario', $pago->id_usuario)
                    ->select('nombre', 'apaterno', 'correo')
                    ->first();

                $idAbono = DB::table('abono')->insertGetId([
                    'id_pago'        => $id,
                    'id_usuario'     => $pago->id_usuario,
                    'monto_abono'    => $validated['monto_abono'],
                    'fecha_abono'    => now(),
                    'tipo_abono'     => 'en_linea',
                    'referencia'     => null,
                    'registrado_por' => $user->id_usuario,
                ]);

                $mpService   = new MercadoPagoService();
                $preferencia = $mpService->crearPreferencia([
                    'id_pago'       => $id,
                    'monto'         => $validated['monto_abono'],
                    'motivo'        => "Abono - " . ($pago->motivo_pago ?? 'Pago Academia'),
                    'alumno_email'  => $alumno->correo ?? 'alumno@academia.com',
                    'alumno_nombre' => "{$alumno->nombre} {$alumno->apaterno}",
                ]);

                DB::table('abono')->where('id_abono', $idAbono)->update([
                    'referencia' => $preferencia['id'],
                ]);

                return response()->json([
                    'success'     => true,
                    'message'     => 'Abono en línea iniciado.',
                    'id_abono'    => $idAbono,
                    'mercadopago' => [
                        'preference_id'      => $preferencia['id'],
                        'init_point'         => $preferencia['init_point'],
                        'sandbox_init_point' => $preferencia['sandbox_init_point'],
                    ],
                ], 201);
            }

            // ── Abono en efectivo (solo admin/sensei) ─────────────────
            $nuevoMontoPagado = $montoPagado + $validated['monto_abono'];
            $nuevoEstado      = $nuevoMontoPagado >= $montoTotal ? 'Completado' : 'Pendiente';

            DB::table('abono')->insert([
                'id_pago'        => $id,
                'id_usuario'     => $pago->id_usuario,
                'monto_abono'    => $validated['monto_abono'],
                'fecha_abono'    => now(),
                'tipo_abono'     => 'efectivo',
                'referencia'     => $validated['referencia'] ?? null,
                'registrado_por' => $user->id_usuario,
            ]);

            DB::table('pago')->where('id_pago', $id)->update([
                'monto_pagado' => $nuevoMontoPagado,
                'estado_pago'  => $nuevoEstado,
            ]);

            return response()->json([
                'success'        => true,
                'message'        => 'Abono registrado correctamente.',
                'nuevo_estado'   => $nuevoEstado,
                'monto_pagado'   => $nuevoMontoPagado,
                'saldo_restante' => $montoTotal - $nuevoMontoPagado,
            ]);

        } catch (\Exception $e) {
            Log::error('PagoApi@abono: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el abono.',
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /api/pagos/historial/{id}
    //  Historial de pagos de un alumno específico.
    //  Admin/sensei → cualquier alumno
    //  Alumno/tutor → solo el suyo
    // ──────────────────────────────────────────────────────────────────
    public function historialAlumno(Request $request, int $idUsuario)
    {
        $user = $request->user();

        if (
            in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== $idUsuario
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver este historial.',
            ], 403);
        }

        try {
            $pagos = DB::table('pago as p')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->where('p.id_usuario', $idUsuario)
                ->select(
                    'p.id_pago',
                    'p.monto',
                    'p.monto_total',
                    'p.monto_pagado',
                    'p.motivo_pago',
                    'p.fecha_pago',
                    'p.referencia_pago',
                    'p.estado_pago',
                    'p.mp_status',
                    'tp.nombre_tipo',
                    DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
                )
                ->orderBy('p.fecha_pago', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $pagos,
            ]);

        } catch (\Exception $e) {
            Log::error('PagoApi@historialAlumno: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial.',
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /api/pagos/{id}/abonos
    //  Lista de abonos de un pago específico.
    //  Admin/sensei → cualquier pago
    //  Alumno/tutor → solo sus propios pagos
    // ──────────────────────────────────────────────────────────────────
    public function listarAbonos(Request $request, int $id)
    {
        $user = $request->user();
        $pago = DB::table('pago')->where('id_pago', $id)->first();

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
        }

        if (
            in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $pago->id_usuario
        ) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        try {
            $abonos = DB::table('abono as a')
                ->leftJoin('usuario as u', 'a.registrado_por', '=', 'u.id_usuario')
                ->where('a.id_pago', $id)
                ->select(
                    'a.id_abono',
                    'a.monto_abono',
                    'a.fecha_abono',
                    'a.tipo_abono',
                    'a.referencia',
                    'a.mp_status',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS registrado_por_nombre")
                )
                ->orderBy('a.fecha_abono', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $abonos,
            ]);

        } catch (\Exception $e) {
            Log::error('PagoApi@listarAbonos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los abonos.',
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /api/pagos/{id}/preference
    //  Devuelve (o crea) el preference_id para inicializar el
    //  Payment Brick en la app móvil.
    // ──────────────────────────────────────────────────────────────────
    public function getPreference(Request $request, int $idPago)
    {
        $user = $request->user();

        $pago = DB::table('pago as p')
            ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
            ->where('p.id_pago', $idPago)
            ->select(
                'p.*',
                DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                'u.correo',
                DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
            )
            ->first();

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
        }

        if (
            in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $pago->id_usuario
        ) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        try {
            $preferenceId = $pago->mp_preference_id;

            if (!$preferenceId) {
                $mpService   = new MercadoPagoService();
                $preferencia = $mpService->crearPreferencia([
                    'id_pago'       => $pago->id_pago,
                    'monto'         => $pago->saldo_restante,
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
                'success'        => true,
                'preference_id'  => $preferenceId,
                'monto'          => $pago->saldo_restante,
                'monto_total'    => $pago->monto_total ?? $pago->monto,
                'monto_pagado'   => $pago->monto_pagado ?? 0,
                'saldo_restante' => $pago->saldo_restante,
                'motivo'         => $pago->motivo_pago,
            ]);

        } catch (\Exception $e) {
            Log::error('PagoApi@getPreference: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la preferencia.',
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /api/pagos/procesar
    //  Recibe el formData del Payment Brick y crea el pago en MP.
    //  El Brick ya tokenizó la tarjeta — nunca llegan datos en texto plano.
    // ──────────────────────────────────────────────────────────────────
    public function procesar(Request $request)
    {
        $validated = $request->validate([
            'id_pago'  => 'required|integer|exists:pago,id_pago',
            'formData' => 'required|array',
        ]);

        $pagoRegistro = DB::table('pago as p')
            ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
            ->where('p.id_pago', $validated['id_pago'])
            ->select(
                'p.*',
                DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                'u.correo',
                DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
            )
            ->first();

        if (!$pagoRegistro) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
        }

        if ($pagoRegistro->estado_pago === 'Completado') {
            return response()->json([
                'success' => false,
                'message' => 'Este pago ya fue procesado anteriormente.',
            ], 422);
        }

        try {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

            $formData    = $validated['formData'];
            $paymentData = array_merge($formData, [
                // Usar el saldo restante, no el monto original completo
                'transaction_amount' => (float) $pagoRegistro->saldo_restante,
                'description'        => $pagoRegistro->motivo_pago ?? 'Pago Academia Karate-Do SMT',
                'external_reference' => (string) $pagoRegistro->id_pago,
                'payer'              => array_merge($formData['payer'] ?? [], [
                    'email' => $formData['payer']['email'] ?? $pagoRegistro->correo ?? 'alumno@academia.com',
                ]),
            ]);

            $client  = new PaymentClient();
            $payment = $client->create($paymentData);

            $estadoInterno = match ($payment->status) {
                'approved'                               => 'Completado',
                'pending', 'in_process', 'authorized'   => 'Pendiente',
                default                                  => 'Fallido',
            };

            // Calcular nuevo monto pagado
            $nuevoMontoPagado = ($pagoRegistro->monto_pagado ?? 0) + (float) $pagoRegistro->saldo_restante;

            DB::table('pago')->where('id_pago', $pagoRegistro->id_pago)->update([
                'mp_payment_id'   => (string) $payment->id,
                'mp_status'       => $payment->status,
                'estado_pago'     => $estadoInterno,
                'referencia_pago' => "MP-{$payment->id}",
                'monto_pagado'    => $estadoInterno === 'Completado' ? $nuevoMontoPagado : ($pagoRegistro->monto_pagado ?? 0),
            ]);

            // Registrar abono si fue aprobado
            if ($estadoInterno === 'Completado') {
                DB::table('abono')->insert([
                    'id_pago'     => $pagoRegistro->id_pago,
                    'id_usuario'  => $pagoRegistro->id_usuario,
                    'monto_abono' => $pagoRegistro->saldo_restante,
                    'fecha_abono' => now(),
                    'tipo_abono'  => 'en_linea',
                    'mp_status'   => $payment->status,
                    'referencia'  => "MP-{$payment->id}",
                ]);
            }

            return response()->json([
                'success'        => true,
                'status'         => $payment->status,
                'status_detail'  => $payment->status_detail,
                'id'             => $payment->id,
                'estado_interno' => $estadoInterno,
            ]);

        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            Log::warning('MP procesar API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $this->traducirErrorMP($e->getMessage()),
            ], 422);

        } catch (\Exception $e) {
            Log::error('MP procesar error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago. Intenta de nuevo.',
            ], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /api/pagos/webhook  (ruta PÚBLICA — fuera de auth:sanctum)
    //  MercadoPago notifica aquí al aprobar/rechazar un pago.
    //  Actualiza estado_pago y registra abono automáticamente.
    // ──────────────────────────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $type      = $request->input('type');
        $paymentId = $request->input('data.id');

        if ($type !== 'payment' || !$paymentId) {
            return response()->json(['ok' => true]);
        }

        try {
            $accessToken = config('services.mercadopago.access_token');
            $response    = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if (!$response->ok()) {
                Log::warning("MP Webhook: no se pudo consultar payment {$paymentId}");
                return response()->json(['ok' => false]);
            }

            $pago        = $response->json();
            $mpStatus    = $pago['status']             ?? null;
            $externalRef = $pago['external_reference'] ?? null;
            $monto       = $pago['transaction_amount'] ?? 0;

            if (!$externalRef) {
                return response()->json(['ok' => true]);
            }

            $estadoInterno = match ($mpStatus) {
                'approved'                             => 'Completado',
                'pending', 'in_process', 'authorized' => 'Pendiente',
                default                               => 'Fallido',
            };

            $pagoRegistro = DB::table('pago')->where('id_pago', (int) $externalRef)->first();

            if ($pagoRegistro) {
                $nuevoMontoPagado = ($pagoRegistro->monto_pagado ?? 0) + $monto;
                $montoTotal       = $pagoRegistro->monto_total ?? $pagoRegistro->monto;
                $estadoFinal      = $nuevoMontoPagado >= $montoTotal ? 'Completado' : $estadoInterno;

                DB::table('pago')
                    ->where('id_pago', (int) $externalRef)
                    ->update([
                        'mp_payment_id'   => (string) $paymentId,
                        'mp_status'       => $mpStatus,
                        'estado_pago'     => $estadoFinal,
                        'referencia_pago' => "MP-{$paymentId}",
                        'monto_pagado'    => $estadoInterno === 'Completado' ? $nuevoMontoPagado : $pagoRegistro->monto_pagado,
                    ]);

                // Registrar abono si fue aprobado y no existe ya
                if ($estadoInterno === 'Completado') {
                    $existeAbono = DB::table('abono')
                        ->where('id_pago', (int) $externalRef)
                        ->where('referencia', "MP-{$paymentId}")
                        ->exists();

                    if (!$existeAbono) {
                        DB::table('abono')->insert([
                            'id_pago'     => (int) $externalRef,
                            'id_usuario'  => $pagoRegistro->id_usuario,
                            'monto_abono' => $monto,
                            'fecha_abono' => now(),
                            'tipo_abono'  => 'en_linea',
                            'mp_status'   => $mpStatus,
                            'referencia'  => "MP-{$paymentId}",
                        ]);
                    }
                }

                Log::info("MP Webhook: pago #{$externalRef} → {$estadoFinal} (MP: {$mpStatus})");
            }

        } catch (\Exception $e) {
            Log::error('MP Webhook error: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /api/tipos-pago
    //  Catálogo de tipos de pago para llenar selects en la app
    // ──────────────────────────────────────────────────────────────────
    public function tiposPago()
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => DB::table('tipo_pago')->orderBy('id_tipo_pago')->get(),
            ]);
        } catch (\Exception $e) {
            Log::error('PagoApi@tiposPago: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  Helper privado: traduce errores de MP al español
    // ──────────────────────────────────────────────────────────────────
    private function traducirErrorMP(string $mensaje): string
    {
        $traducciones = [
            'cc_rejected_insufficient_amount'    => 'Fondos insuficientes en la tarjeta.',
            'cc_rejected_bad_filled_card_number' => 'Número de tarjeta incorrecto.',
            'cc_rejected_bad_filled_date'        => 'Fecha de vencimiento incorrecta.',
            'cc_rejected_bad_filled_security_code' => 'Código de seguridad incorrecto.',
            'cc_rejected_blacklist'              => 'Tarjeta no habilitada para este tipo de pago.',
            'cc_rejected_call_for_authorize'     => 'Debes autorizar el pago con tu banco.',
            'cc_rejected_card_disabled'          => 'Tarjeta deshabilitada. Contacta a tu banco.',
            'cc_rejected_duplicated_payment'     => 'Pago duplicado. Ya realizaste este pago.',
            'cc_rejected_high_risk'              => 'Pago rechazado por seguridad.',
            'cc_rejected_max_attempts'           => 'Alcanzaste el límite de intentos. Usa otra tarjeta.',
        ];

        foreach ($traducciones as $key => $texto) {
            if (str_contains($mensaje, $key)) return $texto;
        }

        return 'El pago no pudo procesarse. Verifica los datos e intenta de nuevo.';
    }
}