<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ══════════════════════════════════════════════════════════════
 *  ESTRUCTURA BD:
 *  - Grado actual: historial_grados ORDER BY fecha_obtencion DESC LIMIT 1
 *  - Documento médico: registro_fisico.certificado_medico
 *  - Datos de bachiller: usuario.numero_control, grupo, especialidad, turno
 *    (no todos los alumnos pertenecen al bachiller)
 * ══════════════════════════════════════════════════════════════
 */
class AlumnoController extends Controller
{
    public function index()
    {
        try {
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
                    'rf.peso',
                    'rf.estatura',
                    'rf.fecha_registro AS fecha_inscripcion',
                    // Datos de bachiller
                    'a.numero_control',
                    'a.grupo',
                    'a.especialidad',
                    'a.turno'
                )
                ->get();

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

            // Usuarios con rol alumno sin registro previo en historial_grados
            // (disponibles para registrar por primera vez)
            $usuariosAlumno = DB::table('usuario')
                ->where('rol', 'alumno')
                ->where('estado', 1)
                ->whereNotIn('id_usuario', function ($q) {
                    $q->select('id_usuario')->from('registro_fisico');
                })
                ->select('id_usuario', DB::raw("CONCAT(nombre,' ',apaterno,' ',amaterno) AS nombre_completo"))
                ->orderBy('nombre')
                ->get();

            return view('usuariosViews.alumno', compact(
                'alumnos_registrados', 'tutores', 'grados', 'usuariosAlumno'
            ));

        } catch (\Exception $e) {
            Log::error('AlumnoController@index: ' . $e->getMessage());
            return view('usuariosViews.alumno', [
                'alumnos_registrados' => collect(),
                'tutores'             => collect(),
                'grados'              => collect(),
                'usuariosAlumno'      => collect(),
            ])->with('error', 'Error al cargar datos: ' . $e->getMessage());
        }
    }

    /**
     * Registrar alumno:
     * - Asigna grado inicial (historial_grados)
     * - Guarda documento médico (registro_fisico)
     * - Si pertenece al bachiller, guarda datos en tabla usuario
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_alumno'         => 'required|exists:usuario,id_usuario',
            'id_grado'          => 'required|integer|exists:grado,id_grado',
            'fecha_inscripcion' => 'required|date',
            'documento_medico'  => 'required|file|mimes:pdf|max:5120',
            // Físicos — opcionales al registrar (0 si no se conocen aún)
            'peso'              => 'nullable|numeric|min:0|max:300',
            'estatura'          => 'nullable|numeric|min:0|max:3',
            // Bachiller — opcionales
            'es_bachiller'      => 'nullable|boolean',
            'numero_control'    => 'nullable|string|max:20',
            'grupo'             => 'nullable|string|max:10',
            'especialidad'      => 'nullable|string|max:100',
            'turno'             => 'nullable|in:Matutino,Vespertino,Nocturno',
        ]);

        try {
            $usuario = DB::table('usuario')
                ->where('id_usuario', $request->id_alumno)
                ->where('rol', 'alumno')
                ->first();

            if (!$usuario) {
                return redirect()->back()->with('error', 'El usuario seleccionado no tiene rol de alumno.');
            }

            $rutaDocumento = null;
            if ($request->hasFile('documento_medico')) {
                $archivo       = $request->file('documento_medico');
                $nombreArchivo = 'medico_' . $request->id_alumno . '_' . time() . '.pdf';
                $rutaDocumento = $archivo->storeAs('documentos_medicos', $nombreArchivo, 'public');
            }

            $esBachiller = $request->boolean('es_bachiller');

            DB::beginTransaction();

            // 1. Grado inicial en historial_grados
            DB::table('historial_grados')->insert([
                'id_usuario'      => $request->id_alumno,
                'id_grado'        => $request->id_grado,
                'fecha_obtencion' => $request->fecha_inscripcion,
                'observaciones'   => 'Grado inicial al momento de inscripción.',
            ]);

            // 2. Certificado médico en registro_fisico
            $registroExistente = DB::table('registro_fisico')
                ->where('id_usuario', $request->id_alumno)
                ->first();

            if ($registroExistente) {
                DB::table('registro_fisico')
                    ->where('id_usuario', $request->id_alumno)
                    ->update([
                        'peso'               => $request->filled('peso')     ? $request->peso     : $registroExistente->peso,
                        'estatura'           => $request->filled('estatura') ? $request->estatura : $registroExistente->estatura,
                        'certificado_medico' => $rutaDocumento,
                        'fecha_registro'     => $request->fecha_inscripcion,
                    ]);
            } else {
                DB::table('registro_fisico')->insert([
                    'id_usuario'         => $request->id_alumno,
                    'peso'               => $request->filled('peso')     ? $request->peso     : 0,
                    'estatura'           => $request->filled('estatura') ? $request->estatura : 0,
                    'certificado_medico' => $rutaDocumento,
                    'fecha_registro'     => $request->fecha_inscripcion,
                ]);
            }

            // 3. Datos de bachiller en tabla usuario
            DB::table('usuario')
                ->where('id_usuario', $request->id_alumno)
                ->update([
                    'numero_control' => $esBachiller ? ($request->numero_control ?? null) : null,
                    'grupo'          => $esBachiller ? ($request->grupo          ?? null) : null,
                    'especialidad'   => $esBachiller ? ($request->especialidad   ?? null) : null,
                    'turno'          => $esBachiller ? ($request->turno          ?? null) : null,
                ]);

            DB::commit();
            return redirect()->route('alumnos.index')->with('success', 'Alumno registrado con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AlumnoController@store: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar alumno:
     * - Agrega nuevo grado al historial
     * - Actualiza documento médico si se sube uno nuevo
     * - Actualiza datos de bachiller (o los limpia si ya no pertenece)
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'id_grado'          => 'required|integer|exists:grado,id_grado',
            'fecha_obtencion'   => 'required|date',
            'observaciones'     => 'nullable|string|max:500',
            'documento_medico'  => 'nullable|file|mimes:pdf|max:5120',
            // Físicos — opcionales
            'peso'              => 'nullable|numeric|min:0|max:300',
            'estatura'          => 'nullable|numeric|min:0|max:3',
            // Bachiller — opcionales
            'es_bachiller'      => 'nullable|boolean',
            'numero_control'    => 'nullable|string|max:20',
            'grupo'             => 'nullable|string|max:10',
            'especialidad'      => 'nullable|string|max:100',
            'turno'             => 'nullable|in:Matutino,Vespertino,Nocturno',
        ]);

        try {
            $esBachiller = $request->boolean('es_bachiller');

            DB::beginTransaction();

            // 1. Nuevo registro en historial_grados
            DB::table('historial_grados')->insert([
                'id_usuario'      => $id,
                'id_grado'        => $request->id_grado,
                'fecha_obtencion' => $request->fecha_obtencion,
                'observaciones'   => $request->observaciones ?? null,
            ]);

            // 2. Documento médico y datos físicos
            $updateFisico = [];
            if ($request->hasFile('documento_medico')) {
                $nombreArchivo = 'medico_' . $id . '_' . time() . '.pdf';
                $updateFisico['certificado_medico'] = $request->file('documento_medico')
                    ->storeAs('documentos_medicos', $nombreArchivo, 'public');
            }
            if ($request->filled('peso'))     $updateFisico['peso']     = $request->peso;
            if ($request->filled('estatura')) $updateFisico['estatura'] = $request->estatura;

            if (!empty($updateFisico)) {
                DB::table('registro_fisico')
                    ->where('id_usuario', $id)
                    ->update($updateFisico);
            }

            // 3. Datos de bachiller:
            //    - Si es_bachiller = true  → actualiza campos
            //    - Si es_bachiller = false → pone NULL (ya no pertenece)
            DB::table('usuario')
                ->where('id_usuario', $id)
                ->update([
                    'numero_control' => $esBachiller ? ($request->numero_control ?? null) : null,
                    'grupo'          => $esBachiller ? ($request->grupo          ?? null) : null,
                    'especialidad'   => $esBachiller ? ($request->especialidad   ?? null) : null,
                    'turno'          => $esBachiller ? ($request->turno          ?? null) : null,
                ]);

            DB::commit();
            return redirect()->route('alumnos.index')->with('success', 'Alumno actualizado con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AlumnoController@update: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Historial de grados para modal (devuelve JSON).
     */
    public function historialGrados(int $id)
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