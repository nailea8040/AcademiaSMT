<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalendarioApiController extends Controller
{
    private function esAdmin(Request $request): bool
    {
        // BD usa 'admin', no 'administrador'
        return $request->user()->rol === 'admin';
    }

    /**
     * GET /api/calendario
     * Pública — no requiere token
     */
    public function index()
    {
        try {
            $eventos = DB::table('calendario')
                ->orderBy('fecha', 'asc')
                ->orderBy('hora', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $eventos,
            ]);

        } catch (\Exception $e) {
            Log::error('CalendarioApi@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el calendario.',
            ], 500);
        }
    }

    /**
     * POST /api/calendario
     * Solo admin
     */
    public function store(Request $request)
    {
        if (!$this->esAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo administradores pueden crear eventos.',
            ], 403);
        }

        $validated = $request->validate([
            'titulo'      => 'required|string|max:100',
            'fecha'       => 'required|date',
            'hora'        => 'required',
            'ubicacion'   => 'required|string|max:255',
            'tipo'        => 'required|string|max:50',
            'descripcion' => 'nullable|string',
        ]);

        try {
            // PK en BD: id_cal (AUTO_INCREMENT, no se inserta)
            $id = DB::table('calendario')->insertGetId([
                'titulo'      => $validated['titulo'],
                'fecha'       => $validated['fecha'],
                'hora'        => $validated['hora'],
                'ubicacion'   => $validated['ubicacion'],
                'tipo'        => $validated['tipo'],
                'descripcion' => $validated['descripcion'] ?? null,
                'id_usuario'  => $request->user()->id_usuario,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Evento creado con éxito.',
                'id'      => $id,
            ], 201);

        } catch (\Exception $e) {
            Log::error('CalendarioApi@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el evento.',
            ], 500);
        }
    }

    /**
     * PUT /api/calendario/{id}
     * Solo admin — PK real: id_cal
     */
    public function update(Request $request, $id)
    {
        if (!$this->esAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo administradores pueden editar eventos.',
            ], 403);
        }

        $validated = $request->validate([
            'titulo'      => 'required|string|max:100',
            'fecha'       => 'required|date',
            'hora'        => 'required',
            'ubicacion'   => 'required|string|max:255',
            'tipo'        => 'required|string|max:50',
            'descripcion' => 'nullable|string',
        ]);

        try {
            $updated = DB::table('calendario')
                ->where('id_cal', $id)
                ->update([
                    'titulo'      => $validated['titulo'],
                    'fecha'       => $validated['fecha'],
                    'hora'        => $validated['hora'],
                    'ubicacion'   => $validated['ubicacion'],
                    'tipo'        => $validated['tipo'],
                    'descripcion' => $validated['descripcion'] ?? null,
                    'updated_at'  => now(),
                ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evento no encontrado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Evento actualizado con éxito.',
            ]);

        } catch (\Exception $e) {
            Log::error('CalendarioApi@update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el evento.',
            ], 500);
        }
    }

    /**
     * DELETE /api/calendario/{id}
     * Solo admin — PK real: id_cal
     */
    public function destroy(Request $request, $id)
    {
        if (!$this->esAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo administradores pueden eliminar eventos.',
            ], 403);
        }

        try {
            $deleted = DB::table('calendario')
                ->where('id_cal', $id)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evento no encontrado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Evento eliminado con éxito.',
            ]);

        } catch (\Exception $e) {
            Log::error('CalendarioApi@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el evento.',
            ], 500);
        }
    }
}