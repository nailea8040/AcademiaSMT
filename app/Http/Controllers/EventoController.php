<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * EventoController maneja la galería multimedia de la academia.
 * Tabla real en BD: evento
 * Columnas: id_evento, titulo, tipo ENUM('imagen','video'), ruta, descripcion, id_usuario
 *
 * NOTA: Los eventos de calendario (clases, torneos, exámenes) se gestionan
 *       en CalendarioController con la tabla 'calendario'.
 */
class EventoController extends Controller
{
    private function esAdmin(): bool
    {
        return Auth::check() && Auth::user()->rol === 'admin';
    }

    public function index()
    {
        try {
            // Tabla correcta: evento (no 'eventos')
            $archivos = DB::table('evento')
                ->orderBy('created_at', 'desc')
                ->get();

            return view('eventoViews.eventos', compact('archivos'));

        } catch (\Exception $e) {
            Log::error('EventoController@index: ' . $e->getMessage());
            return view('eventoViews.eventos', ['archivos' => collect()])
                ->with('error', 'Error al cargar los eventos.');
        }
    }

    public function store(Request $request)
    {
        if (!$this->esAdmin()) {
            return back()->with('error', 'No tienes permisos para subir archivos.');
        }

        $request->validate([
            'titulo'      => 'required|string|max:255',
            // ENUM real en BD: 'imagen' o 'video' (no 'image')
            'tipo'        => 'required|in:imagen,video',
            'archivo'     => 'required|file|max:51200',
            'descripcion' => 'nullable|string',
        ]);

        // Validación adicional según tipo
        if ($request->tipo === 'imagen') {
            $request->validate(['archivo' => 'mimes:jpeg,jpg,png|max:10240']);
        } else {
            $request->validate(['archivo' => 'mimes:mp4|max:51200']);
        }

        try {
            $archivo       = $request->file('archivo');
            $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
            // Guardar en storage/app/public/eventos/
            $ruta = $archivo->storeAs('eventos', $nombreArchivo, 'public');

            // Tabla correcta: evento (no 'galeria', no 'eventos')
            DB::table('evento')->insert([
                'titulo'      => $request->titulo,
                'tipo'        => $request->tipo,   // 'imagen' o 'video'
                'ruta'        => $ruta,
                'descripcion' => $request->descripcion,
                'id_usuario'  => Auth::id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return back()->with('mensaje', 'Archivo subido exitosamente.');

        } catch (\Exception $e) {
            Log::error('EventoController@store: ' . $e->getMessage());
            return back()->with('error', 'Error al subir el archivo.');
        }
    }

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
            // PK real: id_evento
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

    public function destroy($id)
    {
        if (!$this->esAdmin()) {
            return back()->with('error', 'No tienes permisos para eliminar archivos.');
        }

        try {
            // PK real: id_evento
            $archivo = DB::table('evento')->where('id_evento', $id)->first();

            if (!$archivo) {
                return back()->with('error', 'Archivo no encontrado.');
            }

            // Eliminar archivo físico del storage
            if ($archivo->ruta && Storage::disk('public')->exists($archivo->ruta)) {
                Storage::disk('public')->delete($archivo->ruta);
            }

            DB::table('evento')->where('id_evento', $id)->delete();

            return back()->with('mensaje', 'Archivo eliminado exitosamente.');

        } catch (\Exception $e) {
            Log::error('EventoController@destroy: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el archivo.');
        }
    }
}