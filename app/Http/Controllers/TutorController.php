<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TutorController extends Controller
{
    public function index()
    {
        try {
            // Tutores registrados con sus datos base
            $tutores_registrados = DB::table('tutor as t')
                ->join('usuario as u', 't.id_Tutor', '=', 'u.id_usuario')
                ->leftJoin('ocupacion as o', 't.id_ocupacion', '=', 'o.id_ocupacion')
                ->select(
                    't.id_Tutor',
                    't.relacion_estudiante',
                    'o.id_ocupacion',
                    'o.nombre_ocupacion AS ocupacion',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno,' ',u.amaterno) AS nombre_completo"),
                    'u.correo',
                    'u.telefono',
                    'u.estado'
                )
                ->get();

            // Para cada tutor, cargar sus alumnos relacionados desde tutor_alumno.
            // get() ya devuelve una Illuminate\Support\Collection, por lo que
            // ->count() y ->toJson() funcionan correctamente en el blade.
            foreach ($tutores_registrados as $tutor) {
                $tutor->alumnos_relacionados = DB::table('tutor_alumno as ta')
                    ->join('usuario as a', 'ta.id_alumno', '=', 'a.id_usuario')
                    ->where('ta.id_tutor', $tutor->id_Tutor)
                    ->select(
                        'ta.id_alumno',
                        'ta.relacion',
                        DB::raw("CONCAT(a.nombre,' ',a.apaterno,' ',a.amaterno) AS nombre_alumno")
                    )
                    ->get(); // Illuminate\Support\Collection ← tiene count() y toJson()
            }

            $usuarios_sin_perfil = DB::table('usuario as u')
                ->leftJoin('tutor as t', 'u.id_usuario', '=', 't.id_Tutor')
                ->where('u.rol', 'tutor')
                ->whereNull('t.id_Tutor')
                ->where('u.estado', 1)
                ->select(
                    'u.id_usuario AS id_Tutor',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_completo")
                )
                ->get();

            $ocupaciones = DB::table('ocupacion')
                ->orderBy('nombre_ocupacion', 'asc')
                ->get();

            $alumnos = DB::table('usuario')
                ->where('rol', 'alumno')
                ->where('estado', 1)
                ->select(
                    'id_usuario',
                    DB::raw("CONCAT(nombre,' ',apaterno,' ',amaterno) AS nombre_completo")
                )
                ->orderBy('nombre', 'asc')
                ->get();

            return view('usuariosViews.tutor', compact(
                'tutores_registrados', 'usuarios_sin_perfil', 'ocupaciones', 'alumnos'
            ));

        } catch (\Exception $e) {
            Log::error('TutorController@index: ' . $e->getMessage());
            return view('usuariosViews.tutor', [
                'tutores_registrados' => collect(),
                'usuarios_sin_perfil' => collect(),
                'ocupaciones'         => collect(),
                'alumnos'             => collect(),
            ])->with('error', 'Error al cargar datos.');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_Tutor'            => 'required|exists:usuario,id_usuario|unique:tutor,id_Tutor',
            'id_ocupacion'        => 'required|exists:ocupacion,id_ocupacion',
            'relacion_estudiante' => 'required|string|max:50',
            // Múltiples alumnos: array de {id_alumno, relacion}
            'alumnos'             => 'nullable|array',
            'alumnos.*.id_alumno' => 'required|exists:usuario,id_usuario',
            'alumnos.*.relacion'  => 'required|string|max:50',
        ]);

        try {
            DB::beginTransaction();

            DB::table('tutor')->insert([
                'id_Tutor'            => $validated['id_Tutor'],
                'id_ocupacion'        => $validated['id_ocupacion'],
                'relacion_estudiante' => $validated['relacion_estudiante'],
            ]);

            // Insertar relaciones tutor-alumno
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
            return redirect()->route('tutor.index')
                ->with('success', '¡Tutor registrado con éxito!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TutorController@store: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->withErrors(['db_error' => 'Error al registrar: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, int $id)
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

            // Verificar que el tutor existe ANTES de intentar actualizar
            $tutorExiste = DB::table('tutor')->where('id_Tutor', $id)->exists();
            if (!$tutorExiste) {
                DB::rollBack();
                return redirect()->route('tutor.index')
                    ->with('error', 'No se encontró el tutor.');
            }

            // update() devuelve filas afectadas — puede ser 0 si los datos no cambiaron,
            // lo cual es válido (el tutor existe pero los valores son idénticos).
            DB::table('tutor')
                ->where('id_Tutor', $id)
                ->update([
                    'id_ocupacion'        => $validated['id_ocupacion'],
                    'relacion_estudiante' => $validated['relacion_estudiante'],
                ]);

            // Reemplazar todas las relaciones tutor-alumno
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
            return redirect()->route('tutor.index')
                ->with('success', '¡Tutor actualizado con éxito!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TutorController@update: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->withErrors(['db_error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    /**
     * API: devuelve los alumnos relacionados de un tutor (para el modal de edición vía AJAX).
     */
    public function alumnosRelacionados(int $id)
    {
        $alumnos = DB::table('tutor_alumno as ta')
            ->join('usuario as a', 'ta.id_alumno', '=', 'a.id_usuario')
            ->where('ta.id_tutor', $id)
            ->select(
                'ta.id_alumno',
                'ta.relacion',
                DB::raw("CONCAT(a.nombre,' ',a.apaterno,' ',a.amaterno) AS nombre_alumno")
            )
            ->get();

        return response()->json($alumnos);
    }
}