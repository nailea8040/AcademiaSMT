<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

// ════════════════════════════════════════════════════════════════════════════
//  UbicacionApiController
//
//  Guarda y devuelve la ubicación GPS del dojo para validar
//  que el alumno está físicamente en el lugar al registrar asistencia.
//
//  GET  /api/ubicacion → devuelve coordenadas del dojo
//  POST /api/ubicacion → guarda coordenadas del dojo (solo admin/sensei)
//
//  Las coordenadas se guardan en un registro simple de configuración.
//  Si no tienes tabla config, se pueden guardar en caché o en un JSON.
// ════════════════════════════════════════════════════════════════════════════

class UbicacionApiController extends Controller
{
    // Usar cache de Laravel para guardar la ubicación del dojo
    // (sin necesidad de tabla extra)
 
    /** GET /api/ubicacion */
    public function index()
    {
        try {
            $ubicacion = Cache::get('dojo_ubicacion', null);
 
            if (!$ubicacion) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'No se ha configurado la ubicación del dojo.',
                    'ubicacion' => null,
                ]);
            }
 
            return response()->json([
                'success'   => true,
                'ubicacion' => $ubicacion,
            ]);
 
        } catch (\Exception $e) {
            Log::error('UbicacionApi@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }
 
    /** POST /api/ubicacion — solo admin/sensei */
    public function store(Request $request)
    {
        $user = $request->user();
 
        if (!in_array($user->rol, ['admin', 'sensei'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo admin o sensei pueden configurar la ubicación.',
            ], 403);
        }
 
        $validated = $request->validate([
            'latitud'      => 'required|numeric',
            'longitud'     => 'required|numeric',
            'radio_metros' => 'nullable|integer|min:10|max:500',
        ]);
 
        try {
            $ubicacion = [
                'latitud'      => $validated['latitud'],
                'longitud'     => $validated['longitud'],
                'radio_metros' => $validated['radio_metros'] ?? 30,
                'actualizado'  => now()->toDateTimeString(),
                'por'          => $user->id_usuario,
            ];
 
            // Guardar en caché (sin expiración = permanente hasta reinicio)
            Cache::forever('dojo_ubicacion', $ubicacion);
 
            return response()->json([
                'success'   => true,
                'message'   => 'Ubicación del dojo guardada correctamente.',
                'ubicacion' => $ubicacion,
            ]);
 
        } catch (\Exception $e) {
            Log::error('UbicacionApi@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar.'], 500);
        }
    }
}
