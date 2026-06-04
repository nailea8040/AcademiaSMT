<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TutorApiController extends Controller
{
    /**
     * GET /api/tutores
     * Lista todos los tutores con su información base + alumnos relacionados.
     */
    public function index(Request $request)
    {
        // Paginación: ?pagina=1&por_pagina=20 (por defecto 20 por página)
        $porPagina = min((int) $request->query('por_pagina', 20), 100);
        $pagina    = max((int) $request->query('pagina', 1), 1);
        $offset    = ($pagina - 1) * $porPagina;

        try {
            $baseQuery = DB::table('tutor as t')
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
                ->orderBy('u.apaterno', 'asc');

            $total        = (clone $baseQuery)->count();
            $tutores      = $baseQuery->limit($porPagina)->offset($offset)->get();
            $ultimaPagina = (int) ceil($total / $porPagina);

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

            return response()->json([
                'success' => true,
                'data'    => $tutores,
                'meta'    => [
                    'total'         => $total,
                    'por_pagina'    => $porPagina,
                    'pagina_actual' => $pagina,
                    'ultima_pagina' => $ultimaPagina,
                    'hay_mas'       => $pagina < $ultimaPagina,
                ],
            ]);

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

   public function alumnosRelacionados(Request $request)
    {
        $user = Auth::user();
 
        // Solo tutores tienen alumnos relacionados
        if ($user->rol !== 'tutor') {
            return response()->json(['success' => true, 'data' => []]);
        }
 
        try {
            $lista = DB::table('tutor_alumno as ta')
                ->join('usuario as a', 'ta.id_alumno', '=', 'a.id_usuario')
                ->where('ta.id_tutor', $user->id_usuario)
                ->where('a.estado', 1)         // solo alumnos activos
                ->select(
                    'ta.id_alumno',
                    'ta.relacion',
                    DB::raw("CONCAT(a.nombre,' ',a.apaterno,' ',a.amaterno) AS nombre_alumno")
                )
                ->orderBy('a.apaterno')
                ->get();
 
            return response()->json(['success' => true, 'data' => $lista]);
 
        } catch (\Exception $e) {
            Log::error('TutorApi@alumnosRelacionados: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener alumnos.'], 500);
        }
    }
    
}