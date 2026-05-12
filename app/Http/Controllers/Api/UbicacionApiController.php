<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UbicacionApiController extends Controller
{
    /** * GET /api/ubicacion 
     * Obtiene la última ubicación registrada del dojo.
     */
    public function index()
    {
        try {
            // Obtenemos el registro más reciente de la nueva tabla
            $row = DB::table('ubicacion_dojo')
                ->latest('actualizado_en')
                ->first();

            if (!$row) {
                return response()->json([
                    'success'   => false,
                    'message'   => 'No se ha configurado la ubicación del dojo.',
                    'ubicacion' => null,
                ]);
            }

            // Mapeamos los nombres de la tabla a lo que espera el Frontend
            return response()->json([
                'success'   => true,
                'ubicacion' => [
                    'latitud'      => $row->latitud,
                    'longitud'     => $row->longitud,
                    'radio_metros' => $row->radio_metros,
                    'actualizado'  => $row->actualizado_en,
                    'por'          => $row->guardado_por
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('UbicacionApi@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener ubicación.'], 500);
        }
    }

    /** * POST /api/ubicacion 
     * Guarda o actualiza la ubicación. Solo accesible para admin/sensei.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Verificación de rol basada en tu lógica actual
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
            $radio = $validated['radio_metros'] ?? 50;

            // Usamos updateOrInsert con ID 1 para mantener un único registro de configuración
            // o podrías usar solo insert() si deseas guardar un histórico de cambios.
            DB::table('ubicacion_dojo')->updateOrInsert(
                ['id' => 1], 
                [
                    'latitud'        => $validated['latitud'],
                    'longitud'       => $validated['longitud'],
                    'radio_metros'   => $radio,
                    'guardado_por'   => $user->id_usuario,
                    'actualizado_en' => now(),
                ]
            );

            return response()->json([
                'success'   => true,
                'message'   => 'Ubicación del dojo guardada correctamente.',
                'ubicacion' => [
                    'latitud'      => $validated['latitud'],
                    'longitud'     => $validated['longitud'],
                    'radio_metros' => $radio,
                    'actualizado'  => now()->toDateTimeString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('UbicacionApi@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar la ubicación.'], 500);
        }
    }
}