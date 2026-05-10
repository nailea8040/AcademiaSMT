<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TutorApiController extends Controller
{
    /**
     * GET /api/tutores
     * Lista todos los tutores con su información de usuario y ocupación
     */
    public function index()
    {
        try {
            $tutores = DB::table('tutor as t')
                ->join('usuario as u', 't.id_Tutor', '=', 'u.id_usuario')
                ->leftJoin('ocupacion as o', 't.id_ocupacion', '=', 'o.id_ocupacion')
                ->select(
                    't.id_Tutor',
                    't.relacion_estudiante',
                    'o.id_ocupacion',
                    'o.nombre_ocupacion',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno,' ',u.amaterno) AS nombre_completo"),
                    'u.correo',
                    'u.telefono',
                    'u.estado'
                )
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $tutores,
            ]);

        } catch (\Exception $e) {
            Log::error('TutorApi@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tutores.',
            ], 500);
        }
    }

    /**
     * POST /api/tutores
     * Registra el perfil de tutor para un usuario que ya tiene rol='tutor'
     *
     * NOTA: El usuario debe existir previamente en tabla usuario con rol='tutor'.
     * Este endpoint solo crea el registro en la tabla tutor (datos adicionales).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_Tutor'              => 'required|exists:usuario,id_usuario|unique:tutor,id_Tutor',
            'id_ocupacion'          => 'required|exists:ocupacion,id_ocupacion',
            'relacion_estudiante'   => 'required|string|max:50',
            // id_alumno_relacionado agregado — igual que TutorController web
            'id_alumno_relacionado' => 'nullable|exists:usuario,id_usuario',
        ]);

        // Verificar que el usuario tenga rol='tutor'
        $usuario = DB::table('usuario')
            ->where('id_usuario', $validated['id_Tutor'])
            ->first();

        if (!$usuario || $usuario->rol !== 'tutor') {
            return response()->json([
                'success' => false,
                'message' => 'El usuario debe tener rol de tutor.',
            ], 422);
        }

        try {
            DB::table('tutor')->insert([
                'id_Tutor'              => $validated['id_Tutor'],
                'id_ocupacion'          => $validated['id_ocupacion'],
                'relacion_estudiante'   => $validated['relacion_estudiante'],
                'id_alumno_relacionado' => $validated['id_alumno_relacionado'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tutor registrado con éxito.',
            ], 201);

        } catch (\Exception $e) {
            Log::error('TutorApi@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el tutor.',
            ], 500);
        }
    }

    /**
     * PUT /api/tutores/{id}
     * Actualiza la ocupación y relación de un tutor
     * {id} = id_Tutor (FK → usuario.id_usuario)
     */
    public function update(Request $request, int|string $id)
    {
        $validated = $request->validate([
            'id_ocupacion'          => 'required|exists:ocupacion,id_ocupacion',
            'relacion_estudiante'   => 'required|string|max:50',
            // id_alumno_relacionado agregado — igual que TutorController web
            'id_alumno_relacionado' => 'nullable|exists:usuario,id_usuario',
        ]);

        try {
            $updated = DB::table('tutor')
                ->where('id_Tutor', $id)
                ->update([
                    'id_ocupacion'          => $validated['id_ocupacion'],
                    'relacion_estudiante'   => $validated['relacion_estudiante'],
                    'id_alumno_relacionado' => $validated['id_alumno_relacionado'] ?? null,
                ]);

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tutor no encontrado.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tutor actualizado con éxito.',
            ]);

        } catch (\Exception $e) {
            Log::error('TutorApi@update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tutor.',
            ], 500);
        }
    }

    /**
     * GET /api/ocupaciones
     * Catálogo de ocupaciones (para llenar el select en la app al registrar tutor)
     */
    public function ocupaciones()
    {
        try {
            $ocupaciones = DB::table('ocupacion')
                ->orderBy('nombre_ocupacion', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $ocupaciones,
            ]);

        } catch (\Exception $e) {
            Log::error('TutorApi@ocupaciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las ocupaciones.',
            ], 500);
        }
    }
}