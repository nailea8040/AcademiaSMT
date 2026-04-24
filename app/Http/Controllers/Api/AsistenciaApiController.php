<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AsistenciaApiController extends Controller
{
    /**
     * GET /api/asistencia?fecha=YYYY-MM-DD
     * Lista asistencias del día con datos bachiller incluidos
     */
    public function index(Request $request)
    {
        $fecha = $request->query('fecha', now()->toDateString());

        try {
            $asistencias = DB::table('asistencia as a')
                ->join('usuario as u', 'a.id_usuario', '=', 'u.id_usuario')
                ->whereDate('a.fecha', $fecha)
                ->select(
                    'a.id_asistencia',
                    'a.id_usuario',
                    'a.fecha',
                    DB::raw("TIME(a.fecha) AS hora_registro"),
                    'a.token',
                    'a.registrado_por',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno,' ',COALESCE(u.amaterno,''))
                             AS nombre_completo"),
                    'u.numero_control',
                    'u.grupo',
                    'u.especialidad',
                    'u.turno',
                    'u.rol'
                )
                ->orderBy('a.fecha', 'asc')
                ->get();

            return response()->json($asistencias);

        } catch (\Exception $e) {
            Log::error('AsistenciaApi@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener asistencias.',
            ], 500);
        }
    }

    /**
     * POST /api/asistencia
     * Registra asistencia al escanear QR
     *
     * Body: { id_usuario, fecha, token, registrado_por }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_usuario'     => 'required|exists:usuario,id_usuario',
            'fecha'          => 'required|string',
            'token'          => 'required|string',
            'registrado_por' => 'required|exists:usuario,id_usuario',
        ]);

        try {
            // Evitar duplicado en el mismo día
            $hoy = now()->toDateString();
            $yaRegistrado = DB::table('asistencia')
                ->where('id_usuario', $validated['id_usuario'])
                ->whereDate('fecha', $hoy)
                ->exists();

            if ($yaRegistrado) {
                return response()->json([
                    'success' => false,
                    'message' => 'La asistencia de este alumno ya fue registrada hoy.',
                ], 409);
            }

            DB::table('asistencia')->insertGetId([
                'id_usuario'     => $validated['id_usuario'],
                'fecha'          => $validated['fecha'],
                'token'          => $validated['token'],
                'registrado_por' => $validated['registrado_por'],
            ]);

            // Devolver datos del alumno incluyendo bachiller
            $alumno = DB::table('usuario')
                ->where('id_usuario', $validated['id_usuario'])
                ->select(
                    'nombre', 'apaterno', 'amaterno', 'rol',
                    'numero_control', 'grupo', 'especialidad', 'turno'
                )
                ->first();

            return response()->json([
                'success'        => true,
                'message'        => 'Asistencia registrada.',
                'nombre_completo'=> trim($alumno->nombre . ' ' . $alumno->apaterno . ' ' . ($alumno->amaterno ?? '')),
                'rol'            => $alumno->rol,
                'es_bachiller'   => !is_null($alumno->numero_control),
                'numero_control' => $alumno->numero_control,
                'grupo'          => $alumno->grupo,
                'especialidad'   => $alumno->especialidad,
                'turno'          => $alumno->turno,
            ], 201);

        } catch (\Exception $e) {
            Log::error('AsistenciaApi@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar asistencia.',
            ], 500);
        }
    }
}