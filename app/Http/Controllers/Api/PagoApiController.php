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
            $query = DB::table('pagos as p')
                ->leftJoin('usuario as u',    'p.id_usuario',    '=', 'u.id_usuario')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago',  '=', 'tp.id_tipo_pago')
                ->select(
                    'p.id_pago',
                    'p.id_usuario',
                    'p.fecha_pago',
                    'p.monto',
                    'p.estadoPago',
                    'p.motivoPago',
                    'p.referenciaPago',
                    'tp.nombre_tipo',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno,' ',u.amaterno) AS nombre_alumno")
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
            'id_alumno'     => 'required|exists:usuario,id_usuario',
            'id_tipo_pago'  => 'required|exists:tipo_pago,id_tipo_pago',
            'monto'         => 'required|numeric|min:0',
            'fechaPago'     => 'required|date',
            'estadoPago'    => 'required|in:Pendiente,Completado,Cancelado',
            'motivoPago'    => 'nullable|string|max:255',
            'referenciaPago'=> 'nullable|string|max:100',
            'pagar_en_linea'=> 'nullable|boolean',
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

            $id_pago = DB::table('pagos')->insertGetId([
                'id_usuario'     => $validated['id_alumno'],
                'id_tipo_pago'   => $validated['id_tipo_pago'],
                'monto'          => $validated['monto'],
                'fecha_pago'     => $validated['fechaPago'],
                'estadoPago'     => $validated['estadoPago'],
                'motivoPago'     => $validated['motivoPago']     ?? null,
                'referenciaPago' => $validated['referenciaPago'] ?? null,
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

        $pago = DB::table('pagos as p')
            ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
            ->leftJoin('usuario as u',    'p.id_usuario',   '=', 'u.id_usuario')
            ->where('p.id_pago', $id)
            ->select('p.*', 'tp.nombre_tipo', DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"))
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
                    'title'       => $pago->nombre_tipo ?? 'Pago Academia',
                    'description' => $pago->motivoPago  ?? 'Pago de cuota',
                    'quantity'    => 1,
                    'unit_price'  => (float) $pago->monto,
                    'currency_id' => 'MXN',
                ]],
                'payer' => [
                    'name'  => $pago->nombre_alumno ?? 'Alumno',
                    'email' => DB::table('usuario')->where('id_usuario', $pago->id_usuario)->value('correo') ?? '',
                ],
                'back_urls' => [
                    'success' => $baseUrl . '/pagos/resultado?estado=success&pago_id=' . $pago->id_pago,
                    'failure' => $baseUrl . '/pagos/resultado?estado=failure&pago_id=' . $pago->id_pago,
                    'pending' => $baseUrl . '/pagos/resultado?estado=pending&pago_id=' . $pago->id_pago,
                ],
                'auto_return'       => 'approved',
                'external_reference'=> (string) $pago->id_pago,
                'notification_url'  => $baseUrl . '/api/pagos/webhook',
            ];

            $client     = new PreferenceClient();
            $preference = $client->create($preferenceData);

            return response()->json([
                'success'           => true,
                'preference_id'     => $preference->id,
                'sandbox_init_point'=> $preference->sandbox_init_point,
                'init_point'        => $preference->init_point,
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
                        DB::table('pagos')
                            ->where('id_pago', (int) $extRef)
                            ->update(['estadoPago' => 'Completado', 'referenciaPago' => (string) $dataId]);
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
            'token'          => 'required|string',
            'issuer_id'      => 'nullable',
            'payment_method_id' => 'required|string',
            'transaction_amount' => 'required|numeric',
            'installments'   => 'required|integer',
            'payer'          => 'required|array',
            'payer.email'    => 'required|email',
            'id_pago'        => 'required|integer|exists:pagos,id_pago',
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
                DB::table('pagos')
                    ->where('id_pago', $validated['id_pago'])
                    ->update(['estadoPago' => 'Completado', 'referenciaPago' => (string) $payment->id]);
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
            $pagos = DB::table('pagos as p')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->where('p.id_usuario', $idUsuario)
                ->select(
                    'p.id_pago', 'p.fecha_pago', 'p.monto',
                    'p.estadoPago', 'p.motivoPago', 'p.referenciaPago',
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
                    'a.id_abono', 'a.monto', 'a.fecha_abono',
                    'a.metodo', 'a.notas',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS registrado_por")
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
            'monto'  => 'required|numeric|min:0.01',
            'metodo' => 'required|in:efectivo,transferencia,tarjeta',
            'notas'  => 'nullable|string|max:255',
        ]);

        try {
            $pago = DB::table('pagos')->where('id_pago', $id)->first();
            if (!$pago) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
            }

            DB::table('abono')->insert([
                'id_pago'           => $id,
                'monto'             => $validated['monto'],
                'fecha_abono'       => now()->toDateString(),
                'metodo'            => $validated['metodo'],
                'notas'             => $validated['notas'] ?? null,
                'id_registrado_por' => $user->id_usuario,
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
            $affected = DB::table('pagos')
                ->where('id_pago', $id)
                ->update(['estadoPago' => 'Completado']);

            if (!$affected) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
            }

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
    //  NUEVO: GET /api/pagos/alumno/{id_alumno}
    //  Pagos de un alumno específico — para el perfil del tutor en la app
    //
    //  Acceso:
    //   - admin / sensei: siempre
    //   - tutor: solo si el alumno está en su tabla tutor_alumno
    // ─────────────────────────────────────────────────────────────────────────
    public function pagosAlumnoTutor(int $id_alumno)
    {
        $user = Auth::user();

        // Verificar que el alumno existe y tiene rol correcto
        $alumno = DB::table('usuario')
            ->where('id_usuario', $id_alumno)
            ->where('rol', 'alumno')
            ->select('id_usuario', 'nombre', 'apaterno', 'amaterno', 'correo')
            ->first();

        if (!$alumno) {
            return response()->json(['success' => false, 'message' => 'Alumno no encontrado.'], 404);
        }

        // Si es tutor, verificar relación
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
            $pagos = DB::table('pagos as p')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->where('p.id_usuario', $id_alumno)
                ->select(
                    'p.id_pago',
                    'p.fecha_pago',
                    'p.monto',
                    'p.estadoPago',
                    'p.motivoPago',
                    'p.referenciaPago',
                    'tp.nombre_tipo'
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