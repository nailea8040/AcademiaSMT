<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ══════════════════════════════════════════════════════════════
 *  ESTRUCTURA BD:
 *  - Grado actual:      historial_grados ORDER BY fecha_obtencion DESC LIMIT 1
 *  - Documento médico:  registro_fisico.certificado_medico
 *  - Bachiller:         usuario.numero_control, grupo, especialidad, turno
 *  - Seminarios:        seminario (catálogo) + historial_seminarios (participaciones)
 *    NOTA: seminario.id_usuario fue eliminado; el catálogo ya no pertenece a un usuario.
 * ══════════════════════════════════════════════════════════════
 */
class AlumnoController extends Controller
{
    // ── index ─────────────────────────────────────────────────────────────────

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
                    'a.numero_control',
                    'a.grupo',
                    'a.especialidad',
                    'a.turno'
                )
                ->get();

            $grados = DB::table('grado')->orderBy('id_grado', 'asc')->get();

            $usuariosAlumno = DB::table('usuario')
                ->where('rol', 'alumno')
                ->where('estado', 1)
                ->whereNotIn('id_usuario', function ($q) {
                    $q->select('id_usuario')->from('registro_fisico');
                })
                ->select('id_usuario', DB::raw("CONCAT(nombre,' ',apaterno,' ',amaterno) AS nombre_completo"))
                ->orderBy('nombre')
                ->get();

            // Catálogo de seminarios para el selector del modal
            $seminarios = DB::table('seminario')
                ->orderBy('fecha', 'desc')
                ->select('id_seminario', 'nombre_seminario', 'fecha', 'maestro')
                ->get();

            return view('usuariosViews.alumno', compact(
                'alumnos_registrados', 'grados', 'usuariosAlumno', 'seminarios'
            ));

        } catch (\Exception $e) {
            Log::error('AlumnoController@index: ' . $e->getMessage());
            return view('usuariosViews.alumno', [
                'alumnos_registrados' => collect(),
                'grados'              => collect(),
                'usuariosAlumno'      => collect(),
                'seminarios'          => collect(),
            ])->with('error', 'Error al cargar datos: ' . $e->getMessage());
        }
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'id_alumno'         => 'required|exists:usuario,id_usuario',
            'id_grado'          => 'required|integer|exists:grado,id_grado',
            'fecha_inscripcion' => 'required|date',
            'documento_medico'  => 'required|file|mimes:pdf|max:5120',
            'peso'              => 'nullable|numeric|min:0|max:300',
            'estatura'          => 'nullable|numeric|min:0|max:3',
            'es_bachiller'      => 'nullable|boolean',
            'numero_control'    => 'nullable|string|max:20',
            'grupo'             => 'nullable|string|max:10',
            'especialidad'      => 'nullable|string|max:100',
            'turno'             => 'nullable|in:Matutino,Vespertino',
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

            DB::table('historial_grados')->insert([
                'id_usuario'      => $request->id_alumno,
                'id_grado'        => $request->id_grado,
                'fecha_obtencion' => $request->fecha_inscripcion,
                'observaciones'   => 'Grado inicial al momento de inscripción.',
            ]);

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

    // ── update ────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $request->validate([
            'id_grado'         => 'required|integer|exists:grado,id_grado',
            'fecha_obtencion'  => 'required|date',
            'observaciones'    => 'nullable|string|max:500',
            'documento_medico' => 'nullable|file|mimes:pdf|max:5120',
            'peso'             => 'nullable|numeric|min:0|max:300',
            'estatura'         => 'nullable|numeric|min:0|max:3',
            'es_bachiller'     => 'nullable|boolean',
            'numero_control'   => 'nullable|string|max:20',
            'grupo'            => 'nullable|string|max:10',
            'especialidad'     => 'nullable|string|max:100',
            'turno'            => 'nullable|in:Matutino,Vespertino',
        ]);

        try {
            $esBachiller = $request->boolean('es_bachiller');

            DB::beginTransaction();

            DB::table('historial_grados')->insert([
                'id_usuario'      => $id,
                'id_grado'        => $request->id_grado,
                'fecha_obtencion' => $request->fecha_obtencion,
                'observaciones'   => $request->observaciones ?? null,
            ]);

            $updateFisico = [];
            if ($request->hasFile('documento_medico')) {
                $nombreArchivo = 'medico_' . $id . '_' . time() . '.pdf';
                $updateFisico['certificado_medico'] = $request->file('documento_medico')
                    ->storeAs('documentos_medicos', $nombreArchivo, 'public');
            }
            if ($request->filled('peso'))     $updateFisico['peso']     = $request->peso;
            if ($request->filled('estatura')) $updateFisico['estatura'] = $request->estatura;

            if (!empty($updateFisico)) {
                DB::table('registro_fisico')->where('id_usuario', $id)->update($updateFisico);
            }

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

    // ── historialGrados ───────────────────────────────────────────────────────

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

    // ══════════════════════════════════════════════════════════════
    //  SEMINARIOS
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /seminarios
     * Lista el catálogo de seminarios (para admin/sensei).
     */
    public function seminarios()
    {
        try {
            $seminarios = DB::table('seminario')
                ->orderBy('fecha', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $seminarios]);

        } catch (\Exception $e) {
            Log::error('AlumnoController@seminarios: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener seminarios.'], 500);
        }
    }

    /**
     * POST /seminarios
     * Admin/sensei crea un nuevo seminario en el catálogo.
     * NOTA: seminario.id_usuario fue eliminado; no se inserta.
     */
    public function storeSeminario(Request $request)
    {
        $request->validate([
            'nombre_seminario' => 'required|string|max:150',
            'fecha'            => 'required|date',
            'maestro'          => 'required|string|max:150',
            'descripcion'      => 'nullable|string',
            'resultado'        => 'nullable|string|max:50',
        ]);

        try {
            $id = DB::table('seminario')->insertGetId([
                'nombre_seminario' => $request->nombre_seminario,
                'fecha'            => $request->fecha,
                'maestro'          => $request->maestro,
                'descripcion'      => $request->descripcion,
                'resultado'        => $request->resultado,
            ]);

            return redirect()->route('alumnos.index')
                ->with('success', 'Seminario creado con éxito.');

        } catch (\Exception $e) {
            Log::error('AlumnoController@storeSeminario: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al crear el seminario: ' . $e->getMessage());
        }
    }

    /**
     * PUT /seminarios/{id}
     * Admin/sensei actualiza un seminario del catálogo.
     */
    public function updateSeminario(Request $request, int $id)
    {
        $request->validate([
            'nombre_seminario' => 'required|string|max:150',
            'fecha'            => 'required|date',
            'maestro'          => 'required|string|max:150',
            'descripcion'      => 'nullable|string',
            'resultado'        => 'nullable|string|max:50',
        ]);

        try {
            $updated = DB::table('seminario')
                ->where('id_seminario', $id)
                ->update([
                    'nombre_seminario' => $request->nombre_seminario,
                    'fecha'            => $request->fecha,
                    'maestro'          => $request->maestro,
                    'descripcion'      => $request->descripcion,
                    'resultado'        => $request->resultado,
                ]);

            return redirect()->route('alumnos.index')->with(
                $updated ? 'success' : 'error',
                $updated ? 'Seminario actualizado.' : 'No se encontró el seminario.'
            );

        } catch (\Exception $e) {
            Log::error('AlumnoController@updateSeminario: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /seminarios/{id}
     * Solo admin elimina un seminario del catálogo.
     * También elimina las participaciones relacionadas en historial_seminarios.
     */
    public function destroySeminario(int $id)
    {
        try {
            DB::beginTransaction();
            // Primero eliminar participaciones (FK)
            DB::table('historial_seminarios')->where('id_seminario', $id)->delete();
            $deleted = DB::table('seminario')->where('id_seminario', $id)->delete();
            DB::commit();

            return redirect()->route('alumnos.index')->with(
                $deleted ? 'success' : 'error',
                $deleted ? 'Seminario eliminado.' : 'No se encontró el seminario.'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AlumnoController@destroySeminario: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumnos/{id}/historial-seminarios
     * Devuelve JSON con los seminarios de un alumno (para modal).
     * El alumno autenticado solo puede ver los suyos; admin/sensei ven cualquiera.
     */
    public function historialSeminarios(int $id)
    {
        try {
            // Si hay sesión activa verificar permiso
            if (auth()->check()) {
                $authUser = auth()->user();
                if ($authUser->rol === 'alumno' && (int) $authUser->id_usuario !== $id) {
                    return response()->json(['error' => 'Sin permiso.'], 403);
                }
            }

            $historial = DB::table('historial_seminarios as hs')
                ->join('seminario as s', 'hs.id_seminario', '=', 's.id_seminario')
                ->where('hs.id_usuario', $id)
                ->orderBy('s.fecha', 'desc')
                ->select(
                    'hs.id',
                    's.id_seminario',
                    's.nombre_seminario',
                    's.fecha',
                    's.maestro',
                    's.descripcion',
                    's.resultado',
                    'hs.fecha_participacion',
                    'hs.observaciones'
                )
                ->get();

            return response()->json($historial);

        } catch (\Exception $e) {
            Log::error('AlumnoController@historialSeminarios: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener historial.'], 500);
        }
    }

    /**
     * POST /alumnos/{id}/historial-seminarios
     * Admin/sensei vincula un alumno a un seminario existente.
     */
    public function storeHistorialSeminario(Request $request, int $id)
    {
        $request->validate([
            'id_seminario'       => 'required|integer|exists:seminario,id_seminario',
            'fecha_participacion' => 'required|date',
            'observaciones'      => 'nullable|string|max:500',
        ]);

        try {
            // Verificar que el alumno exista
            $alumno = DB::table('usuario')
                ->where('id_usuario', $id)
                ->where('rol', 'alumno')
                ->first();

            if (!$alumno) {
                return redirect()->back()->with('error', 'Alumno no encontrado.');
            }

            // Evitar duplicado en el mismo seminario
            $existe = DB::table('historial_seminarios')
                ->where('id_usuario', $id)
                ->where('id_seminario', $request->id_seminario)
                ->exists();

            if ($existe) {
                return redirect()->back()
                    ->with('error', 'Este alumno ya tiene registrada su participación en ese seminario.');
            }

            DB::table('historial_seminarios')->insert([
                'id_usuario'          => $id,
                'id_seminario'        => $request->id_seminario,
                'fecha_participacion' => $request->fecha_participacion,
                'observaciones'       => $request->observaciones,
            ]);

            return redirect()->route('alumnos.index')
                ->with('success', 'Participación en seminario registrada con éxito.');

        } catch (\Exception $e) {
            Log::error('AlumnoController@storeHistorialSeminario: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /alumnos/historial-seminarios/{id}
     * Admin/sensei elimina una participación específica del historial.
     * {id} = historial_seminarios.id (PK)
     */
    public function destroyHistorialSeminario(int $id)
    {
        try {
            $deleted = DB::table('historial_seminarios')->where('id', $id)->delete();

            return redirect()->route('alumnos.index')->with(
                $deleted ? 'success' : 'error',
                $deleted ? 'Participación eliminada.' : 'No se encontró el registro.'
            );

        } catch (\Exception $e) {
            Log::error('AlumnoController@destroyHistorialSeminario: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }
}