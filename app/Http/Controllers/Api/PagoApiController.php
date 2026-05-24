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
                ->leftJoin('concepto_pago as cp', 'p.id_concepto', '=', 'cp.id_concepto')
                ->select(
                    'p.id_pago', 'p.monto', 'p.monto_total', 'p.monto_pagado',
                    'p.motivo_pago', 'p.fecha_pago', 'p.referencia_pago',
                    'p.estado_pago', 'p.id_usuario', 'p.id_tipo_pago', 'p.id_concepto',
                    'p.mp_preference_id', 'p.mp_payment_id', 'p.mp_status',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                    'tp.nombre_tipo',
                    'cp.nombre AS nombre_concepto',
                    DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
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

    // ──────────────────────────────────────────────────────────────────
    //  POST /api/pagos
    //  Admin/sensei: registran cargos para cualquier alumno/tutor.
    //  Alumno/tutor: registran su propio pago eligiendo concepto del
    //                catálogo y ajustando el monto.
    //                Estado siempre Pendiente hasta que admin confirme.
    // ──────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $user          = $request->user();
        $esAdminSensei = in_array($user->rol, ['admin', 'sensei']);
        $esAlumnoTutor = in_array($user->rol, ['alumno', 'tutor']);

        // Reglas comunes
        $rules = [
            'id_concepto'    => 'required|exists:concepto_pago,id_concepto',
            'id_tipo_pago'   => 'required|exists:tipo_pago,id_tipo_pago',
            'monto'          => 'required|numeric|min:1',
            'fechaPago'      => 'required|date',
            'motivoPago'     => 'nullable|string|max:100',
            'pagar_en_linea' => 'boolean',
        ];

        // Reglas exclusivas de admin/sensei
        if ($esAdminSensei) {
            $rules['id_alumno']      = 'required|exists:usuario,id_usuario';
            $rules['estadoPago']     = 'required|in:Pendiente,Completado';
            $rules['referenciaPago'] = 'nullable|string|max:100';
        }

        $validated = $request->validate($rules);

        // Destinatario: admin elige, alumno es él mismo
        $idDestinatario = $esAdminSensei
            ? $validated['id_alumno']
            : $user->id_usuario;

        $pagarEnLinea = (bool) ($validated['pagar_en_linea'] ?? false);

        // Estado inicial
        $estadoFinal = 'Pendiente';
        if ($esAdminSensei && !$pagarEnLinea) {
            $estadoFinal = $validated['estadoPago'];
        }

        // Autocompletar motivo desde el concepto
        $concepto = DB::table('concepto_pago')->where('id_concepto', $validated['id_concepto'])->first();
        $motivo   = $validated['motivoPago'] ?? null;
        if (!$motivo && $concepto) {
            $motivo = $concepto->nombre;
        }

        try {
            $id = DB::table('pago')->insertGetId([
                'id_usuario'      => $idDestinatario,
                'id_tipo_pago'    => $validated['id_tipo_pago'],
                'id_concepto'     => $validated['id_concepto'],
                'monto'           => $validated['monto'],
                'monto_total'     => $validated['monto'],
                'monto_pagado'    => 0.00,
                'motivo_pago'     => $motivo,
                'fecha_pago'      => $validated['fechaPago'],
                'referencia_pago' => $validated['referenciaPago'] ?? null,
                'estado_pago'     => $estadoFinal,
            ]);

            // Admin registra efectivo como Completado → abono automático
            if ($esAdminSensei && !$pagarEnLinea && $estadoFinal === 'Completado') {
                DB::table('abono')->insert([
                    'id_pago'        => $id,
                    'id_usuario'     => $idDestinatario,
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
                'message' => $esAlumnoTutor && $estadoFinal === 'Pendiente' && !$pagarEnLinea
                    ? 'Pago registrado. Quedará pendiente hasta que el administrador lo confirme.'
                    : 'Pago registrado.',
                'id'      => $id,
            ];

            // Pago en línea → crear preferencia MP
            if ($pagarEnLinea) {
                $alumno = DB::table('usuario')
                    ->where('id_usuario', $idDestinatario)
                    ->select('nombre', 'apaterno', 'correo')
                    ->first();

                $mpService   = new MercadoPagoService();
                $preferencia = $mpService->crearPreferencia([
                    'id_pago'       => $id,
                    'monto'         => $validated['monto'],
                    'motivo'        => $motivo ?? 'Pago Academia',
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
            return response()->json(['success' => false, 'message' => 'Error al registrar el pago.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /api/pagos/{id}/completar  — solo admin/sensei
    // ──────────────────────────────────────────────────────────────────
    public function completar(Request $request, int $id)
    {
        $user = $request->user();
        if (!in_array($user->rol, ['admin', 'sensei'])) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso.'], 403);
        }

        $pago = DB::table('pago')->where('id_pago', $id)->first();
        if (!$pago) return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);

        if ($pago->estado_pago === 'Completado') {
            return response()->json(['success' => false, 'message' => 'Este pago ya estaba completado.'], 422);
        }

        $montoTotal = $pago->monto_total ?? $pago->monto;

        DB::table('pago')->where('id_pago', $id)->update([
            'estado_pago'  => 'Completado',
            'monto_pagado' => $montoTotal,
        ]);

        $tieneAbonos = DB::table('abono')->where('id_pago', $id)->count();
        if ($tieneAbonos === 0) {
            DB::table('abono')->insert([
                'id_pago'        => $id,
                'id_usuario'     => $pago->id_usuario,
                'monto_abono'    => $montoTotal,
                'fecha_abono'    => now(),
                'tipo_abono'     => $pago->mp_payment_id ? 'en_linea' : 'efectivo',
                'referencia'     => $pago->mp_payment_id
                    ? "MP-{$pago->mp_payment_id}"
                    : 'Verificado por admin',
                'registrado_por' => $user->id_usuario,
            ]);
        } else {
            $totalAbonado = DB::table('abono')->where('id_pago', $id)->sum('monto_abono');
            $diferencia   = $montoTotal - $totalAbonado;
            if ($diferencia > 0) {
                DB::table('abono')->insert([
                    'id_pago'        => $id,
                    'id_usuario'     => $pago->id_usuario,
                    'monto_abono'    => $diferencia,
                    'fecha_abono'    => now(),
                    'tipo_abono'     => 'efectivo',
                    'referencia'     => 'Completado por admin',
                    'registrado_por' => $user->id_usuario,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Pago marcado como completado.']);
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /api/pagos/{id}/abono
    //  Admin/sensei → efectivo (aplica inmediato) o en línea (MP)
    //  Alumno/tutor → efectivo (Pendiente hasta confirmación) o en línea (MP)
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
        if (!$pago) return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);

        if (in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $pago->id_usuario) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
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
            // Abono en línea → crear preferencia MP
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

            // Abono en efectivo
            $esAdminSensei = in_array($user->rol, ['admin', 'sensei']);

            DB::table('abono')->insert([
                'id_pago'        => $id,
                'id_usuario'     => $pago->id_usuario,
                'monto_abono'    => $validated['monto_abono'],
                'fecha_abono'    => now(),
                'tipo_abono'     => 'efectivo',
                'referencia'     => $validated['referencia'] ?? null,
                'registrado_por' => $user->id_usuario,
            ]);

            if ($esAdminSensei) {
                $nuevoMontoPagado = $montoPagado + $validated['monto_abono'];
                $nuevoEstado      = $nuevoMontoPagado >= $montoTotal ? 'Completado' : 'Pendiente';
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
            } else {
                // Alumno: queda Pendiente hasta confirmación de admin
                return response()->json([
                    'success' => true,
                    'message' => 'Abono registrado. Quedará pendiente hasta que el administrador lo confirme.',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('PagoApi@abono: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar el abono.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /api/pagos/{id}/preference  — obtener/crear URL de MP
    // ──────────────────────────────────────────────────────────────────
    public function getPreference(Request $request, int $id)
    {
        $user = $request->user();
        $pago = DB::table('pago as p')
            ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
            ->where('p.id_pago', $id)
            ->select(
                'p.*',
                DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                'u.correo',
                DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
            )
            ->first();

        if (!$pago) return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);

        if (in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $pago->id_usuario) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        try {
            $mpService   = new MercadoPagoService();
            $preferencia = $mpService->crearPreferencia([
                'id_pago'       => $pago->id_pago,
                'monto'         => $pago->saldo_restante,
                'motivo'        => $pago->motivo_pago ?? 'Pago Academia',
                'alumno_email'  => $pago->correo ?? 'alumno@academia.com',
                'alumno_nombre' => $pago->nombre_alumno,
            ]);

            DB::table('pago')->where('id_pago', $id)->update([
                'mp_preference_id' => $preferencia['id'],
            ]);

            return response()->json([
                'success'            => true,
                'preference_id'      => $preferencia['id'],
                'init_point'         => $preferencia['init_point'],
                'sandbox_init_point' => $preferencia['sandbox_init_point'],
            ]);

        } catch (\Exception $e) {
            Log::error('PagoApi@getPreference: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al crear la preferencia.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /api/pagos/historial/{id}
    // ──────────────────────────────────────────────────────────────────
    public function historialAlumno(Request $request, int $idUsuario)
    {
        $user = $request->user();

        if (in_array($user->rol, ['alumno', 'tutor']) && (int) $user->id_usuario !== $idUsuario) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso.'], 403);
        }

        try {
            $pagos = DB::table('pago as p')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->leftJoin('concepto_pago as cp', 'p.id_concepto', '=', 'cp.id_concepto')
                ->where('p.id_usuario', $idUsuario)
                ->select(
                    'p.id_pago', 'p.monto', 'p.monto_total', 'p.monto_pagado',
                    'p.motivo_pago', 'p.fecha_pago', 'p.referencia_pago',
                    'p.estado_pago', 'p.mp_status', 'tp.nombre_tipo',
                    'cp.nombre AS nombre_concepto',
                    DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
                )
                ->orderBy('p.fecha_pago', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $pagos]);

        } catch (\Exception $e) {
            Log::error('PagoApi@historialAlumno: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener historial.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /api/pagos/{id}/abonos
    // ──────────────────────────────────────────────────────────────────
    public function listarAbonos(Request $request, int $id)
    {
        $user = $request->user();
        $pago = DB::table('pago')->where('id_pago', $id)->first();
        if (!$pago) return response()->json(['success' => false, 'message' => 'No encontrado.'], 404);

        if (in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $pago->id_usuario) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        try {
            $abonos = DB::table('abono as a')
                ->leftJoin('usuario as u', 'a.registrado_por', '=', 'u.id_usuario')
                ->where('a.id_pago', $id)
                ->select(
                    'a.id_abono', 'a.monto_abono', 'a.fecha_abono',
                    'a.tipo_abono', 'a.referencia', 'a.mp_status',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS registrado_por_nombre")
                )
                ->orderBy('a.fecha_abono', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $abonos]);

        } catch (\Exception $e) {
            Log::error('PagoApi@listarAbonos: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /api/conceptos-pago  — catálogo activo (todos los roles)
    // ──────────────────────────────────────────────────────────────────
    public function conceptosPago()
    {
        try {
            return response()->json([
                'success' => true,
                'data'    => DB::table('concepto_pago')->where('activo', 1)->orderBy('nombre')->get(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /api/tipos-pago
    // ──────────────────────────────────────────────────────────────────
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

    // ──────────────────────────────────────────────────────────────────
    //  POST /api/conceptos-pago  — crear concepto (admin/sensei)
    // ──────────────────────────────────────────────────────────────────
    public function storeConcepto(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->rol, ['admin', 'sensei'])) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        $validated = $request->validate([
            'nombre'         => 'required|string|max:100|unique:concepto_pago,nombre',
            'descripcion'    => 'nullable|string|max:255',
            'monto_sugerido' => 'nullable|numeric|min:0',
        ]);

        $id = DB::table('concepto_pago')->insertGetId([
            'nombre'         => $validated['nombre'],
            'descripcion'    => $validated['descripcion'] ?? null,
            'monto_sugerido' => $validated['monto_sugerido'] ?? null,
            'activo'         => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Concepto creado correctamente.',
            'id'      => $id,
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────
    //  PUT /api/conceptos-pago/{id}  — editar concepto (admin/sensei)
    // ──────────────────────────────────────────────────────────────────
    public function updateConcepto(Request $request, int $id)
    {
        $user = $request->user();
        if (!in_array($user->rol, ['admin', 'sensei'])) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        $validated = $request->validate([
            'nombre'         => 'required|string|max:100|unique:concepto_pago,nombre,' . $id . ',id_concepto',
            'descripcion'    => 'nullable|string|max:255',
            'monto_sugerido' => 'nullable|numeric|min:0',
            'activo'         => 'nullable|boolean',
        ]);

        DB::table('concepto_pago')->where('id_concepto', $id)->update([
            'nombre'         => $validated['nombre'],
            'descripcion'    => $validated['descripcion'] ?? null,
            'monto_sugerido' => $validated['monto_sugerido'] ?? null,
            'activo'         => $validated['activo'] ?? 1,
        ]);

        return response()->json(['success' => true, 'message' => 'Concepto actualizado correctamente.']);
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /api/pagos/procesar  (Payment Brick callback)
    // ──────────────────────────────────────────────────────────────────
    public function procesar(Request $request)
    {
        // Validación básica de entrada
        // id_pago puede venir como string ("19") o integer (19) desde el Brick
        $validated = $request->validate([
            'formData' => 'required|array',
            'id_pago'  => 'required|numeric',
        ]);

        // Log para diagnóstico — muestra qué llega exactamente del Brick
        Log::info('MP procesar: request recibido', [
            'id_pago'       => $request->input('id_pago'),
            'formData_keys' => array_keys($request->input('formData', [])),
            'content_type'  => $request->header('Content-Type'),
        ]);

        // Obtener el registro de pago con los datos del alumno
        $pagoRegistro = DB::table('pago as p')
            ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
            ->where('p.id_pago', $validated['id_pago'])
            ->select(
                'p.*', 'u.correo',
                DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
            )
            ->first();

        if (!$pagoRegistro) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado.'], 404);
        }

        // No reprocesar pagos ya completados
        if ($pagoRegistro->estado_pago === 'Completado') {
            return response()->json([
                'success' => false,
                'message' => 'Este pago ya fue completado anteriormente.',
            ], 422);
        }

        try {
            // Inicializar MP con el Access Token del servidor
            $accessToken = (string) config('services.mercadopago.access_token', '');
if (empty($accessToken)) {
    Log::error('MP_ACCESS_TOKEN no configurado en .env');
    return response()->json(['success' => false, 'message' => 'Error de configuración de pagos.'], 500);
}
MercadoPagoConfig::setAccessToken($accessToken);

            // Activar modo sandbox si MP_SANDBOX=true (cuenta de prueba Vendedor)
            if (config('services.mercadopago.sandbox', false)) {
                MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
            }

            $formData = $validated['formData'];

            // CORRECCIÓN PRINCIPAL:
            // El Payment Brick envía en formData['transaction_amount'] el monto de la preferencia.
            // NO lo sobreescribimos con saldo_restante para evitar discrepancias que MP rechaza.
            // Solo garantizamos que el email del pagador sea el del alumno registrado.
            $paymentData = $formData;

            // NO sobreescribir el email del payer: el Brick ya lo captura del usuario.
            // Si se reemplaza con el email del alumno registrado en BD, MP puede rechazar
            // el pago en sandbox porque espera el email de la cuenta Comprador de prueba.

            // Metadatos internos — no afectan el procesamiento de MP
            $paymentData['external_reference'] = (string) $pagoRegistro->id_pago;
            $paymentData['metadata']           = ['id_pago' => $pagoRegistro->id_pago];

            // Descripción del pago (aparece en el estado de cuenta)
            if (!isset($paymentData['description']) || empty($paymentData['description'])) {
                $paymentData['description'] = $pagoRegistro->motivo_pago ?? 'Pago Academia Karate-Do';
            }

            Log::info("MP procesar: enviando pago para id_pago #{$pagoRegistro->id_pago}", [
                'transaction_amount' => $paymentData['transaction_amount'] ?? 'no_set',
                'payment_method'     => $paymentData['payment_method_id'] ?? ($paymentData['paymentMethodId'] ?? 'brick'),
            ]);

            $client  = new PaymentClient();
            $payment = $client->create($paymentData);

            Log::info("MP procesar: respuesta MP para pago #{$pagoRegistro->id_pago}", [
                'status'        => $payment->status,
                'status_detail' => $payment->status_detail,
                'payment_id'    => $payment->id,
            ]);

            // Mapeo de estado MP → estado interno
            $estadoInterno = match ($payment->status) {
                'approved'                             => 'Completado',
                'pending', 'in_process', 'authorized' => 'Pendiente',
                default                               => 'Fallido',
            };

            // Monto que acaba de pagarse (viene de la respuesta de MP, más confiable)
            $montoTransaccion = (float) ($payment->transaction_amount ?? $pagoRegistro->saldo_restante);
            $nuevoMontoPagado = ($pagoRegistro->monto_pagado ?? 0) + $montoTransaccion;

            // Actualizar registro de pago
            DB::table('pago')->where('id_pago', $pagoRegistro->id_pago)->update([
                'mp_payment_id'   => (string) $payment->id,
                'mp_status'       => $payment->status,
                'estado_pago'     => $estadoInterno,
                'referencia_pago' => "MP-{$payment->id}",
                'monto_pagado'    => $estadoInterno === 'Completado'
                    ? $nuevoMontoPagado
                    : ($pagoRegistro->monto_pagado ?? 0),
            ]);

            // Registrar abono solo si fue aprobado
            if ($estadoInterno === 'Completado') {
                // Evitar abonos duplicados (por si el webhook llega antes)
                $existeAbono = DB::table('abono')
                    ->where('id_pago', $pagoRegistro->id_pago)
                    ->where('referencia', "MP-{$payment->id}")
                    ->exists();

                if (!$existeAbono) {
                    DB::table('abono')->insert([
                        'id_pago'     => $pagoRegistro->id_pago,
                        'id_usuario'  => $pagoRegistro->id_usuario,
                        'monto_abono' => $montoTransaccion,
                        'fecha_abono' => now(),
                        'tipo_abono'  => 'en_linea',
                        'mp_status'   => $payment->status,
                        'referencia'  => "MP-{$payment->id}",
                    ]);
                }
            }

            return response()->json([
                'success'        => true,
                'status'         => $payment->status,
                'status_detail'  => $payment->status_detail,
                'id'             => $payment->id,
                'estado_interno' => $estadoInterno,
            ]);

        } catch (\MercadoPago\Exceptions\MPApiException $e) {
            // Error de la API de MP (tarjeta rechazada, datos inválidos, etc.)
            $msgOriginal = $e->getMessage();
            Log::warning("MP procesar API error para pago #{$pagoRegistro->id_pago}: {$msgOriginal}");
            return response()->json([
                'success' => false,
                'message' => $this->traducirErrorMP($msgOriginal),
            ], 422);

        } catch (\Exception $e) {
            Log::error("MP procesar error para pago #{$pagoRegistro->id_pago}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago. Intenta de nuevo.',
            ], 500);
        }
    }


    // ──────────────────────────────────────────────────────────────────
    //  POST /api/pagos/webhook  (PÚBLICA — sin auth:sanctum)
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
                return response()->json(['ok' => false]);
            }

            $pago        = $response->json();
            $mpStatus    = $pago['status']             ?? null;
            $externalRef = $pago['external_reference'] ?? null;
            $monto       = $pago['transaction_amount'] ?? 0;

            if (!$externalRef) return response()->json(['ok' => true]);

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

                DB::table('pago')->where('id_pago', (int) $externalRef)->update([
                    'mp_payment_id'   => (string) $paymentId,
                    'mp_status'       => $mpStatus,
                    'estado_pago'     => $estadoFinal,
                    'referencia_pago' => "MP-{$paymentId}",
                    'monto_pagado'    => $estadoInterno === 'Completado'
                        ? $nuevoMontoPagado
                        : $pagoRegistro->monto_pagado,
                ]);

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

    // ── Helper ────────────────────────────────────────────────────────
    private function traducirErrorMP(string $mensaje): string
    {
        $traducciones = [
            'cc_rejected_insufficient_amount'      => 'Fondos insuficientes en la tarjeta.',
            'cc_rejected_bad_filled_card_number'   => 'Número de tarjeta incorrecto.',
            'cc_rejected_bad_filled_date'          => 'Fecha de vencimiento incorrecta.',
            'cc_rejected_bad_filled_security_code' => 'Código de seguridad incorrecto.',
            'cc_rejected_blacklist'                => 'Tarjeta no habilitada para este tipo de pago.',
            'cc_rejected_call_for_authorize'       => 'Debes autorizar el pago con tu banco.',
            'cc_rejected_card_disabled'            => 'Tarjeta deshabilitada. Contacta a tu banco.',
            'cc_rejected_duplicated_payment'       => 'Pago duplicado. Ya realizaste este pago.',
            'cc_rejected_high_risk'                => 'Pago rechazado por seguridad.',
            'cc_rejected_max_attempts'             => 'Alcanzaste el límite de intentos. Usa otra tarjeta.',
        ];

        foreach ($traducciones as $key => $texto) {
            if (str_contains($mensaje, $key)) return $texto;
        }

        return 'El pago no pudo procesarse. Verifica los datos e intenta de nuevo.';
    }
}