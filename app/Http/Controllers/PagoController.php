<?php
// app/Http/Controllers/PagoController.php — REEMPLAZA COMPLETO

namespace App\Http\Controllers;

use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoController extends Controller
{
    // ──────────────────────────────────────────────────────────────────
    //  GET /pagos
    //  Admin/sensei → todos los pagos + formulario registro
    //  Alumno/tutor → solo sus pagos, SIN formulario (solo ven y pagan)
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

            // Solo admin/sensei necesitan el formulario de registro
            $alumnos    = collect();
            $tipos_pago = collect();
            $conceptos  = collect();

            if (in_array($user->rol, ['admin', 'sensei'])) {
                // Lista de alumnos Y tutores (ambos roles pueden recibir cargos)
                $alumnos = DB::table('usuario')
                    ->whereIn('rol', ['alumno', 'tutor'])
                    ->where('estado', 1)
                    ->select('id_usuario', 'rol', DB::raw("CONCAT(nombre,' ',apaterno,' (',rol,')') AS nombre_completo"))
                    ->orderBy('nombre')
                    ->get();

                $tipos_pago = DB::table('tipo_pago')->orderBy('id_tipo_pago')->get();

                $conceptos = DB::table('concepto_pago')
                    ->where('activo', 1)
                    ->orderBy('nombre')
                    ->get();
            }

            return view('pagosViews.pagos', compact('pagos', 'alumnos', 'tipos_pago', 'conceptos', 'user'));

        } catch (\Exception $e) {
            Log::error('PagoController@index: ' . $e->getMessage());
            return view('pagosViews.pagos', [
                'pagos'      => collect(),
                'alumnos'    => collect(),
                'tipos_pago' => collect(),
                'conceptos'  => collect(),
                'user'       => Auth::user(),
            ])->with('mensaje', 'Error al cargar datos.');
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /pagos
    //  Solo admin/sensei registran cargos nuevos para un alumno.
    //  El alumno NO puede crear pagos — solo los paga.
    //  Estado por defecto: Pendiente.
    //  Si modo efectivo y estado Completado → abono automático.
    // ──────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->rol, ['admin', 'sensei'])) {
            return redirect()->back()
                ->with('mensaje', 'No tienes permiso para registrar pagos.')
                ->with('sessionInsertado', 'false');
        }

        $validated = $request->validate([
            'id_alumno'      => 'required|exists:usuario,id_usuario',
            'id_tipo_pago'   => 'required|exists:tipo_pago,id_tipo_pago',
            'id_concepto'    => 'nullable|exists:concepto_pago,id_concepto',
            'monto'          => 'required|numeric|min:0',
            'fechaPago'      => 'required|date',
            'estadoPago'     => 'required|in:Pendiente,Completado',   // No "Fallido" aquí — lo pone MP
            'motivoPago'     => 'nullable|string|max:100',
            'referenciaPago' => 'nullable|string|max:100',
            'pagar_en_linea' => 'nullable|boolean',
        ]);

        $pagarEnLinea = (bool) ($validated['pagar_en_linea'] ?? false);

        // Si se elige pagar en línea, siempre Pendiente (MP lo actualizará)
        $estadoFinal = $pagarEnLinea ? 'Pendiente' : $validated['estadoPago'];

        // Auto-completar motivo desde concepto si no se escribió
        $motivo = $validated['motivoPago'] ?? null;
        if (!$motivo && !empty($validated['id_concepto'])) {
            $concepto = DB::table('concepto_pago')->find($validated['id_concepto']);
            $motivo   = $concepto?->nombre ?? null;
        }

        try {
            $id = DB::table('pago')->insertGetId([
                'id_usuario'      => $validated['id_alumno'],
                'id_tipo_pago'    => $validated['id_tipo_pago'],
                'id_concepto'     => $validated['id_concepto'] ?? null,
                'monto'           => $validated['monto'],
                'monto_total'     => $validated['monto'],
                'monto_pagado'    => 0.00,
                'motivo_pago'     => $motivo,
                'fecha_pago'      => $validated['fechaPago'],
                'referencia_pago' => $validated['referenciaPago'] ?? null,
                'estado_pago'     => $estadoFinal,
            ]);

            // Si admin registra efectivo y lo marca Completado → abono automático
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

            // Si el admin quiere que el alumno pague en línea → redirigir a /pagos/{id}/pagar
            // El Payment Brick se inicializa ahí (no desde aquí)
            if ($pagarEnLinea) {
                return redirect()->route('pagos.pagar', $id)
                    ->with('sessionInsertado', 'true')
                    ->with('mensaje', 'Cargo creado. Redirigiendo al pago en línea...');
            }

            return redirect()->route('pagos.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', '¡Pago registrado con éxito!');

        } catch (\Exception $e) {
            Log::error('PagoController@store: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al registrar el pago.');
        }
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /pagos/{id}/completar
    //  Admin/sensei marcan un pago como Completado tras verificar
    //  la transacción (transferencia, depósito, etc.)
    // ──────────────────────────────────────────────────────────────────
    public function completar(int $id)
    {
        $user = Auth::user();

        if (!in_array($user->rol, ['admin', 'sensei'])) {
            abort(403);
        }

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

        return redirect()->route('pagos.index')
            ->with('sessionInsertado', 'true')
            ->with('mensaje', 'Pago marcado como completado correctamente.');
    }

    // ──────────────────────────────────────────────────────────────────
    //  POST /pagos/{id}/abono
    //  Admin/sensei → efectivo o en línea
    //  Alumno/tutor → solo en línea (sobre SUS pagos)
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

        // Alumno/tutor no pueden registrar abono en efectivo
        if (in_array($user->rol, ['alumno', 'tutor']) && $validated['tipo_abono'] === 'efectivo') {
            return redirect()->back()
                ->with('mensaje', 'Solo el administrador puede registrar abonos en efectivo.')
                ->with('sessionInsertado', 'false');
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
            // ── Abono en línea → redirigir a Payment Brick ────────────
            if ($validated['tipo_abono'] === 'en_linea') {
                // Guardar monto del abono en sesión para que /pagar lo use
                session(['abono_pendiente' => [
                    'id_pago'     => $id,
                    'monto_abono' => $validated['monto_abono'],
                ]]);

                return redirect()->route('pagos.pagar', $id)
                    ->with('mensaje', 'Redirigiendo al pago en línea...')
                    ->with('sessionInsertado', 'true');
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

            $saldoRestante = $montoTotal - $nuevoMontoPagado;
            $mensaje = "Abono de $" . number_format($validated['monto_abono'], 2) . " registrado. ";
            $mensaje .= $nuevoEstado === 'Completado'
                ? '¡Pago completado!'
                : "Saldo restante: $" . number_format($saldoRestante, 2);

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
    //  GET /pagos/{id}/pagar
    //  Página con Payment Brick. Disponible para cualquier rol si el
    //  pago le pertenece (alumno/tutor) o si es admin/sensei.
    //  El monto cobrado es el saldo restante (o el abono_pendiente).
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

        // Alumno/tutor solo pueden pagar SUS propios pagos
        if (in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $pago->id_usuario) {
            abort(403, 'No tienes permiso para pagar este registro.');
        }

        if ($pago->estado_pago === 'Completado') {
            return redirect()->route('pagos.index')
                ->with('mensaje', 'Este pago ya fue completado.')
                ->with('sessionInsertado', 'true');
        }

        // Si viene de un abono en línea, usar ese monto; si no, el saldo completo
        $abonoPendiente = session('abono_pendiente');
        $montoACobrar   = ($abonoPendiente && (int) $abonoPendiente['id_pago'] === $idPago)
            ? (float) $abonoPendiente['monto_abono']
            : (float) $pago->saldo_restante;

        // Crear/reutilizar preferencia MP por el monto a cobrar
        $preferenceId = null;

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

            // Limpiar sesión de abono
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
    //  GET /pagos/resultado
    //  MercadoPago redirige aquí después del pago (back_urls)
    // ──────────────────────────────────────────────────────────────────
    public function resultado(Request $request)
    {
        $estado = $request->query('estado');
        $idPago = $request->query('id_pago');

        $pago = $idPago
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
                    'p.estado_pago', 'p.mp_status', 'tp.nombre_tipo', 'cp.nombre AS nombre_concepto',
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

        return response()->json($abonos);
    }
}