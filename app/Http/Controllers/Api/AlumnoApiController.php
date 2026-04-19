<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlumnoApiController extends Controller
{
    /**
     * GET /api/alumnos
     */
    public function index(Request $request)
    {
        try {
            $alumnos = DB::table('usuario as a')
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
                    'a.nombre',
                    'a.apaterno',
                    'a.amaterno',
                    'a.estado',
                    'a.correo',
                    'a.telefono',
                    'a.fecha_naci',
                    'g.id_grado',
                    'g.nombreGrado',
                    DB::raw("CONCAT(a.nombre,' ',a.apaterno,' ',a.amaterno) AS nombre_completo"),
                    'rf.certificado_medico',
                    'rf.fecha_registro AS fecha_inscripcion'
                )
                ->get()
                ->map(function ($a) {
                    // Añadir URL pública del certificado si existe
                    $a->certificado_medico_url = $a->certificado_medico
                        ? asset('storage/' . $a->certificado_medico)
                        : null;
                    return $a;
                });

            return response()->json(['success' => true, 'data' => $alumnos]);

        } catch (\Exception $e) {
            Log::error('AlumnoApi@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener alumnos.'], 500);
        }
    }

    /**
     * POST /api/alumnos
     * multipart/form-data porque incluye PDF
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_alumno'         => 'required|exists:usuario,id_usuario',
            'id_grado'          => 'required|integer|exists:grado,id_grado',
            'fecha_inscripcion' => 'required|date',
            'documento_medico'  => 'required|file|mimes:pdf|max:5120',
        ]);

        try {
            $usuario = DB::table('usuario')
                ->where('id_usuario', $validated['id_alumno'])
                ->where('rol', 'alumno')
                ->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no tiene rol de alumno.',
                ], 422);
            }

            $rutaDoc = null;
            if ($request->hasFile('documento_medico')) {
                $archivo  = $request->file('documento_medico');
                $nombre   = 'medico_' . $validated['id_alumno'] . '_' . time() . '.pdf';
                $rutaDoc  = $archivo->storeAs('documentos_medicos', $nombre, 'public');
            }

            DB::beginTransaction();

            DB::table('historial_grados')->insert([
                'id_usuario'      => $validated['id_alumno'],
                'id_grado'        => $validated['id_grado'],
                'fecha_obtencion' => $validated['fecha_inscripcion'],
                'observaciones'   => 'Grado inicial al momento de inscripción.',
            ]);

            $existe = DB::table('registro_fisico')
                ->where('id_usuario', $validated['id_alumno'])
                ->first();

            if ($existe) {
                DB::table('registro_fisico')
                    ->where('id_usuario', $validated['id_alumno'])
                    ->update([
                        'certificado_medico' => $rutaDoc,
                        'fecha_registro'     => $validated['fecha_inscripcion'],
                    ]);
            } else {
                DB::table('registro_fisico')->insert([
                    'id_usuario'         => $validated['id_alumno'],
                    'peso'               => 0,
                    'estatura'           => 0,
                    'certificado_medico' => $rutaDoc,
                    'fecha_registro'     => $validated['fecha_inscripcion'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Alumno registrado con éxito.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AlumnoApi@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar.'], 500);
        }
    }

    /**
     * PUT /api/alumnos/{id}
     * Asigna nuevo grado y opcionalmente nuevo documento médico
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_grado'         => 'required|integer|exists:grado,id_grado',
            'fecha_obtencion'  => 'required|date',
            'observaciones'    => 'nullable|string|max:500',
            'documento_medico' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        try {
            DB::beginTransaction();

            DB::table('historial_grados')->insert([
                'id_usuario'      => $id,
                'id_grado'        => $validated['id_grado'],
                'fecha_obtencion' => $validated['fecha_obtencion'],
                'observaciones'   => $validated['observaciones'] ?? null,
            ]);

            if ($request->hasFile('documento_medico')) {
                $nombre = 'medico_' . $id . '_' . time() . '.pdf';
                $ruta   = $request->file('documento_medico')
                    ->storeAs('documentos_medicos', $nombre, 'public');

                DB::table('registro_fisico')
                    ->where('id_usuario', $id)
                    ->update(['certificado_medico' => $ruta]);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Alumno actualizado.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AlumnoApi@update: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar.'], 500);
        }
    }

    /**
     * GET /api/alumnos/{id}/historial-grados
     */
    public function historialGrados($id)
    {
        try {
            $historial = DB::table('historial_grados as hg')
                ->join('grado as g', 'hg.id_grado', '=', 'g.id_grado')
                ->where('hg.id_usuario', $id)
                ->orderBy('hg.fecha_obtencion', 'desc')
                ->select('g.id_grado', 'g.nombreGrado', 'hg.fecha_obtencion', 'hg.observaciones')
                ->get();

            return response()->json(['success' => true, 'data' => $historial]);

        } catch (\Exception $e) {
            Log::error('AlumnoApi@historialGrados: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener historial.'], 500);
        }
    }
}