<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\GaleriaController;   // para supabasePublicUrl()
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GaleriaApiController extends Controller
{
    // ── Helpers Supabase (copiados de GaleriaController para mantener paridad) ──

    private function supabaseUrl(): string
    {
        return rtrim((string) config('services.supabase.url'), '/');
    }

    private function supabaseKey(): string
    {
        return (string) config('services.supabase.secret_key');
    }

    private function supabaseBucket(): string
    {
        return 'galeria';
    }

    /**
     * Sube un archivo a Supabase Storage.
     * FIX: en la versión anterior se usaba Storage::disk('public') local,
     * lo que causaba que las imágenes subidas desde móvil no fuesen accesibles.
     */
    private function supabaseUpload(\Illuminate\Http\UploadedFile $archivo, string $path): string
    {
        $contenido = file_get_contents($archivo->getRealPath());
        $mime      = $archivo->getMimeType();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->supabaseKey(),
            'Content-Type'  => $mime,
        ])->withBody($contenido, $mime)
          ->post($this->supabaseUrl() . '/storage/v1/object/' . $this->supabaseBucket() . '/' . $path);

        if ($response->failed()) {
            throw new \Exception('Supabase upload error: ' . $response->body());
        }

        return $path;
    }

    /**
     * Elimina un archivo de Supabase Storage.
     * FIX: en la versión anterior se usaba Storage::disk('public')->delete(),
     * que no llegaba a borrar nada en Supabase.
     */
    private function supabaseDelete(string $path): void
    {
        Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->supabaseKey(),
            'Content-Type'  => 'application/json',
        ])->delete($this->supabaseUrl() . '/storage/v1/object/' . $this->supabaseBucket() . '/' . $path);
    }

    // ── Permisos ──────────────────────────────────────────────────────────────

    private function esAdmin(Request $request): bool
    {
        return $request->user()->rol === 'admin';
    }

    /**
     * Admin y sensei pueden subir archivos.
     * Igual que GaleriaController (web).
     */
    private function puedeGestionar(Request $request): bool
    {
        return in_array($request->user()->rol, ['admin', 'sensei']);
    }

    // ── index ─────────────────────────────────────────────────────────────────

    /**
     * GET /api/galeria
     * Pública — no requiere token
     * Devuelve eventos agrupados + archivos individuales con URLs públicas de Supabase.
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

    // ── store ─────────────────────────────────────────────────────────────────

    /**
     * POST /api/galeria
     * Admin o sensei — acepta multipart/form-data
     *
     * Campos:
     *   modo          string  'individual' | 'evento'
     *   titulo        string  requerido si modo=individual
     *   nombre_evento string  requerido si modo=evento
     *   tipo          string  'imagen' | 'video'
     *   descripcion   string  opcional
     *   archivos[]    file    uno o varios archivos
     *   destacado     bool    opcional (solo admin lo aplica)
     */
    public function store(Request $request)
    {
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
            'destacado'     => 'nullable|boolean',
        ]);

        if ($request->tipo === 'imagen') {
            $request->validate(['archivos.*' => 'mimes:jpeg,jpg,png|max:10240']);
        } else {
            $request->validate(['archivos.*' => 'mimes:mp4|max:51200']);
        }

        try {
            $modoEvento   = $request->modo === 'evento';
            $nombreEvento = $modoEvento ? trim($request->nombre_evento) : null;
            $subidos      = 0;

            // FIX: destacado alineado con GaleriaController web:
            // admin Y sensei pueden marcar destacado (puedeGestionar, no solo esAdmin)
            $destacado = ($this->puedeGestionar($request) && $request->boolean('destacado')) ? 1 : 0;

            DB::beginTransaction();

            foreach ($request->file('archivos') as $i => $archivo) {
                // Sanitizar nombre del archivo
                $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo->getClientOriginalName());
                $path = time() . '_' . $i . '_' . $safe;

                // FIX: subir a Supabase Storage (antes usaba Storage::disk('public') local)
                $ruta = $this->supabaseUpload($archivo, $path);

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

    // ── destroy ───────────────────────────────────────────────────────────────

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

            // FIX: eliminar de Supabase (antes usaba Storage::disk('public')->delete())
            if ($archivo->ruta) {
                $this->supabaseDelete($archivo->ruta);
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

    // ── destroyEvento ─────────────────────────────────────────────────────────

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

            // FIX: eliminar de Supabase (antes usaba Storage::disk('public')->delete())
            foreach ($archivos as $archivo) {
                if ($archivo->ruta) {
                    $this->supabaseDelete($archivo->ruta);
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
     * Mapea un registro de BD a un array con URL pública de Supabase incluida.
     * FIX: usa GaleriaController::supabasePublicUrl() igual que la web,
     * en lugar de asset('storage/...') que apuntaba al disco local.
     */
    private function mapArchivo(object $archivo): array
    {
        return [
            'id_evento'   => $archivo->id_evento,
            'titulo'      => $archivo->titulo,
            'tipo'        => $archivo->tipo,        // 'imagen' o 'video'
            'descripcion' => $archivo->descripcion ?? null,
            // FIX: URL pública de Supabase, no del storage local
            'url'         => GaleriaController::supabasePublicUrl($archivo->ruta),
            'created_at'  => $archivo->created_at,
        ];
    }
}