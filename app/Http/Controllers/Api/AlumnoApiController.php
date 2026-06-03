<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AlumnoApiController extends Controller
{
    // ── Supabase helpers ──────────────────────────────────────────────────────
    // CORRECCIÓN: los documentos médicos (PDFs) ahora se suben a Supabase Storage
    // en lugar del disco local de Railway, que es efímero.

    private function supabaseUrl(): string
    {
        return rtrim((string) config('services.supabase.url'), '/');
    }

    private function supabaseKey(): string
    {
        return (string) config('services.supabase.secret_key');
    }

    private function supabaseUploadPdf(\Illuminate\Http\UploadedFile $archivo, string $path): string
    {
        $contenido = file_get_contents($archivo->getRealPath());

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->supabaseKey(),
            'Content-Type'  => 'application/pdf',
        ])->withBody($contenido, 'application/pdf')
          ->post($this->supabaseUrl() . '/storage/v1/object/documentos/' . $path);

        if ($response->failed()) {
            throw new \Exception('Supabase upload error: ' . $response->body());
        }

        return $path;
    }
    /**
     * GET /api/alumnos
     *
     * FIX duplicados: se filtra por MAX(id) en historial_grados, no por
     * MAX(fecha_obtencion), porque si el alumno tiene varios registros el mismo
     * día el JOIN anterior devolvía una fila por cada uno.
     * El MAX(id) garantiza un único registro (el más reciente) por alumno.
     * El GROUP BY es red de seguridad adicional.
     *
     * También se agregan peso y estatura desde registro_fisico.
     */
    public function index(Request $request)
    {
        // Paginación: ?pagina=1&por_pagina=20 (por defecto 20 por página)
        $porPagina = min((int) $request->query('por_pagina', 20), 100);
        $pagina    = max((int) $request->query('pagina', 1), 1);
        $offset    = ($pagina - 1) * $porPagina;

        try {
            // Base query reutilizable para total y datos
            $baseQuery = DB::table('usuario as a')
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
                ->where('a.rol', 'alumno');

            $total = $baseQuery->count(DB::raw('DISTINCT a.id_usuario'));

            $alumnos = $baseQuery
                ->select(
                    'a.id_usuario',
                    'a.nombre',
                    'a.apaterno',
                    'a.amaterno',
                    'a.estado',
                    'a.correo',
                    'a.telefono',
                    'a.fecha_naci',
                    'a.numero_control',
                    'a.grupo',
                    'a.especialidad',
                    'a.turno',
                    'g.id_grado',
                    'g.nombreGrado',
                    DB::raw("CONCAT(a.nombre,' ',a.apaterno,' ',a.amaterno) AS nombre_completo"),
                    'rf.certificado_medico',
                    'rf.fecha_registro AS fecha_inscripcion',
                    'rf.peso',
                    'rf.estatura'
                )
                ->groupBy(
                    'a.id_usuario', 'a.nombre', 'a.apaterno', 'a.amaterno',
                    'a.estado', 'a.correo', 'a.telefono', 'a.fecha_naci',
                    'a.numero_control', 'a.grupo', 'a.especialidad', 'a.turno',
                    'g.id_grado', 'g.nombreGrado',
                    'rf.certificado_medico', 'rf.fecha_registro',
                    'rf.peso', 'rf.estatura'
                )
                ->orderBy('a.apaterno', 'asc')
                ->limit($porPagina)
                ->offset($offset)
                ->get()
                ->map(function ($a) {
                    $a->certificado_medico_url = $a->certificado_medico
                        ? asset('storage/' . $a->certificado_medico)
                        : null;
                    return $a;
                });

            $ultimaPagina = (int) ceil($total / $porPagina);

            return response()->json([
                'success' => true,
                'data'    => $alumnos,
                'meta'    => [
                    'total'         => $total,
                    'por_pagina'    => $porPagina,
                    'pagina_actual' => $pagina,
                    'ultima_pagina' => $ultimaPagina,
                    'hay_mas'       => $pagina < $ultimaPagina,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('AlumnoApi@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener alumnos.'], 500);
        }
    }

    /**
     * POST /api/alumnos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_alumno'         => 'required|exists:usuario,id_usuario',
            'id_grado'          => 'required|integer|exists:grado,id_grado',
            'fecha_inscripcion' => 'required|date',
            'documento_medico'  => 'nullable|file|mimes:pdf|max:5120',
            'peso'              => 'nullable|numeric|min:0|max:300',
            'estatura'          => 'nullable|numeric|min:0|max:3',
            // Bachiller
            'es_bachiller'      => 'nullable|boolean',
            'numero_control'    => 'nullable|string|max:20',
            'grupo'             => 'nullable|in:1A,1B,2A,2B,3A,3B,4A,4B,5A,5B,6A,6B',
            'especialidad'      => 'nullable|in:Análisis clínicos,Programación,Mecánica,Logística,Producción digital,Ciberseguridad,Soporte y mantenimiento',
            'turno'             => 'nullable|in:Matutino,Vespertino',
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

            // CORRECCIÓN: subir PDF a Supabase en lugar del disco local efímero
            $rutaDoc = null;
            if ($request->hasFile('documento_medico')) {
                $archivo = $request->file('documento_medico');
                // CORRECCIÓN: time() → Str::uuid() para evitar colisiones
                $nombre  = 'medico_' . $validated['id_alumno'] . '_' . Str::uuid() . '.pdf';
                $rutaDoc = $this->supabaseUploadPdf($archivo, $nombre);
            }

            $esBachiller = $request->boolean('es_bachiller');

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
                        'peso'               => $validated['peso']     ?? $existe->peso,
                        'estatura'           => $validated['estatura'] ?? $existe->estatura,
                    ]);
            } else {
                DB::table('registro_fisico')->insert([
                    'id_usuario'         => $validated['id_alumno'],
                    'peso'               => $validated['peso']     ?? 0,
                    'estatura'           => $validated['estatura'] ?? 0,
                    'certificado_medico' => $rutaDoc,
                    'fecha_registro'     => $validated['fecha_inscripcion'],
                ]);
            }

            // Guardar datos bachiller en tabla usuario
            DB::table('usuario')
                ->where('id_usuario', $validated['id_alumno'])
                ->update([
                    'numero_control' => $esBachiller ? ($request->numero_control ?? null) : null,
                    'grupo'          => $esBachiller ? ($request->grupo          ?? null) : null,
                    'especialidad'   => $esBachiller ? ($request->especialidad   ?? null) : null,
                    'turno'          => $esBachiller ? ($request->turno          ?? null) : null,
                ]);

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
     * Inserta nuevo grado en historial y actualiza datos físicos opcionales.
     * NO crea filas duplicadas en la lista porque index() filtra por MAX(id).
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'id_grado'         => 'required|integer|exists:grado,id_grado',
            'fecha_obtencion'  => 'required|date',
            'observaciones'    => 'nullable|string|max:500',
            'documento_medico' => 'nullable|file|mimes:pdf|max:5120',
            'peso'             => 'nullable|numeric|min:0|max:300',
            'estatura'         => 'nullable|numeric|min:0|max:3',
            // Bachiller
            'es_bachiller'     => 'nullable|boolean',
            'numero_control'   => 'nullable|string|max:20',
            'grupo'            => 'nullable|in:1A,1B,2A,2B,3A,3B,4A,4B,5A,5B,6A,6B',
            'especialidad'     => 'nullable|in:Análisis clínicos,Programación,Mecánica,Logística,Producción digital,Ciberseguridad,Soporte y mantenimiento',
            'turno'            => 'nullable|in:Matutino,Vespertino',
        ]);

        try {
            $esBachiller = $request->boolean('es_bachiller');

            DB::beginTransaction();

            // Solo inserta en historial — NO duplica en la lista
            // porque index() siempre trae solo el MAX(id) por alumno
            DB::table('historial_grados')->insert([
                'id_usuario'      => $id,
                'id_grado'        => $validated['id_grado'],
                'fecha_obtencion' => $validated['fecha_obtencion'],
                'observaciones'   => $validated['observaciones'] ?? null,
            ]);

            // Actualizar registro físico
            $updateFisico = [];

            // CORRECCIÓN: subir PDF a Supabase en lugar del disco local efímero
            if ($request->hasFile('documento_medico')) {
                // CORRECCIÓN: time() → Str::uuid() para evitar colisiones
                $nombre = 'medico_' . $id . '_' . Str::uuid() . '.pdf';
                $updateFisico['certificado_medico'] = $this->supabaseUploadPdf(
                    $request->file('documento_medico'),
                    $nombre
                );
            }

            if (!is_null($validated['peso'] ?? null)) {
                $updateFisico['peso'] = $validated['peso'];
            }
            if (!is_null($validated['estatura'] ?? null)) {
                $updateFisico['estatura'] = $validated['estatura'];
            }

            if (!empty($updateFisico)) {
                DB::table('registro_fisico')
                    ->where('id_usuario', $id)
                    ->update($updateFisico);
            }

            // Guardar datos bachiller en tabla usuario
            DB::table('usuario')
                ->where('id_usuario', $id)
                ->update([
                    'numero_control' => $esBachiller ? ($request->numero_control ?? null) : null,
                    'grupo'          => $esBachiller ? ($request->grupo          ?? null) : null,
                    'especialidad'   => $esBachiller ? ($request->especialidad   ?? null) : null,
                    'turno'          => $esBachiller ? ($request->turno          ?? null) : null,
                ]);

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
    public function historialGrados(int $id)
    {
        try {
            $historial = DB::table('historial_grados as hg')
                ->join('grado as g', 'hg.id_grado', '=', 'g.id_grado')
                ->where('hg.id_usuario', $id)
                ->orderBy('hg.id', 'desc')
                ->select('g.id_grado', 'g.nombreGrado', 'hg.fecha_obtencion', 'hg.observaciones')
                ->get();

            return response()->json(['success' => true, 'data' => $historial]);

        } catch (\Exception $e) {
            Log::error('AlumnoApi@historialGrados: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener historial.'], 500);
        }
    }

    /**
     * GET /api/alumnos/{id}/historial-seminarios
     *
     * Devuelve todos los seminarios en los que participó el alumno,
     * ordenados del más reciente al más antiguo.
     * Joins: historial_seminarios → seminario (catálogo).
     */
    public function historialSeminarios(int $id)
    {
        try {
            $historial = DB::table('historial_seminarios as hs')
                ->leftJoin('seminario as s', 'hs.id_seminario', '=', 's.id_seminario')
                ->where('hs.id_usuario', $id)
                ->orderBy('hs.id', 'desc')
                ->select(
                    'hs.id',
                    'hs.fecha_participacion',
                    'hs.observaciones',
                    's.id_seminario',
                    's.nombre_seminario',
                    's.fecha       AS fecha_seminario',
                    's.maestro',
                    's.descripcion AS descripcion_seminario',
                    's.resultado'
                )
                ->get();

            return response()->json(['success' => true, 'data' => $historial]);

        } catch (\Exception $e) {
            Log::error('AlumnoApi@historialSeminarios: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener historial de seminarios.'], 500);
        }
    }
}