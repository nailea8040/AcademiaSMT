<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GaleriaController extends Controller
{
    // ── Supabase helpers ──────────────────────────────────────────────────────

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
     * Sube un archivo a Supabase Storage y devuelve la ruta relativa (path dentro del bucket).
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
     */
    private function supabaseDelete(string $path): void
    {
        Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->supabaseKey(),
            'Content-Type'  => 'application/json',
        ])->delete($this->supabaseUrl() . '/storage/v1/object/' . $this->supabaseBucket() . '/' . $path);
    }

    /**
     * Devuelve la URL pública de un archivo en Supabase Storage.
     */
    public static function supabasePublicUrl(string $path): string
    {
        $url    = rtrim((string) config('services.supabase.url'), '/');
        $bucket = 'galeria';
        return $url . '/storage/v1/object/public/' . $bucket . '/' . $path;
    }

    // ── Permisos ──────────────────────────────────────────────────────────────

    private function esAdmin(): bool
    {
        return Auth::check() && Auth::user()->rol === 'admin';
    }

    private function esSensei(): bool
    {
        return Auth::check() && Auth::user()->rol === 'sensei';
    }

    private function puedeGestionar(): bool
    {
        return $this->esAdmin() || $this->esSensei();
    }

    // ── index ─────────────────────────────────────────────────────────────────
    public function index()
    {
        try {
            $nombresEventos = DB::table('evento')
                ->whereNotNull('nombre_evento')
                ->select('nombre_evento')
                ->distinct()
                ->orderBy('nombre_evento')
                ->pluck('nombre_evento');

            $eventos = [];
            foreach ($nombresEventos as $nombre) {
                $archivosEvento = DB::table('evento')
                    ->where('nombre_evento', $nombre)
                    ->orderBy('created_at', 'asc')
                    ->get();

                $eventos[] = (object)[
                    'nombre'       => $nombre,
                    'archivos'     => $archivosEvento,
                    'total'        => $archivosEvento->count(),
                    'total_fotos'  => $archivosEvento->where('tipo', 'imagen')->count(),
                    'total_videos' => $archivosEvento->where('tipo', 'video')->count(),
                    'miniaturas'   => $archivosEvento->take(8),
                    'destacado'    => $archivosEvento->where('destacado', 1)->count() > 0,
                ];
            }

            $individuales = DB::table('evento')
                ->whereNull('nombre_evento')
                ->orderBy('created_at', 'desc')
                ->get();

            $imagenes_ind = $individuales->where('tipo', 'imagen')->values();
            $videos_ind   = $individuales->where('tipo', 'video')->values();

            $eventosData = collect($eventos)->mapWithKeys(function ($e) {
                return [
                    $e->nombre => collect($e->archivos)->map(function ($a) {
                        return [
                            'id'        => $a->id_evento,
                            'titulo'    => $a->titulo,
                            'tipo'      => $a->tipo,
                            'src'       => self::supabasePublicUrl($a->ruta),
                            'destacado' => $a->destacado ?? 0,
                        ];
                    })->values()
                ];
            });

            return view('galeria', compact(
                'eventos', 'individuales', 'imagenes_ind',
                'videos_ind', 'eventosData'
            ));

        } catch (\Exception $e) {
            Log::error('GaleriaController@index: ' . $e->getMessage());
            return view('galeria', [
                'eventos'      => [],
                'individuales' => collect(),
                'imagenes_ind' => collect(),
                'videos_ind'   => collect(),
                'eventosData'  => collect(),
            ])->with('error', 'Error al cargar la galería.');
        }
    }

    // ── store ─────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        if (!$this->puedeGestionar()) {
            return back()->with('error', 'No tienes permisos para subir archivos.');
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
            $archivos     = $request->file('archivos');
            $modoEvento   = $request->modo === 'evento';
            $nombreEvento = $modoEvento ? trim($request->nombre_evento) : null;
            $destacado    = ($this->puedeGestionar() && $request->boolean('destacado')) ? 1 : 0;
            $subidos      = 0;

            DB::beginTransaction();

            foreach ($archivos as $index => $archivo) {
                $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo->getClientOriginalName());
                $path = time() . '_' . $index . '_' . $safe;

                // Subir a Supabase Storage
                $ruta = $this->supabaseUpload($archivo, $path);

                DB::table('evento')->insert([
                    'titulo'        => $modoEvento
                        ? $archivo->getClientOriginalName()
                        : ($request->titulo ?? $archivo->getClientOriginalName()),
                    'nombre_evento' => $nombreEvento,
                    'tipo'          => $request->tipo,
                    'ruta'          => $ruta,
                    'descripcion'   => $request->descripcion,
                    'destacado'     => $destacado,
                    'id_usuario'    => Auth::id(),
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $subidos++;
            }

            DB::commit();

            $msg = $modoEvento
                ? "{$subidos} archivo(s) añadidos al evento \"{$nombreEvento}\"."
                : 'Archivo subido exitosamente.';

            return back()->with('mensaje', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GaleriaController@store: ' . $e->getMessage());
            return back()->with('error', 'Error al subir: ' . $e->getMessage());
        }
    }

    // ── destroy ───────────────────────────────────────────────────────────────
    public function destroy(int $id)
    {
        if (!$this->esAdmin()) {
            return back()->with('error', 'No tienes permisos para eliminar archivos.');
        }

        try {
            $archivo = DB::table('evento')->where('id_evento', $id)->first();
            if (!$archivo) return back()->with('error', 'Archivo no encontrado.');

            if ($archivo->ruta) {
                $this->supabaseDelete($archivo->ruta);
            }

            DB::table('evento')->where('id_evento', $id)->delete();
            return back()->with('mensaje', 'Archivo eliminado.');
        } catch (\Exception $e) {
            Log::error('GaleriaController@destroy: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar.');
        }
    }

    // ── destroyEvento ─────────────────────────────────────────────────────────
    public function destroyEvento(Request $request)
    {
        if (!$this->esAdmin()) {
            return back()->with('error', 'No tienes permisos para eliminar eventos.');
        }

        try {
            $nombre   = $request->input('nombre_evento');
            $archivos = DB::table('evento')->where('nombre_evento', $nombre)->get();

            DB::beginTransaction();
            foreach ($archivos as $a) {
                if ($a->ruta) {
                    $this->supabaseDelete($a->ruta);
                }
            }
            $n = DB::table('evento')->where('nombre_evento', $nombre)->delete();
            DB::commit();

            return back()->with('mensaje', "Evento eliminado ({$n} archivos).");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GaleriaController@destroyEvento: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el evento.');
        }
    }

    // ── toggleDestacado ───────────────────────────────────────────────────────
    public function toggleDestacado(int $id)
    {
        if (!$this->puedeGestionar()) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        try {
            $archivo = DB::table('evento')->where('id_evento', $id)->first();

            if (!$archivo) {
                return response()->json(['success' => false, 'message' => 'No encontrado.'], 404);
            }

            $nuevoValor = $archivo->destacado ? 0 : 1;

            DB::table('evento')
                ->where('id_evento', $id)
                ->update(['destacado' => $nuevoValor]);

            return response()->json([
                'success'   => true,
                'destacado' => $nuevoValor,
                'message'   => $nuevoValor
                    ? 'Marcado como destacado en el landing.'
                    : 'Quitado de destacados.',
            ]);

        } catch (\Exception $e) {
            Log::error('GaleriaController@toggleDestacado: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }
}