<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PagoController
 *
 * Estructura real en BD:
 *   pago:      id_pago, id_usuario (FK→usuario), id_tipo_pago (FK→tipo_pago),
 *              monto, motivo_pago, fecha_pago, referencia_pago, estado_pago
 *   tipo_pago: id_tipo_pago, nombre_tipo
 *              Valores: 1=Efectivo, 2=Tarjeta, 3=Transferencia, 4=Otro
 */
class PagoController extends Controller
{
    public function index()
    {
        try {
            $pagos = DB::table('pago as p')
                ->join('usuario as u', 'p.id_usuario', '=', 'u.id_usuario')
                ->leftJoin('tipo_pago as tp', 'p.id_tipo_pago', '=', 'tp.id_tipo_pago')
                ->select(
                    'p.id_pago',
                    'p.monto',
                    'p.motivo_pago',
                    'p.fecha_pago',
                    'p.referencia_pago',
                    'p.estado_pago',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
                    'tp.nombre_tipo',
                    'p.id_usuario',
                    'p.id_tipo_pago'
                )
                ->orderBy('p.fecha_pago', 'desc')
                ->get();

            // Solo alumnos activos para el dropdown
            $alumnos = DB::table('usuario')
                ->where('rol', 'alumno')
                ->where('estado', 1)
                ->select(
                    'id_usuario',
                    DB::raw("CONCAT(nombre,' ',apaterno) AS nombre_completo")
                )
                ->orderBy('nombre', 'asc')
                ->get();

            $tipos_pago = DB::table('tipo_pago')->orderBy('id_tipo_pago', 'asc')->get();

            return view('pagosViews.pagos', compact('pagos', 'alumnos', 'tipos_pago'));

        } catch (\Exception $e) {
            Log::error('PagoController@index: ' . $e->getMessage());
            // Vista correcta: pagosViews.pagos (no usuariosViews.pagos)
            return view('pagosViews.pagos', [
                'pagos'      => collect(),
                'alumnos'    => collect(),
                'tipos_pago' => collect(),
            ])->with('mensaje', 'Error al cargar datos.');
        }
    }

    public function store(Request $request)
    {
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
            DB::table('pago')->insert([
                'id_usuario'      => $validated['id_alumno'],
                'id_tipo_pago'    => $validated['id_tipo_pago'],
                'monto'           => $validated['monto'],
                'motivo_pago'     => $validated['motivoPago'] ?? null,
                'fecha_pago'      => $validated['fechaPago'],
                'referencia_pago' => $validated['referenciaPago'] ?? null,
                'estado_pago'     => $validated['estadoPago'],
            ]);

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

    /**
     * Historial de pagos de un alumno específico (para modal o vista detalle).
     */
    public function historialAlumno($idUsuario)
    {
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

            return response()->json($pagos);

        } catch (\Exception $e) {
            Log::error('PagoController@historialAlumno: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener historial.'], 500);
        }
    }
}