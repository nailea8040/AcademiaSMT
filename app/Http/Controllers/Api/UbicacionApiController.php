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
            // Trae el registro más reciente
            $row = DB::table('ubicacion_dojo')
                ->orderBy('actualizado_en', 'desc')
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
                'ubicacion' => [
                    'latitud'      => $row->latitud,
                    'longitud'     => $row->longitud,
                    'radio_metros' => $row->radio_metros,
                    'actualizado'  => $row->actualizado_en,
                    'por'          => $row->guardado_por,
                ],
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
            $radioMetros = $validated['radio_metros'] ?? 50;

            // Actualiza si ya existe un registro, inserta si no
            $existe = DB::table('ubicacion_dojo')->first();

            if ($existe) {
                DB::table('ubicacion_dojo')
                    ->where('id', $existe->id)
                    ->update([
                        'latitud'      => $validated['latitud'],
                        'longitud'     => $validated['longitud'],
                        'radio_metros' => $radioMetros,
                        'guardado_por' => $user->id_usuario,
                        // actualizado_en se actualiza solo por ON UPDATE CURRENT_TIMESTAMP
                    ]);
            } else {
                DB::table('ubicacion_dojo')->insert([
                    'latitud'      => $validated['latitud'],
                    'longitud'     => $validated['longitud'],
                    'radio_metros' => $radioMetros,
                    'guardado_por' => $user->id_usuario,
                ]);
            }

            // Devolver los datos actualizados
            $row = DB::table('ubicacion_dojo')
                ->orderBy('actualizado_en', 'desc')
                ->first();

            return response()->json([
                'success'   => true,
                'message'   => 'Ubicación del dojo guardada correctamente.',
                'ubicacion' => [
                    'latitud'      => $row->latitud,
                    'longitud'     => $row->longitud,
                    'radio_metros' => $row->radio_metros,
                    'actualizado'  => $row->actualizado_en,
                    'por'          => $row->guardado_por,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('UbicacionApi@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar.'], 500);
        }
    }
}