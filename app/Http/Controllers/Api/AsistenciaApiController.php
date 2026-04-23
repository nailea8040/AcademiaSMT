<?php

namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
 
// ════════════════════════════════════════════════════════════════════════════
//  AsistenciaApiController
//
//  Tabla asistencia: id_asistencia, id_usuario, fecha, token, registrado_por
//
//  GET  /api/asistencia?fecha=YYYY-MM-DD  → lista asistencias del día
//  POST /api/asistencia                   → registra asistencia por QR
// ════════════════════════════════════════════════════════════════════════════
class AsistenciaApiController extends Controller
{
    /**
     * GET /api/asistencia?fecha=YYYY-MM-DD
     * Lista las asistencias de un día con nombre del alumno
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
                    'a.token',
                    'a.registrado_por',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_alumno"),
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
     * Registra asistencia al escanear el QR del alumno
     *
     * Body: {
     *   id_usuario:     int     — alumno escaneado
     *   fecha:          string  — ISO timestamp del escaneo
     *   token:          string  — token único del QR
     *   registrado_por: int     — id del sensei/admin que escanea
     * }
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
            // Verificar que no haya asistencia duplicada hoy para este usuario
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
 
            $id = DB::table('asistencia')->insertGetId([
                'id_usuario'     => $validated['id_usuario'],
                'fecha'          => $validated['fecha'],
                'token'          => $validated['token'],
                'registrado_por' => $validated['registrado_por'],
            ]);
 
            // Obtener nombre del alumno para la respuesta
            $alumno = DB::table('usuario')
                ->where('id_usuario', $validated['id_usuario'])
                ->select('nombre', 'apaterno', 'rol')
                ->first();
 
            return response()->json([
                'success'      => true,
                'message'      => 'Asistencia registrada correctamente.',
                'id'           => $id,
                'nombre'       => $alumno ? trim($alumno->nombre . ' ' . $alumno->apaterno) : '',
                'rol'          => $alumno?->rol ?? '',
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