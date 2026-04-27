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
     *
     * Body: {
     *   id_usuario:     int     — alumno escaneado
     *   fecha:          string  — ISO timestamp  ej: "2025-04-24T14:30:00.000Z"
     *   token:          string  — token del QR
     *   registrado_por: int     — id del sensei/admin
     * }
     */
    public function store(Request $request)
    {
        // ── CORRECCIÓN: quitar exists:usuario en registrado_por ────────────
        // Si registrado_por=0 (cuando userData es null) el exists falla.
        // También el campo fecha viene como ISO string desde la app,
        // cambiamos la validación a 'string' en vez de 'date_format' estricto.
        try {
            $validated = $request->validate([
                'id_usuario'     => 'required|integer|exists:usuario,id_usuario',
                'fecha'          => 'required|string',   // ISO string desde la app
                'token'          => 'required|string',
                'registrado_por' => 'required|integer',  // sin exists para evitar fallo con 0
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('AsistenciaApi@store validación: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors'  => $e->errors(),
            ], 422);
        }

        try {
            // Verificar que registrado_por sea un usuario real (si no es 0)
            if ($validated['registrado_por'] > 0) {
                $registrador = DB::table('usuario')
                    ->where('id_usuario', $validated['registrado_por'])
                    ->first();

                if (!$registrador) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El usuario que registra no existe.',
                    ], 422);
                }
            }

            // Verificar duplicado en el mismo día
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

            // Convertir el ISO string a formato MySQL datetime
            // La app envía: "2025-04-24T14:30:00.000Z"
            // MySQL espera: "2025-04-24 14:30:00"
            $fechaMysql = date('Y-m-d H:i:s', strtotime($validated['fecha']));

            DB::table('asistencia')->insert([
                'id_usuario'     => $validated['id_usuario'],
                'fecha'          => $fechaMysql,
                'token'          => $validated['token'],
                'registrado_por' => $validated['registrado_por'] > 0
                                    ? $validated['registrado_por']
                                    : null,
            ]);

            // Datos del alumno para la respuesta
            $alumno = DB::table('usuario')
                ->where('id_usuario', $validated['id_usuario'])
                ->select(
                    'nombre', 'apaterno', 'amaterno', 'rol',
                    'numero_control', 'grupo', 'especialidad', 'turno'
                )
                ->first();

            $nombreCompleto = trim(
                $alumno->nombre . ' ' .
                $alumno->apaterno . ' ' .
                ($alumno->amaterno ?? '')
            );

            return response()->json([
                'success'        => true,
                'message'        => 'Asistencia registrada.',
                'nombre_completo'=> $nombreCompleto,
                'rol'            => $alumno->rol,
                'es_bachiller'   => !is_null($alumno->numero_control),
                'numero_control' => $alumno->numero_control,
                'grupo'          => $alumno->grupo,
                'especialidad'   => $alumno->especialidad,
                'turno'          => $alumno->turno,
            ], 201);

        } catch (\Exception $e) {
            // Loguear el error REAL para poder depurar
            Log::error('AsistenciaApi@store: ' . $e->getMessage());
            Log::error('AsistenciaApi@store trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar asistencia.',
                // En desarrollo mostrar el error real:
                'debug'   => app()->environment('production') ? null : $e->getMessage(),
            ], 500);
        }
    }
}