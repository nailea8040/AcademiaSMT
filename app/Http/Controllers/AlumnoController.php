<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ══════════════════════════════════════════════════════════════
 *  ESTRUCTURA BD (sin columnas extra en usuario):
 *
 *  - Grado actual del alumno:
 *      historial_grados WHERE id_usuario = ? ORDER BY fecha_obtencion DESC LIMIT 1
 *
 *  - Relación alumno ↔ tutor:
 *      usuario.rol = 'alumno' o 'tutor'
 *      tabla tutor: id_Tutor (FK→usuario.id_usuario), id_ocupacion, relacion_estudiante
 *      No existe tabla intermedia alumno-tutor → la relación se gestiona
 *      en historial o se asigna al registrar (registro_fisico guarda la fecha de inscripción)
 *
 *  - Documento médico: registro_fisico.certificado_medico
 * ══════════════════════════════════════════════════════════════
 */
class AlumnoController extends Controller
{
    public function index()
    {
        try {
            // Grado actual: último registro en historial_grados por fecha
            $alumnos_registrados = DB::table('usuario as a')
                ->leftJoin('historial_grados as hg', function ($join) {
                    $join->on('hg.id_usuario', '=', 'a.id_usuario')
                         ->whereRaw('hg.fecha_obtencion = (
                             SELECT MAX(hg2.fecha_obtencion)
                             FROM historial_grados hg2
                             WHERE hg2.id_usuario = a.id_usuario
                         )');
                })
                ->leftJoin('grado as g', 'hg.id_grado', '=', 'g.id_grado')
                ->leftJoin('registro_fisico as rf', 'a.id_usuario', '=', 'rf.id_usuario')
                ->where('a.rol', 'alumno')
                ->select(
                    'a.id_usuario',
                    'a.estado',
                    'g.id_grado',
                    'g.nombreGrado',
                    DB::raw("CONCAT(a.nombre,' ',a.apaterno,' ',a.amaterno) AS nombre_alumno"),
                    'rf.certificado_medico',
                    'rf.fecha_registro AS fecha_inscripcion'
                )
                ->get();

            // Tutores: usuarios con rol='tutor' que tienen registro en tabla tutor
            $tutores = DB::table('tutor as t')
                ->join('usuario as u', 't.id_Tutor', '=', 'u.id_usuario')
                ->where('u.estado', 1)
                ->select(
                    't.id_Tutor',
                    DB::raw("CONCAT(u.nombre,' ',u.apaterno) AS nombre_completo"),
                    't.relacion_estudiante'
                )
                ->get();

            $grados = DB::table('grado')->orderBy('id_grado', 'asc')->get();

            return view('usuariosViews.alumno', compact(
                'alumnos_registrados', 'tutores', 'grados'
            ));

        } catch (\Exception $e) {
            Log::error('AlumnoController@index: ' . $e->getMessage());
            return view('usuariosViews.alumno', [
                'alumnos_registrados' => collect(),
                'tutores'             => collect(),
                'grados'              => collect(),
            ])->with('error', 'Error al cargar datos: ' . $e->getMessage());
        }
    }

    /**
     * Registrar alumno:
     *  - El usuario ya existe con rol='alumno' (creado desde UsuarioController o RegistroController)
     *  - Aquí se asigna: grado inicial (historial_grados) + documento médico (registro_fisico)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_alumno'        => 'required|exists:usuario,id_usuario',
            'id_grado'         => 'required|integer|exists:grado,id_grado',
            'fecha_inscripcion' => 'required|date',
            'documento_medico' => 'required|file|mimes:pdf|max:5120',
        ]);

        try {
            // Verificar que el usuario sea alumno
            $usuario = DB::table('usuario')
                ->where('id_usuario', $validated['id_alumno'])
                ->where('rol', 'alumno')
                ->first();

            if (!$usuario) {
                return redirect()->back()->with('error', 'El usuario seleccionado no tiene rol de alumno.');
            }

            $rutaDocumento = null;
            if ($request->hasFile('documento_medico')) {
                $archivo       = $request->file('documento_medico');
                $nombreArchivo = 'medico_' . $validated['id_alumno'] . '_' . time() . '.pdf';
                $rutaDocumento = $archivo->storeAs('documentos_medicos', $nombreArchivo, 'public');
            }

            DB::beginTransaction();

            // 1. Insertar grado inicial en historial_grados
            DB::table('historial_grados')->insert([
                'id_usuario'      => $validated['id_alumno'],
                'id_grado'        => $validated['id_grado'],
                'fecha_obtencion' => $validated['fecha_inscripcion'],
                'observaciones'   => 'Grado inicial al momento de inscripción.',
            ]);

            // 2. Guardar / actualizar certificado médico en registro_fisico
            $registroExistente = DB::table('registro_fisico')
                ->where('id_usuario', $validated['id_alumno'])
                ->first();

            if ($registroExistente) {
                DB::table('registro_fisico')
                    ->where('id_usuario', $validated['id_alumno'])
                    ->update([
                        'certificado_medico' => $rutaDocumento,
                        'fecha_registro'     => $validated['fecha_inscripcion'],
                    ]);
            } else {
                DB::table('registro_fisico')->insert([
                    'id_usuario'         => $validated['id_alumno'],
                    'peso'               => 0,
                    'estatura'           => 0,
                    'certificado_medico' => $rutaDocumento,
                    'fecha_registro'     => $validated['fecha_inscripcion'],
                ]);
            }

            DB::commit();
            return redirect()->route('alumnos.index')->with('success', 'Alumno registrado con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AlumnoController@store: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar alumno: asignar nuevo grado (agrega registro en historial)
     * y actualizar documento médico si se sube uno nuevo.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_grado'          => 'required|integer|exists:grado,id_grado',
            'fecha_obtencion'   => 'required|date',
            'observaciones'     => 'nullable|string|max:500',
            'documento_medico'  => 'nullable|file|mimes:pdf|max:5120',
        ]);

        try {
            DB::beginTransaction();

            // Insertar nuevo registro en historial_grados (no se sobreescribe el historial)
            DB::table('historial_grados')->insert([
                'id_usuario'      => $id,
                'id_grado'        => $validated['id_grado'],
                'fecha_obtencion' => $validated['fecha_obtencion'],
                'observaciones'   => $validated['observaciones'] ?? null,
            ]);

            // Actualizar documento médico si se proporcionó uno nuevo
            if ($request->hasFile('documento_medico')) {
                $nombreArchivo = 'medico_' . $id . '_' . time() . '.pdf';
                $ruta = $request->file('documento_medico')
                    ->storeAs('documentos_medicos', $nombreArchivo, 'public');

                DB::table('registro_fisico')
                    ->where('id_usuario', $id)
                    ->update(['certificado_medico' => $ruta]);
            }

            DB::commit();
            return redirect()->route('alumnos.index')->with('success', 'Alumno actualizado con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AlumnoController@update: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Devuelve el historial de grados de un alumno (para modal o vista detalle).
     */
    public function historialGrados($id)
    {
        try {
            $historial = DB::table('historial_grados as hg')
                ->join('grado as g', 'hg.id_grado', '=', 'g.id_grado')
                ->where('hg.id_usuario', $id)
                ->orderBy('hg.fecha_obtencion', 'desc')
                ->select('g.nombreGrado', 'g.orden', 'hg.fecha_obtencion', 'hg.observaciones')
                ->get();

            return response()->json($historial);

        } catch (\Exception $e) {
            Log::error('AlumnoController@historialGrados: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener historial.'], 500);
        }
    }
}