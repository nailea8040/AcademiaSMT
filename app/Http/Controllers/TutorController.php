<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TutorController
 *
 * Estructura real en BD:
 *   tutor:     id_Tutor (FK→usuario.id_usuario), id_ocupacion, relacion_estudiante
 *   ocupacion: id_ocupacion, nombre_ocupacion   ← SIN columna 'empresa'
 *   usuario:   rol='tutor' para identificar tutores
 *
 * NOTA: La columna 'ocupacion.empresa' NO existe en el SQL actual.
 *       Si se necesita en el futuro:
 *       ALTER TABLE ocupacion ADD COLUMN empresa VARCHAR(150) NULL;
 */
class TutorController extends Controller
{
    public function index()
    {
        try {
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

            // Usuarios con rol='tutor' que aún NO tienen perfil en tabla tutor
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

            // Catálogo de ocupaciones para el dropdown del formulario
            $ocupaciones = DB::table('ocupacion')
                ->orderBy('nombre_ocupacion', 'asc')
                ->get();

            return view('usuariosViews.tutor', compact(
                'tutores_registrados', 'usuarios_sin_perfil', 'ocupaciones'
            ));

        } catch (\Exception $e) {
            Log::error('TutorController@index: ' . $e->getMessage());
            return view('usuariosViews.tutor', [
                'tutores_registrados' => collect(),
                'usuarios_sin_perfil' => collect(),
                'ocupaciones'         => collect(),
            ])->with('error', 'Error al cargar datos.');
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_Tutor'            => 'required|exists:usuario,id_usuario|unique:tutor,id_Tutor',
            // id_ocupacion: el usuario selecciona del catálogo existente
            'id_ocupacion'        => 'required|exists:ocupacion,id_ocupacion',
            'relacion_estudiante' => 'required|string|max:50',
        ]);

        try {
            DB::table('tutor')->insert([
                'id_Tutor'            => $validated['id_Tutor'],
                'id_ocupacion'        => $validated['id_ocupacion'],
                'relacion_estudiante' => $validated['relacion_estudiante'],
            ]);

            return redirect()->route('tutor.index')
                ->with('success', '¡Tutor registrado con éxito!');

        } catch (\Exception $e) {
            Log::error('TutorController@store: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->withErrors(['db_error' => 'Error al registrar: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_ocupacion'        => 'required|exists:ocupacion,id_ocupacion',
            'relacion_estudiante' => 'required|string|max:50',
        ]);

        try {
            $updated = DB::table('tutor')
                ->where('id_Tutor', $id)
                ->update([
                    'id_ocupacion'        => $validated['id_ocupacion'],
                    'relacion_estudiante' => $validated['relacion_estudiante'],
                ]);

            return redirect()->route('tutor.index')->with(
                $updated ? 'success' : 'error',
                $updated ? '¡Tutor actualizado con éxito!' : 'No se encontró el tutor.'
            );

        } catch (\Exception $e) {
            Log::error('TutorController@update: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->withErrors(['db_error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }
}