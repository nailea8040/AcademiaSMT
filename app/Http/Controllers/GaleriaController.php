<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * GaleriaController
 *
 * Tabla 'evento' con columna nueva: nombre_evento VARCHAR(255) NULL
 *   nombre_evento IS NULL  → archivo individual
 *   nombre_evento = valor  → pertenece a galería de evento
 */
class GaleriaController extends Controller
{
    private function esAdmin(): bool
    {
        return Auth::check() && Auth::user()->rol === 'admin';
    }

    public function index()
    {
        try {
            // Eventos agrupados por nombre_evento
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
                ];
            }

            // Archivos individuales (nombre_evento NULL)
            $individuales = DB::table('evento')
                ->whereNull('nombre_evento')
                ->orderBy('created_at', 'desc')
                ->get();

            $imagenes_ind = $individuales->where('tipo', 'imagen')->values();
            $videos_ind   = $individuales->where('tipo', 'video')->values();

                        // ===============================
            // Generar estructura para JS
            // ===============================
            $eventosData = collect($eventos)->mapWithKeys(function($e) {
                return [
                    $e->nombre => collect($e->archivos)->map(function($a) {
                        return [
                            'id'     => $a->id_evento,
                            'titulo' => $a->titulo,
                            'tipo'   => $a->tipo,
                            'src'    => asset('storage/' . $a->ruta),
                        ];
                    })->values()
                ];
            });

            return view('galeria', compact('eventos', 'individuales', 'imagenes_ind', 'videos_ind', 'eventosData'));

        } catch (\Exception $e) {
            Log::error('GaleriaController@index: ' . $e->getMessage());
            return view('galeria', [
                'eventos'      => [],
                'individuales' => collect(),
                'imagenes_ind' => collect(),
                'videos_ind'   => collect(),
            ])->with('error', 'Error al cargar la galería.');
        }
    }

    public function store(Request $request)
    {
        if (!$this->esAdmin()) {
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
            $subidos      = 0;

            DB::beginTransaction();

            foreach ($archivos as $index => $archivo) {
                $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $archivo->getClientOriginalName());
                $ruta = $archivo->storeAs('galeria', time() . '_' . $index . '_' . $safe, 'public');

                DB::table('evento')->insert([
                    'titulo'        => $modoEvento ? $archivo->getClientOriginalName() : ($request->titulo ?? $archivo->getClientOriginalName()),
                    'nombre_evento' => $nombreEvento,
                    'tipo'          => $request->tipo,
                    'ruta'          => $ruta,
                    'descripcion'   => $request->descripcion,
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

    public function destroy($id)
    {
        if (!$this->esAdmin()) {
            return back()->with('error', 'No tienes permisos.');
        }
        try {
            $archivo = DB::table('evento')->where('id_evento', $id)->first();
            if (!$archivo) return back()->with('error', 'Archivo no encontrado.');
            if ($archivo->ruta && Storage::disk('public')->exists($archivo->ruta)) {
                Storage::disk('public')->delete($archivo->ruta);
            }
            DB::table('evento')->where('id_evento', $id)->delete();
            return back()->with('mensaje', 'Archivo eliminado.');
        } catch (\Exception $e) {
            Log::error('GaleriaController@destroy: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar.');
        }
    }

    /** Eliminar evento completo (todos sus archivos) */
    public function destroyEvento(Request $request)
    {
        if (!$this->esAdmin()) {
            return back()->with('error', 'No tienes permisos.');
        }
        try {
            $nombre   = $request->input('nombre_evento');
            $archivos = DB::table('evento')->where('nombre_evento', $nombre)->get();
            DB::beginTransaction();
            foreach ($archivos as $a) {
                if ($a->ruta && Storage::disk('public')->exists($a->ruta)) {
                    Storage::disk('public')->delete($a->ruta);
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
}