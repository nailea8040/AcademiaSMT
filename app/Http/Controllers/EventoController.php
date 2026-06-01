<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * EventoController — gestión de galería multimedia (admin panel).
 *
 * Tabla real en BD: evento
 * Columnas: id_evento, titulo, tipo ENUM('imagen','video'), ruta,
 *           descripcion, id_usuario, nombre_evento, destacado,
 *           created_at, updated_at
 *
 * CORRECCIÓN: migrado de Storage::disk('public') local a Supabase Storage,
 * igual que GaleriaController. Así todos los archivos quedan en el mismo
 * sistema de almacenamiento y no se pierden en cada deploy de Railway.
 *
 * NOTA: Los eventos de calendario (clases, torneos, exámenes) se gestionan
 *       en CalendarioController con la tabla 'calendario'.
 */
class EventoController extends Controller
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
     * Sube un archivo a Supabase Storage y devuelve el path dentro del bucket.
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

    // ── index ─────────────────────────────────────────────────────────────────

    public function index()
    {
        try {
            $archivos = DB::table('evento')
                ->orderBy('created_at', 'desc')
                ->get();

            // Agregar URL pública de Supabase a cada archivo
            $archivos->transform(function ($archivo) {
                $archivo->url_publica = $archivo->ruta
                    ? self::supabasePublicUrl($archivo->ruta)
                    : null;
                return $archivo;
            });

            return view('eventoViews.eventos', compact('archivos'));

        } catch (\Exception $e) {
            Log::error('EventoController@index: ' . $e->getMessage());
            return view('eventoViews.eventos', ['archivos' => collect()])
                ->with('error', 'Error al cargar los eventos.');
        }
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        if (!$this->esAdmin()) {
            return back()->with('error', 'No tienes permisos para subir archivos.');
        }

        $request->validate([
            'titulo'      => 'required|string|max:255',
            'tipo'        => 'required|in:imagen,video',
            'archivo'     => 'required|file|max:51200',
            'descripcion' => 'nullable|string',
        ]);

        if ($request->tipo === 'imagen') {
            $request->validate(['archivo' => 'mimes:jpeg,jpg,png|max:10240']);
        } else {
            $request->validate(['archivo' => 'mimes:mp4|max:51200']);
        }

        try {
            $archivo       = $request->file('archivo');
            // CORRECCIÓN: time() podía generar colisiones. Str::uuid() garantiza unicidad.
            $safe          = preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo->getClientOriginalName());
            $nombreArchivo = Str::uuid() . '_' . $safe;

            // Subir a Supabase Storage (no al disco local)
            $ruta = $this->supabaseUpload($archivo, $nombreArchivo);

            DB::table('evento')->insert([
                'titulo'      => $request->titulo,
                'tipo'        => $request->tipo,
                'ruta'        => $ruta,
                'descripcion' => $request->descripcion,
                'id_usuario'  => Auth::id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return back()->with('mensaje', 'Archivo subido exitosamente.');

        } catch (\Exception $e) {
            Log::error('EventoController@store: ' . $e->getMessage());
            return back()->with('error', 'Error al subir el archivo: ' . $e->getMessage());
        }
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        if (!$this->esAdmin()) {
            return back()->with('error', 'No tienes permisos para editar.');
        }

        $request->validate([
            'titulo'      => 'required|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        try {
            $updated = DB::table('evento')
                ->where('id_evento', $id)
                ->update([
                    'titulo'      => $request->titulo,
                    'descripcion' => $request->descripcion,
                    'updated_at'  => now(),
                ]);

            return back()->with(
                $updated ? 'mensaje' : 'error',
                $updated ? 'Evento actualizado con éxito.' : 'No se encontró el evento.'
            );

        } catch (\Exception $e) {
            Log::error('EventoController@update: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar.');
        }
    }

    // ── destroy ───────────────────────────────────────────────────────────────

    public function destroy($id)
    {
        if (!$this->esAdmin()) {
            return back()->with('error', 'No tienes permisos para eliminar archivos.');
        }

        try {
            $archivo = DB::table('evento')->where('id_evento', $id)->first();

            if (!$archivo) {
                return back()->with('error', 'Archivo no encontrado.');
            }

            // Eliminar de Supabase Storage
            if ($archivo->ruta) {
                $this->supabaseDelete($archivo->ruta);
            }

            DB::table('evento')->where('id_evento', $id)->delete();

            return back()->with('mensaje', 'Archivo eliminado exitosamente.');

        } catch (\Exception $e) {
            Log::error('EventoController@destroy: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el archivo.');
        }
    }
}