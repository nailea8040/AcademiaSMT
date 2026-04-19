<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoApiController extends Controller
{
    /**
     * GET /api/pagos
     *
     * - Admin/sensei: ve todos los pagos
     * - Alumno/tutor: solo ve sus propios pagos
     */
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
                    'p.motivo_pago',
                    'p.fecha_pago',
                    'p.referencia_pago',
                    'p.estado_pago',
                    'p.id_usuario',
                    'p.id_tipo_pago',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                    'tp.nombre_tipo'
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

    /**
     * POST /api/pagos
     * Solo admin y sensei pueden registrar pagos
     */
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
        ]);

        try {
            $id = DB::table('pago')->insertGetId([
                'id_usuario'      => $validated['id_alumno'],
                'id_tipo_pago'    => $validated['id_tipo_pago'],
                'monto'           => $validated['monto'],
                'motivo_pago'     => $validated['motivoPago'] ?? null,
                'fecha_pago'      => $validated['fechaPago'],
                'referencia_pago' => $validated['referenciaPago'] ?? null,
                'estado_pago'     => $validated['estadoPago'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado con éxito.',
                'id'      => $id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('PagoApi@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago.',
            ], 500);
        }
    }

    /**
     * GET /api/pagos/historial/{idUsuario}
     * Historial de pagos de un alumno específico
     *
     * - Admin/sensei: puede ver el historial de cualquier usuario
     * - Alumno/tutor: solo puede ver el suyo propio
     */
    public function historialAlumno(Request $request, $idUsuario)
    {
        $user = $request->user();

        // Alumno/tutor solo puede ver su propio historial
        if (
            in_array($user->rol, ['alumno', 'tutor']) &&
            (int) $user->id_usuario !== (int) $idUsuario
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
                    'p.motivo_pago',
                    'p.fecha_pago',
                    'p.referencia_pago',
                    'p.estado_pago',
                    'tp.nombre_tipo'
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

    /**
     * GET /api/tipos-pago
     * Catálogo de tipos de pago (para llenar el select en la app)
     * Efectivo, Tarjeta, Transferencia, Otro
     */
    public function tiposPago()
    {
        try {
            $tipos = DB::table('tipo_pago')
                ->orderBy('id_tipo_pago', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $tipos,
            ]);

        } catch (\Exception $e) {
            Log::error('PagoApi@tiposPago: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tipos de pago.',
            ], 500);
        }
    }
}