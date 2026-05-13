<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SeminarioApiController
 *
 * Rutas sugeridas en routes/api.php:
 *
 *   // Catálogo de seminarios (admin/sensei)
 *   Route::get   ('/seminarios',                      [SeminarioApiController::class, 'index']);
 *   Route::post  ('/seminarios',                      [SeminarioApiController::class, 'store']);
 *   Route::put   ('/seminarios/{id}',                 [SeminarioApiController::class, 'update']);
 *   Route::delete('/seminarios/{id}',                 [SeminarioApiController::class, 'destroy']);
 *
 *   // Historial de participaciones por alumno
 *   Route::get   ('/alumnos/{id}/historial-seminarios',  [SeminarioApiController::class, 'historialSeminarios']);
 *   Route::post  ('/alumnos/{id}/historial-seminarios',  [SeminarioApiController::class, 'storeHistorial']);
 *   Route::delete('/historial-seminarios/{id}',          [SeminarioApiController::class, 'destroyHistorial']);
 *
 * NOTA: seminario.id_usuario fue eliminado; el catálogo ya no pertenece a un usuario.
 */
class SeminarioApiController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    //  CATÁLOGO DE SEMINARIOS
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /api/seminarios
     * Devuelve el catálogo completo de seminarios.
     * Requiere auth (cualquier rol puede verlo para mostrar el selector).
     */
    public function index()
    {
        try {
            $seminarios = DB::table('seminario')
                ->orderBy('fecha', 'desc')
                ->select('id_seminario', 'nombre_seminario', 'fecha', 'maestro', 'descripcion', 'resultado')
                ->get();

            return response()->json(['success' => true, 'data' => $seminarios]);

        } catch (\Exception $e) {
            Log::error('SeminarioApi@index: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener seminarios.'], 500);
        }
    }

    /**
     * POST /api/seminarios
     * Admin/sensei crea un seminario en el catálogo.
     *
     * Body JSON:
     * {
     *   "nombre_seminario": "Seminario Kihon 2025",
     *   "fecha":            "2025-08-15",
     *   "maestro":          "Sensei Ramírez",
     *   "descripcion":      "Fundamentos avanzados",   (opcional)
     *   "resultado":        "Aprobado"                 (opcional)
     * }
     */
    public function store(Request $request)
    {
        $this->soloAdminOSensei($request);

        $validated = $request->validate([
            'nombre_seminario' => 'required|string|max:150',
            'fecha'            => 'required|date',
            'maestro'          => 'required|string|max:150',
            'descripcion'      => 'nullable|string',
            'resultado'        => 'nullable|string|max:50',
        ]);

        try {
            $id = DB::table('seminario')->insertGetId([
                'nombre_seminario' => $validated['nombre_seminario'],
                'fecha'            => $validated['fecha'],
                'maestro'          => $validated['maestro'],
                'descripcion'      => $validated['descripcion'] ?? null,
                'resultado'        => $validated['resultado']   ?? null,
            ]);

            $seminario = DB::table('seminario')->where('id_seminario', $id)->first();

            return response()->json([
                'success'   => true,
                'message'   => 'Seminario creado con éxito.',
                'seminario' => $seminario,
            ], 201);

        } catch (\Exception $e) {
            Log::error('SeminarioApi@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al crear el seminario.'], 500);
        }
    }

    /**
     * PUT /api/seminarios/{id}
     * Admin/sensei actualiza un seminario.
     */
    public function update(Request $request, int|string $id)
    {
        $this->soloAdminOSensei($request);

        $validated = $request->validate([
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
                    'nombre_seminario' => $validated['nombre_seminario'],
                    'fecha'            => $validated['fecha'],
                    'maestro'          => $validated['maestro'],
                    'descripcion'      => $validated['descripcion'] ?? null,
                    'resultado'        => $validated['resultado']   ?? null,
                ]);

            if (!$updated) {
                return response()->json(['success' => false, 'message' => 'Seminario no encontrado.'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Seminario actualizado.']);

        } catch (\Exception $e) {
            Log::error('SeminarioApi@update: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar.'], 500);
        }
    }

    /**
     * DELETE /api/seminarios/{id}
     * Solo admin elimina un seminario y todas sus participaciones.
     */
    public function destroy(Request $request, int|string $id)
    {
        $this->soloAdmin($request);

        try {
            $seminario = DB::table('seminario')->where('id_seminario', $id)->first();

            if (!$seminario) {
                return response()->json(['success' => false, 'message' => 'Seminario no encontrado.'], 404);
            }

            DB::beginTransaction();
            // Eliminar participaciones primero (FK)
            DB::table('historial_seminarios')->where('id_seminario', $id)->delete();
            DB::table('seminario')->where('id_seminario', $id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Seminario eliminado.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SeminarioApi@destroy: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar.'], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  HISTORIAL DE PARTICIPACIONES
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /api/alumnos/{id}/historial-seminarios
     * Devuelve los seminarios en los que participó un alumno.
     *
     * Reglas de visibilidad:
     *   - admin / sensei → pueden ver el historial de cualquier alumno.
     *   - alumno         → solo puede ver el suyo propio.
     */
    public function historialSeminarios(Request $request, int|string $id)
    {
        $authUser = $request->user();

        // Alumno solo puede ver su propio historial
        if ($authUser->rol === 'alumno' && (int) $authUser->id_usuario !== (int) $id) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        try {
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

            return response()->json(['success' => true, 'data' => $historial]);

        } catch (\Exception $e) {
            Log::error('SeminarioApi@historialSeminarios: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al obtener el historial.'], 500);
        }
    }

    /**
     * POST /api/alumnos/{id}/historial-seminarios
     * Admin/sensei registra la participación de un alumno en un seminario.
     *
     * Body JSON:
     * {
     *   "id_seminario":        3,
     *   "fecha_participacion": "2025-08-15",
     *   "observaciones":       "Completó con distinción"  (opcional)
     * }
     */
    public function storeHistorial(Request $request, int|string $id)
    {
        $this->soloAdminOSensei($request);

        $validated = $request->validate([
            'id_seminario'        => 'required|integer|exists:seminario,id_seminario',
            'fecha_participacion' => 'required|date',
            'observaciones'       => 'nullable|string|max:500',
        ]);

        try {
            // Verificar que el alumno exista
            $alumno = DB::table('usuario')
                ->where('id_usuario', $id)
                ->where('rol', 'alumno')
                ->first();

            if (!$alumno) {
                return response()->json(['success' => false, 'message' => 'Alumno no encontrado.'], 404);
            }

            // Evitar duplicado en el mismo seminario
            $existe = DB::table('historial_seminarios')
                ->where('id_usuario', $id)
                ->where('id_seminario', $validated['id_seminario'])
                ->exists();

            if ($existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este alumno ya tiene registrada su participación en ese seminario.',
                ], 409);
            }

            $nuevoId = DB::table('historial_seminarios')->insertGetId([
                'id_usuario'          => $id,
                'id_seminario'        => $validated['id_seminario'],
                'fecha_participacion' => $validated['fecha_participacion'],
                'observaciones'       => $validated['observaciones'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Participación registrada.',
                'id'      => $nuevoId,
            ], 201);

        } catch (\Exception $e) {
            Log::error('SeminarioApi@storeHistorial: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar.'], 500);
        }
    }

    /**
     * DELETE /api/historial-seminarios/{id}
     * Admin/sensei elimina una participación del historial.
     * {id} = historial_seminarios.id (PK)
     */
    public function destroyHistorial(Request $request, int|string $id)
    {
        $this->soloAdminOSensei($request);

        try {
            $deleted = DB::table('historial_seminarios')->where('id', $id)->delete();

            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'Registro no encontrado.'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Participación eliminada.']);

        } catch (\Exception $e) {
            Log::error('SeminarioApi@destroyHistorial: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al eliminar.'], 500);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function soloAdmin(Request $request): void
    {
        if ($request->user()->rol !== 'admin') {
            abort(response()->json(['success' => false, 'message' => 'Solo administradores.'], 403));
        }
    }

    private function soloAdminOSensei(Request $request): void
    {
        if (!in_array($request->user()->rol, ['admin', 'sensei'])) {
            abort(response()->json(['success' => false, 'message' => 'Solo administradores y senseis.'], 403));
        }
    }
}