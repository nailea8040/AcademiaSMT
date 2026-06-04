<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;

class PagoApiController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  GET /api/pagos
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $user = Auth::user();

        try {
            // ✅ FIX: tabla 'pago' (sin s) + columnas snake_case correctas
            $query = DB::table('pago as p')
                ->leftJoin('usuario as u',    'p.id_usuario',   '=', 'u.id_usuario')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->leftJoin('concepto_pago as cp', 'p.id_concepto', '=', 'cp.id_concepto')
                ->select(
                    'p.id_pago',
                    'p.id_usuario',
                    'p.fecha_pago',
                    'p.monto',
                    'p.monto_total',
                    'p.monto_pagado',
                    'p.estado_pago',
                    'p.motivo_pago',
                    'p.referencia_pago',
                    'p.mp_preference_id',
                    'p.mp_payment_id',
                    'p.mp_status',
                    'p.id_concepto',
                    'p.id_tipo_pago',
                    'tp.nombre_tipo',
                    'cp.nombre AS nombre_concepto',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno,' ',u.amaterno) AS nombre_alumno"),
                    DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
                )
                ->orderBy('p.fecha_pago', 'desc');

            // Alumno/tutor solo ve sus propios pagos
            if (in_array($user->rol, ['alumno', 'tutor'])) {
                $query->where('p.id_usuario', $user->id_usuario);
            }

            $pagos = $query->get();

            return response()->json(['success' => true, 'data' => $pagos]);

        } catch (\Exception $e) {
            Log::error('PagoApi@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener pagos.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /api/pagos
    // ─────────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'id_alumno'      => 'required|exists:usuario,id_usuario',
            'id_tipo_pago'   => 'required|exists:tipo_pago,id_tipo_pago',
            'id_concepto'    => 'nullable|exists:concepto_pago,id_concepto',
            'monto'          => 'required|numeric|min:0',
            'fechaPago'      => 'required|date',
            'estadoPago'     => 'required|in:Pendiente,Completado,Cancelado',
            'motivoPago'     => 'nullable|string|max:255',
            'referenciaPago' => 'nullable|string|max:100',
            'pagar_en_linea' => 'nullable|boolean',
        ]);

        // Solo admin/sensei pueden registrar pagos de otros
        if (!in_array($user->rol, ['admin', 'sensei'])) {
            if ($validated['id_alumno'] != $user->id_usuario) {
                return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
            }
        }

        // Verificar que el destinatario sea alumno
        $destinatario = DB::table('usuario')
            ->where('id_usuario', $validated['id_alumno'])
            ->where('rol', 'alumno')
            ->first();

        if (!$destinatario) {
            return response()->json([
                'success' => false,
                'message' => 'El destinatario debe ser un alumno activo.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // ✅ FIX: tabla 'pago' (sin s) + columnas snake_case
            $id_pago = DB::table('pago')->insertGetId([
                'id_usuario'     => $validated['id_alumno'],
                'id_tipo_pago'   => $validated['id_tipo_pago'],
                'id_concepto'    => $validated['id_concepto'] ?? null,
                'monto'          => $validated['monto'],
                'monto_total'    => $validated['monto'],
                'monto_pagado'   => 0,
                'fecha_pago'     => $validated['fechaPago'],
                'estado_pago'    => $validated['estadoPago'],
                'motivo_pago'    => $validated['motivoPago']     ?? null,
                'referencia_pago'=> $validated['referenciaPago'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado.',
                'id_pago' => $id_pago,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PagoApi@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /api/pagos/{id}/preference  — genera link MercadoPago
    // ─────────────────────────────────────────────────────────────────────────
    public function getPreference(int $id)
    {
        $user = Auth::user();

        // ✅ FIX: tabla 'pago' (sin s) + columnas snake_case
        $pago = DB::table('pago as p')
            ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
            ->leftJoin('usuario as u',    'p.id_usuario',   '=', 'u.id_usuario')
            ->where('p.id_pago', $id)
            ->select(
                'p.*',
                'tp.nombre_tipo',
                DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno")
            )
            ->first();

        if (!$pago) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
        }

        if (in_array($user->rol, ['alumno', 'tutor']) && $pago->id_usuario != $user->id_usuario) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        try {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
            MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);

            $baseUrl = config('app.url');

            $preferenceData = [
                'items' => [[
                    'id'          => (string) $pago->id_pago,
                    'title'       => $pago->nombre_tipo  ?? 'Pago Academia',
                    'description' => $pago->motivo_pago  ?? 'Pago de cuota',
                    'quantity'    => 1,
                    'unit_price'  => (float) ($pago->monto_total ?? $pago->monto),
                    'currency_id' => 'MXN',
                ]],
                'payer' => [
                    'name'  => $pago->nombre_alumno ?? 'Alumno',
                    'email' => DB::table('usuario')->where('id_usuario', $pago->id_usuario)->value('correo') ?? '',
                ],
                'back_urls' => [
                    'success' => $baseUrl . '/pagos/resultado?estado=success&pago_id=' . $pago->id_pago,
                    'failure' => $baseUrl . '/pagos/resultado?estado=failure&pago_id=' . $pago->id_pago,
                    'pending' => $baseUrl . '/pagos/resultado?estado=pending&pago_id='  . $pago->id_pago,
                ],
                'auto_return'        => 'approved',
                'external_reference' => (string) $pago->id_pago,
                'notification_url'   => $baseUrl . '/api/pagos/webhook',
            ];

            $client     = new PreferenceClient();
            $preference = $client->create($preferenceData);

            return response()->json([
                'success'            => true,
                'preference_id'      => $preference->id,
                'sandbox_init_point' => $preference->sandbox_init_point,
                'init_point'         => $preference->init_point,
            ]);

        } catch (MPApiException $e) {
            Log::error('PagoApi@getPreference MP: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error con MercadoPago: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('PagoApi@getPreference: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al generar preferencia.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /api/pagos/webhook  — notificación MercadoPago
    // ─────────────────────────────────────────────────────────────────────────
    public function webhook(Request $request)
    {
        try {
            $type   = $request->input('type') ?? $request->input('topic');
            $dataId = $request->input('data.id') ?? $request->input('id');

            Log::info('MP Webhook: type=' . $type . ' id=' . $dataId);

            if ($type === 'payment' && $dataId) {
                MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

                $paymentClient = new \MercadoPago\Client\Payment\PaymentClient();
                $payment       = $paymentClient->get((int) $dataId);

                if ($payment && $payment->status === 'approved') {
                    $extRef = $payment->external_reference;
                    if ($extRef) {
                        // ✅ FIX: tabla 'pago' (sin s) + columnas snake_case
                        DB::table('pago')
                            ->where('id_pago', (int) $extRef)
                            ->update([
                                'estado_pago'    => 'Completado',
                                'referencia_pago'=> (string) $dataId,
                                'mp_payment_id'  => (string) $dataId,
                                'mp_status'      => 'approved',
                            ]);
                        Log::info('Pago ' . $extRef . ' marcado como Completado vía webhook.');
                    }
                }
            }

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('PagoApi@webhook: ' . $e->getMessage());
            return response()->json(['success' => false], 200); // 200 para que MP no reintente
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /api/pagos/procesar  — Payment Brick
    // ─────────────────────────────────────────────────────────────────────────
    public function procesar(Request $request)
    {
        $validated = $request->validate([
            'token'              => 'required|string',
            'issuer_id'          => 'nullable',
            'payment_method_id'  => 'required|string',
            'transaction_amount' => 'required|numeric',
            'installments'       => 'required|integer',
            'payer'              => 'required|array',
            'payer.email'        => 'required|email',
            // ✅ FIX: validar contra tabla 'pago' (sin s)
            'id_pago'            => 'required|integer|exists:pago,id_pago',
        ]);

        try {
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));

            $paymentClient = new \MercadoPago\Client\Payment\PaymentClient();
            $payment       = $paymentClient->create([
                'token'              => $validated['token'],
                'issuer_id'          => $validated['issuer_id'] ?? null,
                'payment_method_id'  => $validated['payment_method_id'],
                'transaction_amount' => (float) $validated['transaction_amount'],
                'installments'       => (int) $validated['installments'],
                'payer'              => $validated['payer'],
                'external_reference' => (string) $validated['id_pago'],
            ]);

            if ($payment->status === 'approved') {
                // ✅ FIX: tabla 'pago' (sin s) + columnas snake_case
                DB::table('pago')
                    ->where('id_pago', $validated['id_pago'])
                    ->update([
                        'estado_pago'    => 'Completado',
                        'referencia_pago'=> (string) $payment->id,
                        'mp_payment_id'  => (string) $payment->id,
                        'mp_status'      => 'approved',
                    ]);
            }

            return response()->json([
                'success' => true,
                'status'  => $payment->status,
                'detail'  => $payment->status_detail,
            ]);

        } catch (MPApiException $e) {
            Log::error('PagoApi@procesar MP: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error('PagoApi@procesar: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al procesar pago.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /api/pagos/historial/{idUsuario}
    // ─────────────────────────────────────────────────────────────────────────
    public function historialAlumno(int $idUsuario)
    {
        $user = Auth::user();

        if (in_array($user->rol, ['alumno', 'tutor']) && $idUsuario !== $user->id_usuario) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        try {
            // ✅ FIX: tabla 'pago' (sin s) + columnas snake_case
            $pagos = DB::table('pago as p')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->where('p.id_usuario', $idUsuario)
                ->select(
                    'p.id_pago',
                    'p.fecha_pago',
                    'p.monto',
                    'p.monto_total',
                    'p.monto_pagado',
                    'p.estado_pago',
                    'p.motivo_pago',
                    'p.referencia_pago',
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

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /api/pagos/{id}/abonos
    // ─────────────────────────────────────────────────────────────────────────
    public function listarAbonos(int $id)
    {
        try {
            $abonos = DB::table('abono as a')
                ->leftJoin('usuario as u', 'a.id_registrado_por', '=', 'u.id_usuario')
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

            return response()->json(['success' => true, 'data' => $abonos]);

        } catch (\Exception $e) {
            Log::error('PagoApi@listarAbonos: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener abonos.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /api/pagos/{id}/abono
    // ─────────────────────────────────────────────────────────────────────────
    public function abono(Request $request, int $id)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'monto_abono' => 'required|numeric|min:0.01',
            'tipo_abono'  => 'required|in:efectivo,en_linea',
            'referencia'  => 'nullable|string|max:255',
        ]);

        try {
            // ✅ FIX: tabla 'pago' (sin s)
            $pago = DB::table('pago')->where('id_pago', $id)->first();
            if (!$pago) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
            }

            DB::table('abono')->insert([
                'id_pago'           => $id,
                'monto_abono'       => $validated['monto_abono'],
                'fecha_abono'       => now()->toDateString(),
                'tipo_abono'        => $validated['tipo_abono'],
                'referencia'        => $validated['referencia'] ?? null,
                'id_registrado_por' => $user->id_usuario,
            ]);

            // Recalcular monto_pagado sumando todos los abonos
            $totalAbonado = DB::table('abono')
                ->where('id_pago', $id)
                ->sum('monto_abono');

            $montoTotal = $pago->monto_total ?? $pago->monto;

            // ✅ FIX: tabla 'pago' (sin s) + columnas snake_case
            DB::table('pago')->where('id_pago', $id)->update([
                'monto_pagado' => $totalAbonado,
                'estado_pago'  => ($totalAbonado >= $montoTotal) ? 'Completado' : $pago->estado_pago,
            ]);

            return response()->json(['success' => true, 'message' => 'Abono registrado.']);

        } catch (\Exception $e) {
            Log::error('PagoApi@abono: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar abono.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /api/pagos/{id}/completar
    // ─────────────────────────────────────────────────────────────────────────
    public function completar(int $id)
    {
        $user = Auth::user();

        if (!in_array($user->rol, ['admin', 'sensei'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        try {
            // ✅ FIX: tabla 'pago' (sin s) + columna snake_case
            $pago = DB::table('pago')->where('id_pago', $id)->first();
            if (!$pago) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
            }

            DB::table('pago')->where('id_pago', $id)->update([
                'estado_pago'  => 'Completado',
                'monto_pagado' => $pago->monto_total ?? $pago->monto,
            ]);

            return response()->json(['success' => true, 'message' => 'Pago marcado como Completado.']);

        } catch (\Exception $e) {
            Log::error('PagoApi@completar: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al completar.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /api/tipos-pago
    // ─────────────────────────────────────────────────────────────────────────
    public function tiposPago()
    {
        try {
            $tipos = DB::table('tipo_pago')->orderBy('id_tipo_pago')->get();
            return response()->json(['success' => true, 'data' => $tipos]);
        } catch (\Exception $e) {
            Log::error('PagoApi@tiposPago: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /api/conceptos-pago
    // ─────────────────────────────────────────────────────────────────────────
    public function conceptosPago()
    {
        try {
            $conceptos = DB::table('concepto_pago')
                ->where('activo', 1)
                ->orderBy('nombre')
                ->get();
            return response()->json(['success' => true, 'data' => $conceptos]);
        } catch (\Exception $e) {
            Log::error('PagoApi@conceptosPago: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POST /api/conceptos-pago
    // ─────────────────────────────────────────────────────────────────────────
    public function storeConcepto(Request $request)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|max:100|unique:concepto_pago,nombre',
            'descripcion'    => 'nullable|string|max:255',
            'monto_sugerido' => 'nullable|numeric|min:0',
        ]);

        try {
            $id = DB::table('concepto_pago')->insertGetId([
                'nombre'         => $validated['nombre'],
                'descripcion'    => $validated['descripcion']    ?? null,
                'monto_sugerido' => $validated['monto_sugerido'] ?? null,
                'activo'         => 1,
            ]);
            return response()->json(['success' => true, 'id_concepto' => $id, 'message' => 'Concepto creado.'], 201);
        } catch (\Exception $e) {
            Log::error('PagoApi@storeConcepto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al crear concepto.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PUT /api/conceptos-pago/{id}
    // ─────────────────────────────────────────────────────────────────────────
    public function updateConcepto(Request $request, int $id)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|max:100|unique:concepto_pago,nombre,' . $id . ',id_concepto',
            'descripcion'    => 'nullable|string|max:255',
            'monto_sugerido' => 'nullable|numeric|min:0',
            'activo'         => 'nullable|boolean',
        ]);

        try {
            DB::table('concepto_pago')->where('id_concepto', $id)->update([
                'nombre'         => $validated['nombre'],
                'descripcion'    => $validated['descripcion']    ?? null,
                'monto_sugerido' => $validated['monto_sugerido'] ?? null,
                'activo'         => $validated['activo']         ?? 1,
            ]);
            return response()->json(['success' => true, 'message' => 'Concepto actualizado.']);
        } catch (\Exception $e) {
            Log::error('PagoApi@updateConcepto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar.'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  GET /api/pagos/alumno/{id_alumno}
    //  Pagos de un alumno específico — para el perfil del tutor en la app
    // ─────────────────────────────────────────────────────────────────────────
    public function pagosAlumnoTutor(int $id_alumno)
    {
        $user = Auth::user();

        $alumno = DB::table('usuario')
            ->where('id_usuario', $id_alumno)
            ->where('rol', 'alumno')
            ->select('id_usuario', 'nombre', 'apaterno', 'amaterno', 'correo')
            ->first();

        if (!$alumno) {
            return response()->json(['success' => false, 'message' => 'Alumno no encontrado.'], 404);
        }

        if ($user->rol === 'tutor') {
            $relacionado = DB::table('tutor_alumno')
                ->where('id_tutor',  $user->id_usuario)
                ->where('id_alumno', $id_alumno)
                ->exists();

            if (!$relacionado) {
                return response()->json(['success' => false, 'message' => 'No tienes permiso para ver estos pagos.'], 403);
            }
        } elseif (!in_array($user->rol, ['admin', 'sensei'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        try {
            // ✅ FIX: tabla 'pago' (sin s) + columnas snake_case
            $pagos = DB::table('pago as p')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->leftJoin('concepto_pago as cp', 'p.id_concepto', '=', 'cp.id_concepto')
                ->where('p.id_usuario', $id_alumno)
                ->select(
                    'p.id_pago',
                    'p.fecha_pago',
                    'p.monto',
                    'p.monto_total',
                    'p.monto_pagado',
                    'p.estado_pago',
                    'p.motivo_pago',
                    'p.referencia_pago',
                    'tp.nombre_tipo',
                    'cp.nombre AS nombre_concepto'
                )
                ->orderBy('p.fecha_pago', 'desc')
                ->get();

            $tipos_pago = DB::table('tipo_pago')->orderBy('id_tipo_pago')->get();
            $conceptos  = DB::table('concepto_pago')->where('activo', 1)->orderBy('nombre')->get();

            return response()->json([
                'success'    => true,
                'alumno'     => $alumno,
                'pagos'      => $pagos,
                'tipos_pago' => $tipos_pago,
                'conceptos'  => $conceptos,
            ]);

        } catch (\Exception $e) {
            Log::error('PagoApi@pagosAlumnoTutor: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener pagos.'], 500);
        }
    }
}