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
     * Lista todos los tutores con su información base + alumnos relacionados.
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

            // Adjuntar alumnos relacionados desde tabla intermedia
            foreach ($tutores as $tutor) {
                $tutor->alumnos_relacionados = DB::table('tutor_alumno as ta')
                    ->join('usuario as a', 'ta.id_alumno', '=', 'a.id_usuario')
                    ->where('ta.id_tutor', $tutor->id_Tutor)
                    ->select(
                        'ta.id_alumno',
                        'ta.relacion',
                        DB::raw("CONCAT(a.nombre,' ',a.apaterno,' ',a.amaterno) AS nombre_alumno")
                    )
                    ->get();
            }

            return response()->json(['success' => true, 'data' => $tutores]);

        } catch (\Exception $e) {
            Log::error('TutorApi@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener los tutores.'], 500);
        }
    }

    /**
     * POST /api/tutores
     * Registra el perfil de tutor + sus alumnos relacionados (N alumnos).
     *
     * Body esperado:
     *   id_Tutor, id_ocupacion, relacion_estudiante
     *   alumnos[0][id_alumno], alumnos[0][relacion], alumnos[1][...], ...  (opcional)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_Tutor'            => 'required|exists:usuario,id_usuario|unique:tutor,id_Tutor',
            'id_ocupacion'        => 'required|exists:ocupacion,id_ocupacion',
            'relacion_estudiante' => 'required|string|max:50',
            'alumnos'             => 'nullable|array',
            'alumnos.*.id_alumno' => 'required|exists:usuario,id_usuario',
            'alumnos.*.relacion'  => 'required|string|max:50',
        ]);

        $usuario = DB::table('usuario')->where('id_usuario', $validated['id_Tutor'])->first();
        if (!$usuario || $usuario->rol !== 'tutor') {
            return response()->json(['success' => false, 'message' => 'El usuario debe tener rol de tutor.'], 422);
        }

        try {
            DB::beginTransaction();

            DB::table('tutor')->insert([
                'id_Tutor'            => $validated['id_Tutor'],
                'id_ocupacion'        => $validated['id_ocupacion'],
                'relacion_estudiante' => $validated['relacion_estudiante'],
            ]);

            if (!empty($validated['alumnos'])) {
                $rows = [];
                foreach ($validated['alumnos'] as $a) {
                    $rows[] = [
                        'id_tutor'   => $validated['id_Tutor'],
                        'id_alumno'  => $a['id_alumno'],
                        'relacion'   => $a['relacion'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('tutor_alumno')->insert($rows);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Tutor registrado con éxito.'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TutorApi@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar el tutor.'], 500);
        }
    }

    /**
     * PUT /api/tutores/{id}
     * Actualiza ocupación, relación general y reemplaza la lista de alumnos.
     *
     * Body esperado:
     *   id_ocupacion, relacion_estudiante
     *   alumnos[0][id_alumno], alumnos[0][relacion], ...  (opcional — enviar [] para borrar todos)
     */
    public function update(Request $request, int|string $id)
    {
        $validated = $request->validate([
            'id_ocupacion'        => 'required|exists:ocupacion,id_ocupacion',
            'relacion_estudiante' => 'required|string|max:50',
            'alumnos'             => 'nullable|array',
            'alumnos.*.id_alumno' => 'required|exists:usuario,id_usuario',
            'alumnos.*.relacion'  => 'required|string|max:50',
        ]);

        try {
            DB::beginTransaction();

            $updated = DB::table('tutor')
                ->where('id_Tutor', $id)
                ->update([
                    'id_ocupacion'        => $validated['id_ocupacion'],
                    'relacion_estudiante' => $validated['relacion_estudiante'],
                ]);

            if (!$updated) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Tutor no encontrado.'], 404);
            }

            // Reemplazar relaciones alumno
            DB::table('tutor_alumno')->where('id_tutor', $id)->delete();

            if (!empty($validated['alumnos'])) {
                $rows = [];
                foreach ($validated['alumnos'] as $a) {
                    $rows[] = [
                        'id_tutor'   => $id,
                        'id_alumno'  => $a['id_alumno'],
                        'relacion'   => $a['relacion'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('tutor_alumno')->insert($rows);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Tutor actualizado con éxito.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TutorApi@update: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar el tutor.'], 500);
        }
    }

    /**
     * GET /api/tutores/{id}/alumnos
     * Devuelve los alumnos relacionados de un tutor específico.
     */
    public function alumnosRelacionados(int $id)
    {
        try {
            $alumnos = DB::table('tutor_alumno as ta')
                ->join('usuario as a', 'ta.id_alumno', '=', 'a.id_usuario')
                ->where('ta.id_tutor', $id)
                ->select(
                    'ta.id_alumno',
                    'ta.relacion',
                    DB::raw("CONCAT(a.nombre,' ',a.apaterno,' ',a.amaterno) AS nombre_alumno")
                )
                ->get();

            return response()->json(['success' => true, 'data' => $alumnos]);

        } catch (\Exception $e) {
            Log::error('TutorApi@alumnosRelacionados: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener los alumnos.'], 500);
        }
    }

    /**
     * GET /api/ocupaciones
     */
    public function ocupaciones()
    {
        try {
            $ocupaciones = DB::table('ocupacion')
                ->orderBy('nombre_ocupacion', 'asc')
                ->get();

            return response()->json(['success' => true, 'data' => $ocupaciones]);

        } catch (\Exception $e) {
            Log::error('TutorApi@ocupaciones: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener las ocupaciones.'], 500);
        }
    }
}