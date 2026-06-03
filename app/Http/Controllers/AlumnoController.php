<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * FIX duplicados en index():
 *   Se reemplazó MAX(fecha_obtencion) por MAX(id) en el subquery de historial_grados.
 *   Si un alumno tenía varios registros el mismo día, el JOIN anterior devolvía
 *   múltiples filas. Con MAX(id) solo se trae el registro más reciente.
 *   Se agrega GROUP BY como red de seguridad adicional.
 */
class AlumnoController extends Controller
{
    public function index()
    {
        try {
            $alumnos_registrados = DB::table('usuario as a')
                ->leftJoin('historial_grados as hg', function ($join) {
                    $join->on('hg.id_usuario', '=', 'a.id_usuario')
                         ->whereRaw('hg.id = (
                             SELECT MAX(hg2.id)
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
                ->groupBy(
                    'a.id_usuario', 'a.estado', 'g.id_grado', 'g.nombreGrado',
                    'rf.certificado_medico', 'rf.peso', 'rf.estatura', 'rf.fecha_registro',
                    'a.numero_control', 'a.grupo', 'a.especialidad', 'a.turno',
                    'a.nombre', 'a.apaterno', 'a.amaterno'
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
            'grupo'             => 'nullable|in:1A,1B,2A,2B,3A,3B,4A,4B,5A,5B,6A,6B',
            'especialidad'      => 'nullable|in:Análisis clínicos,Programación,Mecánica,Logística,Producción digital,Ciberseguridad,Soporte y mantenimiento',
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
            'grupo'            => 'nullable|in:1A,1B,2A,2B,3A,3B,4A,4B,5A,5B,6A,6B',
            'especialidad'     => 'nullable|in:Análisis clínicos,Programación,Mecánica,Logística,Producción digital,Ciberseguridad,Soporte y mantenimiento',
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

    public function historialGrados(int $id)
    {
        try {
            $historial = DB::table('historial_grados as hg')
                ->join('grado as g', 'hg.id_grado', '=', 'g.id_grado')
                ->where('hg.id_usuario', $id)
                ->orderBy('hg.id', 'desc')
                ->select('g.nombreGrado', 'g.orden', 'hg.fecha_obtencion', 'hg.observaciones')
                ->get();

            return response()->json($historial);

        } catch (\Exception $e) {
            Log::error('AlumnoController@historialGrados: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener historial.'], 500);
        }
    }

    public function seminarios()
    {
        try {
            $seminarios = DB::table('seminario')->orderBy('fecha', 'desc')->get();
            return response()->json(['success' => true, 'data' => $seminarios]);
        } catch (\Exception $e) {
            Log::error('AlumnoController@seminarios: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener seminarios.'], 500);
        }
    }

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
                'descripcion'      => $request->descripcion ?? null,
                'resultado'        => $request->resultado   ?? null,
            ]);

            $seminario = DB::table('seminario')->where('id_seminario', $id)->first();

            return response()->json(['success' => true, 'message' => 'Seminario creado.', 'seminario' => $seminario]);

        } catch (\Exception $e) {
            Log::error('AlumnoController@storeSeminario: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

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
            $updated = DB::table('seminario')->where('id_seminario', $id)->update([
                'nombre_seminario' => $request->nombre_seminario,
                'fecha'            => $request->fecha,
                'maestro'          => $request->maestro,
                'descripcion'      => $request->descripcion ?? null,
                'resultado'        => $request->resultado   ?? null,
            ]);

            if (!$updated) return response()->json(['success' => false, 'message' => 'No encontrado.'], 404);

            $seminario = DB::table('seminario')->where('id_seminario', $id)->first();
            return response()->json(['success' => true, 'message' => 'Seminario actualizado.', 'seminario' => $seminario]);

        } catch (\Exception $e) {
            Log::error('AlumnoController@updateSeminario: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroySeminario(int $id)
    {
        try {
            DB::beginTransaction();
            DB::table('historial_seminarios')->where('id_seminario', $id)->delete();
            $deleted = DB::table('seminario')->where('id_seminario', $id)->delete();
            DB::commit();
            if (!$deleted) return response()->json(['success' => false, 'message' => 'No encontrado.'], 404);
            return response()->json(['success' => true, 'message' => 'Seminario eliminado.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AlumnoController@destroySeminario: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function storeGrado(Request $request)
    {
        $request->validate([
            'nombreGrado' => 'required|string|max:100|unique:grado,nombreGrado',
            'orden'       => 'required|integer|min:1|unique:grado,orden',
        ], [
            'nombreGrado.unique' => 'Ya existe un grado con ese nombre.',
            'orden.unique'       => 'Ya existe un grado con ese número de orden.',
        ]);

        try {
            $id    = DB::table('grado')->insertGetId(['nombreGrado' => $request->nombreGrado, 'orden' => $request->orden]);
            $grado = DB::table('grado')->where('id_grado', $id)->first();
            return response()->json(['success' => true, 'message' => 'Grado creado.', 'grado' => $grado]);
        } catch (\Exception $e) {
            Log::error('AlumnoController@storeGrado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function updateGrado(Request $request, int $id)
    {
        $request->validate([
            'nombreGrado' => 'required|string|max:100|unique:grado,nombreGrado,' . $id . ',id_grado',
            'orden'       => 'required|integer|min:1|unique:grado,orden,' . $id . ',id_grado',
        ], [
            'nombreGrado.unique' => 'Ya existe un grado con ese nombre.',
            'orden.unique'       => 'Ya existe un grado con ese número de orden.',
        ]);

        try {
            $updated = DB::table('grado')->where('id_grado', $id)->update(['nombreGrado' => $request->nombreGrado, 'orden' => $request->orden]);
            if (!$updated) return response()->json(['success' => false, 'message' => 'No encontrado.'], 404);
            $grado = DB::table('grado')->where('id_grado', $id)->first();
            return response()->json(['success' => true, 'message' => 'Grado actualizado.', 'grado' => $grado]);
        } catch (\Exception $e) {
            Log::error('AlumnoController@updateGrado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function historialSeminarios(int $id)
    {
        try {
            if (Auth::check()) {
                $authUser = Auth::user();
                if ($authUser->rol === 'alumno' && (int) $authUser->id_usuario !== $id) {
                    return response()->json(['error' => 'Sin permiso.'], 403);
                }
            }

            $historial = DB::table('historial_seminarios as hs')
                ->join('seminario as s', 'hs.id_seminario', '=', 's.id_seminario')
                ->where('hs.id_usuario', $id)
                ->orderBy('s.fecha', 'desc')
                ->select('hs.id', 's.id_seminario', 's.nombre_seminario', 's.fecha', 's.maestro', 's.descripcion', 's.resultado', 'hs.fecha_participacion', 'hs.observaciones')
                ->get();

            return response()->json($historial);

        } catch (\Exception $e) {
            Log::error('AlumnoController@historialSeminarios: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener historial.'], 500);
        }
    }

    public function storeHistorialSeminario(Request $request, int $id)
    {
        $modo  = $request->input('modo', 'existente');
        $rules = [
            'modo'                => 'required|in:existente,nuevo',
            'fecha_participacion' => 'required|date',
            'observaciones'       => 'nullable|string|max:500',
        ];
        if ($modo === 'existente') {
            $rules['id_seminario'] = 'required|integer|exists:seminario,id_seminario';
        } else {
            $rules['nombre_seminario'] = 'required|string|max:150';
            $rules['fecha_seminario']  = 'required|date';
            $rules['maestro']          = 'required|string|max:150';
            $rules['descripcion']      = 'nullable|string';
            $rules['resultado']        = 'nullable|string|max:50';
        }

        try {
            $validated = $request->validate($rules);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos.', 'errors' => $e->errors()], 422);
        }

        try {
            $alumno = DB::table('usuario')->where('id_usuario', $id)->where('rol', 'alumno')->first();
            if (!$alumno) return response()->json(['success' => false, 'message' => 'Alumno no encontrado.'], 404);

            DB::beginTransaction();

            $idSeminario    = null;
            $seminarioNuevo = null;

            if ($modo === 'nuevo') {
                $idSeminario = DB::table('seminario')->insertGetId([
                    'nombre_seminario' => $validated['nombre_seminario'],
                    'fecha'            => $validated['fecha_seminario'],
                    'maestro'          => $validated['maestro'],
                    'descripcion'      => $validated['descripcion'] ?? null,
                    'resultado'        => $validated['resultado']   ?? null,
                ]);
                $seminarioNuevo = DB::table('seminario')->where('id_seminario', $idSeminario)->first();
            } else {
                $idSeminario = $validated['id_seminario'];
            }

            $existe = DB::table('historial_seminarios')->where('id_usuario', $id)->where('id_seminario', $idSeminario)->exists();
            if ($existe) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Este alumno ya tiene registrada su participación en ese seminario.'], 409);
            }

            $nuevoId = DB::table('historial_seminarios')->insertGetId([
                'id_usuario'          => $id,
                'id_seminario'        => $idSeminario,
                'fecha_participacion' => $validated['fecha_participacion'],
                'observaciones'       => $validated['observaciones'] ?? null,
            ]);

            DB::commit();

            $registro = DB::table('historial_seminarios as hs')
                ->join('seminario as s', 'hs.id_seminario', '=', 's.id_seminario')
                ->where('hs.id', $nuevoId)
                ->select('hs.id', 's.id_seminario', 's.nombre_seminario', 's.fecha', 's.maestro', 's.descripcion', 's.resultado', 'hs.fecha_participacion', 'hs.observaciones')
                ->first();

            return response()->json(['success' => true, 'message' => 'Participación registrada.', 'registro' => $registro, 'seminario_nuevo' => $seminarioNuevo], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AlumnoController@storeHistorialSeminario: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroyHistorialSeminario(int $id)
    {
        try {
            $deleted = DB::table('historial_seminarios')->where('id', $id)->delete();
            if (!$deleted) return response()->json(['success' => false, 'message' => 'No encontrado.'], 404);
            return response()->json(['success' => true, 'message' => 'Participación eliminada.']);
        } catch (\Exception $e) {
            Log::error('AlumnoController@destroyHistorialSeminario: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}