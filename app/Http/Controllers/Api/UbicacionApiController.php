<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UbicacionApiController extends Controller
{
    /** GET /api/ubicacion */
    public function index()
    {
        try {
            $row = DB::table('configuracion')
                ->where('clave', 'dojo_ubicacion')
                ->first();

            if (!$row) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'No se ha configurado la ubicación del dojo.',
                    'ubicacion' => null,
                ]);
            }

            return response()->json([
                'success'   => true,
                'ubicacion' => json_decode($row->valor, true),
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

            // updateOrInsert → crea si no existe, actualiza si ya existe
            DB::table('configuracion')->updateOrInsert(
                ['clave' => 'dojo_ubicacion'],
                ['valor' => json_encode($ubicacion), 'updated_at' => now()]
            );

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