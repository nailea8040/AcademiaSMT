<?php
// app/Http/Controllers/PagoController.php — REEMPLAZA COMPLETO

namespace App\Http\Controllers;

use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use APP\Models\ConceptoPago;

class PagoController extends Controller
{
    // ──────────────────────────────────────────────────────────────────
    //  GET /pagos
    //  Admin/sensei → todos los pagos + formulario registro + gestión conceptos
    //  Alumno/tutor → solo sus pagos + su propio formulario de pago
    // ──────────────────────────────────────────────────────────────────
    public function index()
    {
        try {
            $user = Auth::user();

            $query = DB::table('pago as p')
                ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->leftJoin('concepto_pago as cp', 'p.id_concepto', '=', 'cp.id_concepto')
                ->select(
                    'p.id_pago',
                    'p.monto',
                    'p.monto_total',
                    'p.monto_pagado',
                    'p.motivo_pago',
                    'p.fecha_pago',
                    'p.referencia_pago',
                    'p.estado_pago',
                    'p.mp_preference_id',
                    'p.mp_payment_id',
                    'p.mp_status',
                    'p.id_concepto',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                    'tp.nombre_tipo',
                    'cp.nombre AS nombre_concepto',
                    'p.id_usuario',
                    'p.id_tipo_pago',
                    DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
                )
                ->orderBy('p.fecha_pago', 'desc');

            // Alumno/tutor solo ven sus propios pagos
            if (in_array($user->rol, ['alumno', 'tutor'])) {
                $query->where('p.id_usuario', $user->id_usuario);
            }

            $pagos = $query->get();

            // Catálogo de conceptos activos — todos los roles lo necesitan para su formulario
            $conceptos = DB::table('concepto_pago')
                ->where('activo', 1)
                ->orderBy('nombre')
                ->get();

            // Tipos de pago — todos los roles los necesitan
            $tipos_pago = DB::table('tipo_pago')->orderBy('id_tipo_pago')->get();

            // Lista de alumnos+tutores — solo admin/sensei para su formulario
            $alumnos = collect();
            // Todos los conceptos (incluye inactivos) — solo admin/sensei para el panel de gestión
            $conceptos_todos = collect();

            if (in_array($user->rol, ['admin', 'sensei'])) {
                $alumnos = DB::table('usuario')
                    ->whereIn('rol', ['alumno', 'tutor'])
                    ->where('estado', 1)
                    ->select('id_usuario', 'rol', DB::raw("CONCAT(nombre,' ',apaterno,' (',rol,')') AS nombre_completo"))
                    ->orderBy('nombre')
                    ->get();

                $conceptos_todos = DB::table('concepto_pago')->orderBy('nombre')->get();
            }

            return view('pagosViews.pagos', compact(
                'pagos', 'alumnos', 'tipos_pago', 'conceptos', 'conceptos_todos', 'user'
            ));

        } catch (\Exception $e) {
            Log::error('PagoController@index: ' . $e->getMessage());
            return view('pagosViews.pagos', [
                'pagos'           => collect(),
                'alumnos'         => collect(),
                'tipos_pago'      => collect(),
                'conceptos'       => collect(),
                'conceptos_todos' => collect(),
                'user'            => Auth::user(),
            ])->with('mensaje', 'Error al cargar datos.');
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /pagos
    //  Admin/sensei: registran cargos para cualquier alumno/tutor.
    //                Pueden marcar estado Pendiente o Completado.
    //  Alumno/tutor: registran su propio pago eligiendo concepto del
    //                catálogo y ajustando el monto.
    //                Estado siempre Pendiente hasta que admin confirme.
    // ──────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $user          = Auth::user();
        $esAdminSensei = in_array($user->rol, ['admin', 'sensei']);
        $esAlumnoTutor = in_array($user->rol, ['alumno', 'tutor']);

        // Reglas comunes a todos los roles
        $rules = [
            'id_concepto'    => 'required|exists:concepto_pago,id_concepto',
            'id_tipo_pago'   => 'required|exists:tipo_pago,id_tipo_pago',
            'monto'          => 'required|numeric|min:1',
            'fechaPago'      => 'required|date',
            'motivoPago'     => 'nullable|string|max:100',
            'pagar_en_linea' => 'nullable|boolean',
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

        // Estado inicial:
        //   Admin sin en_línea → el que eligió (Pendiente o Completado)
        //   Admin con en_línea → siempre Pendiente (MP lo actualiza)
        //   Alumno/tutor       → siempre Pendiente (admin confirma efectivo)
        $estadoFinal = 'Pendiente';
        if ($esAdminSensei && !$pagarEnLinea) {
            $estadoFinal = $validated['estadoPago'];
        }

        // Autocompletar motivo desde el nombre del concepto si no se escribió nada
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

            // Admin registra efectivo y lo marca Completado → abono automático inmediato
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

            // Alumno elige efectivo → solo se registra el pago como Pendiente.
            // El admin lo verá, verificará el efectivo y presionará "Completar".
            // No se hace nada extra aquí.

            // Pago en línea → ir a Payment Brick
            if ($pagarEnLinea) {
                return redirect()->route('pagos.pagar', $id)
                    ->with('sessionInsertado', 'true')
                    ->with('mensaje', 'Pago creado. Redirigiendo al pago en línea...');
            }

            $mensajeExtra = ($esAlumnoTutor && $estadoFinal === 'Pendiente')
                ? ' Quedará pendiente hasta que el administrador confirme el pago.'
                : '';

            return redirect()->route('pagos.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', '¡Pago registrado con éxito!' . $mensajeExtra);

        } catch (\Exception $e) {
            Log::error('PagoController@store: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al registrar el pago.');
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /pagos/{id}/completar
    //  Solo admin/sensei. Marca un pago como Completado tras verificar.
    // ──────────────────────────────────────────────────────────────────
    public function completar(int $id)
    {
        $user = Auth::user();
        if (!in_array($user->rol, ['admin', 'sensei'])) abort(403);

        $pago = DB::table('pago')->where('id_pago', $id)->first();
        if (!$pago) abort(404);

        if ($pago->estado_pago === 'Completado') {
            return redirect()->route('pagos.index')
                ->with('mensaje', 'Este pago ya estaba completado.')
                ->with('sessionInsertado', 'true');
        }

        $montoTotal = $pago->monto_total ?? $pago->monto;

        DB::table('pago')->where('id_pago', $id)->update([
            'estado_pago'  => 'Completado',
            'monto_pagado' => $montoTotal,
        ]);

        // Registrar abono de verificación si no había ninguno previo
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
            // Si ya había abonos parciales, registrar el abono de la diferencia
            $totalAbonado = DB::table('abono')->where('id_pago', $id)->sum('monto_abono');
            $diferencia   = $montoTotal - $totalAbonado;
            if ($diferencia > 0) {
                DB::table('abono')->insert([
                    'id_pago'        => $id,
                    'id_usuario'     => $pago->id_usuario,
                    'monto_abono'    => $diferencia,
                    'fecha_abono'    => now(),
                    'tipo_abono'     => $pago->mp_payment_id ? 'en_linea' : 'efectivo',
                    'referencia'     => 'Completado por admin',
                    'registrado_por' => $user->id_usuario,
                ]);
            }
        }

        return redirect()->route('pagos.index')
            ->with('sessionInsertado', 'true')
            ->with('mensaje', 'Pago marcado como completado correctamente.');
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /pagos/{id}/abono
    //  Admin/sensei → efectivo (aplica inmediato) o en línea (MP)
    //  Alumno/tutor → efectivo (queda Pendiente, admin confirma)
    //                 o en línea (MP, aplica automático)
    // ──────────────────────────────────────────────────────────────────
    public function abono(Request $request, int $id)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'monto_abono' => 'required|numeric|min:1',
            'tipo_abono'  => 'required|in:efectivo,en_linea',
            'referencia'  => 'nullable|string|max:100',
        ]);

        $pago = DB::table('pago')->where('id_pago', $id)->first();
        if (!$pago) abort(404);

        // Alumno/tutor solo pueden abonar a SUS propios pagos
        if (in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $pago->id_usuario) {
            abort(403);
        }

        $montoTotal  = $pago->monto_total  ?? $pago->monto;
        $montoPagado = $pago->monto_pagado ?? 0;
        $saldo       = $montoTotal - $montoPagado;

        if ($validated['monto_abono'] > $saldo) {
            return redirect()->back()
                ->with('mensaje', "El abono ($" . number_format($validated['monto_abono'], 2) . ") no puede superar el saldo ($" . number_format($saldo, 2) . ").")
                ->with('sessionInsertado', 'false');
        }

        try {
            // Abono en línea → redirigir a Payment Brick
            if ($validated['tipo_abono'] === 'en_linea') {
                session(['abono_pendiente' => [
                    'id_pago'     => $id,
                    'monto_abono' => $validated['monto_abono'],
                ]]);
                return redirect()->route('pagos.pagar', $id)
                    ->with('mensaje', 'Redirigiendo al pago en línea...')
                    ->with('sessionInsertado', 'true');
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
                // Admin: aplica el abono de inmediato
                $nuevoMontoPagado = $montoPagado + $validated['monto_abono'];
                $nuevoEstado      = $nuevoMontoPagado >= $montoTotal ? 'Completado' : 'Pendiente';
                DB::table('pago')->where('id_pago', $id)->update([
                    'monto_pagado' => $nuevoMontoPagado,
                    'estado_pago'  => $nuevoEstado,
                ]);
                $saldoRestante = $montoTotal - $nuevoMontoPagado;
                $mensaje = "Abono de $" . number_format($validated['monto_abono'], 2) . " registrado. ";
                $mensaje .= $nuevoEstado === 'Completado'
                    ? '¡Pago completado!'
                    : "Saldo restante: $" . number_format($saldoRestante, 2);
            } else {
                // Alumno: el pago queda Pendiente hasta que admin confirme
                $mensaje = "Abono de $" . number_format($validated['monto_abono'], 2) . " registrado. "
                    . "Quedará pendiente hasta que el administrador lo confirme.";
            }

            return redirect()->route('pagos.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', $mensaje);

        } catch (\Exception $e) {
            Log::error('PagoController@abono: ' . $e->getMessage());
            return redirect()->back()
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al registrar el abono.');
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /pagos/{id}/pagar  — Payment Brick
    // ──────────────────────────────────────────────────────────────────
    public function pagar(int $idPago)
    {
        $pago = DB::table('pago as p')
            ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
            ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
            ->leftJoin('concepto_pago as cp', 'p.id_concepto', '=', 'cp.id_concepto')
            ->where('p.id_pago', $idPago)
            ->select(
                'p.*',
                DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                'u.correo',
                'tp.nombre_tipo',
                'cp.nombre AS nombre_concepto',
                DB::raw("COALESCE(p.monto_total, p.monto) AS monto_total_calc"),
                DB::raw("COALESCE(p.monto_pagado, 0) AS monto_pagado_calc"),
                DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
            )
            ->first();

        if (!$pago) abort(404);

        $user = Auth::user();

        if (in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $pago->id_usuario) {
            abort(403, 'No tienes permiso para pagar este registro.');
        }

        if ($pago->estado_pago === 'Completado') {
            return redirect()->route('pagos.index')
                ->with('mensaje', 'Este pago ya fue completado.')
                ->with('sessionInsertado', 'true');
        }

        $abonoPendiente = session('abono_pendiente');
        $montoACobrar   = ($abonoPendiente && (int) $abonoPendiente['id_pago'] === $idPago)
            ? (float) $abonoPendiente['monto_abono']
            : (float) $pago->saldo_restante;

        try {
            $mpService   = new MercadoPagoService();
            $preferencia = $mpService->crearPreferencia([
                'id_pago'       => $pago->id_pago,
                'monto'         => $montoACobrar,
                'motivo'        => $pago->motivo_pago ?? $pago->nombre_concepto ?? 'Pago Academia',
                'alumno_email'  => $pago->correo ?? 'alumno@academia.com',
                'alumno_nombre' => $pago->nombre_alumno,
            ]);

            $preferenceId = $preferencia['id'];

            DB::table('pago')->where('id_pago', $idPago)->update([
                'mp_preference_id' => $preferenceId,
                'estado_pago'      => 'Pendiente',
            ]);

            session()->forget('abono_pendiente');

        } catch (\Exception $e) {
            Log::error('PagoController@pagar: ' . $e->getMessage());
            return redirect()->route('pagos.index')
                ->with('mensaje', 'Error al inicializar el pago. Intenta de nuevo.')
                ->with('sessionInsertado', 'false');
        }

        return view('pagosViews.pagar', compact('pago', 'preferenceId', 'montoACobrar'));
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /pagos/resultado  — back_url de MercadoPago
    // ──────────────────────────────────────────────────────────────────
    public function resultado(Request $request)
    {
        $estado = $request->query('estado');
        $idPago = $request->query('id_pago');
        $pago   = $idPago
            ? DB::table('pago')->where('id_pago', $idPago)->first()
            : null;

        return view('pagosViews.resultado', compact('estado', 'pago'));
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /pagos/{id}/historial
    // ──────────────────────────────────────────────────────────────────
    public function historialAlumno(int $id_usuario)
    {
        try {
            $pagos = DB::table('pago as p')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->leftJoin('concepto_pago as cp', 'p.id_concepto', '=', 'cp.id_concepto')
                ->where('p.id_usuario', $id_usuario)
                ->select(
                    'p.id_pago', 'p.monto', 'p.monto_total', 'p.monto_pagado',
                    'p.motivo_pago', 'p.fecha_pago', 'p.referencia_pago',
                    'p.estado_pago', 'p.mp_status', 'tp.nombre_tipo',
                    'cp.nombre AS nombre_concepto',
                    DB::raw("COALESCE(p.monto_total, p.monto) - COALESCE(p.monto_pagado, 0) AS saldo_restante")
                )
                ->orderBy('p.fecha_pago', 'desc')
                ->get();

            return response()->json($pagos);

        } catch (\Exception $e) {
            Log::error('PagoController@historialAlumno: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener historial.'], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  GET /pagos/{id}/abonos
    // ──────────────────────────────────────────────────────────────────
    public function listarAbonos(int $id)
    {
        $user = Auth::user();
        $pago = DB::table('pago')->where('id_pago', $id)->first();
        if (!$pago) abort(404);

        if (in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $pago->id_usuario) {
            abort(403);
        }

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

        return response()->json($abonos);
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /conceptos-pago  — crear nuevo concepto (admin/sensei)
    // ──────────────────────────────────────────────────────────────────
    public function storeConcepto(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->rol, ['admin', 'sensei'])) abort(403);

        $validated = $request->validate([
            'nombre'         => 'required|string|max:100|unique:concepto_pago,nombre',
            'descripcion'    => 'nullable|string|max:255',
            'monto_sugerido' => 'nullable|numeric|min:0',
        ]);

        DB::table('concepto_pago')->insert([
            'nombre'         => $validated['nombre'],
            'descripcion'    => $validated['descripcion'] ?? null,
            'monto_sugerido' => $validated['monto_sugerido'] ?? null,
            'activo'         => 1,
        ]);

        return redirect()->route('pagos.index')
            ->with('sessionInsertado', 'true')
            ->with('mensaje', 'Concepto "' . $validated['nombre'] . '" creado correctamente.');
    }

    // ──────────────────────────────────────────────────────────────────
    //  PUT /conceptos-pago/{id}  — editar concepto existente (admin/sensei)
    // ──────────────────────────────────────────────────────────────────
    public function updateConcepto(Request $request, int $id)
    {
        $user = Auth::user();
        if (!in_array($user->rol, ['admin', 'sensei'])) abort(403);

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

        return redirect()->route('pagos.index')
            ->with('sessionInsertado', 'true')
            ->with('mensaje', 'Concepto actualizado correctamente.');
    }
}