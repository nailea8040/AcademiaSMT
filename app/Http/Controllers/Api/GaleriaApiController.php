<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GaleriaApiController extends Controller
{
    private function esAdmin(Request $request): bool
    {
        return $request->user()->rol === 'admin';
    }

    private function esSensei(Request $request): bool
    {
        return $request->user()->rol === 'sensei';
    }

    /**
     * Admin y sensei pueden subir archivos.
     * Solo admin puede eliminar.
     * Igual que GaleriaController (web).
     */
    private function puedeGestionar(Request $request): bool
    {
        return in_array($request->user()->rol, ['admin', 'sensei']);
    }

    /**
     * GET /api/galeria
     * Pública — no requiere token
     * Devuelve eventos agrupados + archivos individuales con URLs públicas
     */
    public function index()
    {
        try {
            // ── Eventos agrupados por nombre_evento ──────────────────────
            $nombresEventos = DB::table('evento')
                ->whereNotNull('nombre_evento')
                ->distinct()
                ->orderBy('nombre_evento')
                ->pluck('nombre_evento');

            $eventos = $nombresEventos->map(function ($nombre) {
                $archivos = DB::table('evento')
                    ->where('nombre_evento', $nombre)
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(fn($a) => $this->mapArchivo($a));

                return [
                    'nombre'       => $nombre,
                    'total'        => $archivos->count(),
                    'total_fotos'  => $archivos->where('tipo', 'imagen')->count(),
                    'total_videos' => $archivos->where('tipo', 'video')->count(),
                    'archivos'     => $archivos->values(),
                ];
            });

            // ── Archivos individuales (nombre_evento NULL) ───────────────
            $individuales = DB::table('evento')
                ->whereNull('nombre_evento')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn($a) => $this->mapArchivo($a));

            return response()->json([
                'success'      => true,
                'eventos'      => $eventos->values(),
                'individuales' => $individuales->values(),
            ]);

        } catch (\Exception $e) {
            Log::error('GaleriaApi@index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la galería.',
            ], 500);
        }
    }

    /**
     * POST /api/galeria
     * Solo admin — acepta multipart/form-data
     *
     * Campos:
     *   modo          string  'individual' | 'evento'
     *   titulo        string  requerido si modo=individual
     *   nombre_evento string  requerido si modo=evento
     *   tipo          string  'imagen' | 'video'
     *   descripcion   string  opcional
     *   archivos[]    file    uno o varios archivos
     */
    public function store(Request $request)
    {
        // Web permite admin y sensei subir archivos — igualamos comportamiento
        if (!$this->puedeGestionar($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo administradores y senseis pueden subir archivos.',
            ], 403);
        }

        $request->validate([
            'modo'          => 'required|in:individual,evento',
            'titulo'        => 'required_if:modo,individual|nullable|string|max:255',
            'nombre_evento' => 'required_if:modo,evento|nullable|string|max:255',
            'tipo'          => 'required|in:imagen,video',
            'descripcion'   => 'nullable|string|max:1000',
            'archivos'      => 'required|array|min:1',
            'archivos.*'    => 'required|file|max:51200',
            // Igual que GaleriaController web — solo admin puede marcar destacado
            'destacado'     => 'nullable|boolean',
        ]);

        // Validación adicional por tipo
        if ($request->tipo === 'imagen') {
            $request->validate(['archivos.*' => 'mimes:jpeg,jpg,png|max:10240']);
        } else {
            $request->validate(['archivos.*' => 'mimes:mp4|max:51200']);
        }

        try {
            $modoEvento   = $request->modo === 'evento';
            $nombreEvento = $modoEvento ? trim($request->nombre_evento) : null;
            $subidos      = 0;

            DB::beginTransaction();

            foreach ($request->file('archivos') as $i => $archivo) {
                // Sanitizar nombre del archivo
                $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo->getClientOriginalName());
                $ruta = $archivo->storeAs(
                    'galeria',
                    time() . '_' . $i . '_' . $safe,
                    'public'
                );

                // destacado solo lo puede marcar el admin (igual que GaleriaController web)
                $destacado = ($this->esAdmin($request) && $request->boolean('destacado')) ? 1 : 0;

                DB::table('evento')->insert([
                    'titulo'        => $modoEvento
                        ? $archivo->getClientOriginalName()
                        : ($request->titulo ?? $archivo->getClientOriginalName()),
                    'nombre_evento' => $nombreEvento,
                    'tipo'          => $request->tipo,
                    'ruta'          => $ruta,
                    'descripcion'   => $request->descripcion ?? null,
                    'destacado'     => $destacado,
                    'id_usuario'    => $request->user()->id_usuario,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $subidos++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "{$subidos} archivo(s) subido(s) correctamente.",
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GaleriaApi@store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al subir los archivos.',
            ], 500);
        }
    }

    /**
     * DELETE /api/galeria/{id}
     * Solo admin — elimina un archivo individual por id_evento
     */
    public function destroy(Request $request, int|string $id)
    {
        if (!$this->esAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo administradores pueden eliminar archivos.',
            ], 403);
        }

        try {
            $archivo = DB::table('evento')->where('id_evento', $id)->first();

            if (!$archivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado.',
                ], 404);
            }

            // Eliminar archivo físico del storage
            if ($archivo->ruta && Storage::disk('public')->exists($archivo->ruta)) {
                Storage::disk('public')->delete($archivo->ruta);
            }

            DB::table('evento')->where('id_evento', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado correctamente.',
            ]);

        } catch (\Exception $e) {
            Log::error('GaleriaApi@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo.',
            ], 500);
        }
    }

    /**
     * DELETE /api/galeria/evento
     * Solo admin — elimina todos los archivos de un evento agrupado
     * Body: { "nombre_evento": "Torneo 2025" }
     */
    public function destroyEvento(Request $request)
    {
        if (!$this->esAdmin($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo administradores pueden eliminar eventos.',
            ], 403);
        }

        $request->validate([
            'nombre_evento' => 'required|string',
        ]);

        try {
            $archivos = DB::table('evento')
                ->where('nombre_evento', $request->nombre_evento)
                ->get();

            if ($archivos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el evento.',
                ], 404);
            }

            DB::beginTransaction();

            foreach ($archivos as $archivo) {
                if ($archivo->ruta && Storage::disk('public')->exists($archivo->ruta)) {
                    Storage::disk('public')->delete($archivo->ruta);
                }
            }

            $eliminados = DB::table('evento')
                ->where('nombre_evento', $request->nombre_evento)
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Evento eliminado ({$eliminados} archivo(s)).",
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GaleriaApi@destroyEvento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el evento.',
            ], 500);
        }
    }

    // ── Helper privado ────────────────────────────────────────────────────────

    /**
     * Mapea un registro de BD a un array con URL pública incluida
     */
    private function mapArchivo(object $archivo): array
    {
        return [
            'id_evento'   => $archivo->id_evento,
            'titulo'      => $archivo->titulo,
            'tipo'        => $archivo->tipo,       // 'imagen' o 'video'
            'descripcion' => $archivo->descripcion ?? null,
            'url'         => asset('storage/' . $archivo->ruta),
            'created_at'  => $archivo->created_at,
        ];
    }
}