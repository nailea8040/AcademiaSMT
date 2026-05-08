<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CalendarioController extends Controller
{
    private function esAdmin(): bool
    {
        return Auth::check() && Auth::user()->rol === 'admin';
    }

    private function esSensei(): bool
    {
        return Auth::check() && Auth::user()->rol === 'sensei';
    }

    /**
     * Admin y sensei pueden crear/editar eventos.
     * Solo admin puede eliminar.
     */
    private function puedeGestionar(): bool
    {
        return $this->esAdmin() || $this->esSensei();
    }

    /**
     * Tabla real en BD: calendario
     * Columnas: id_cal, titulo, fecha, hora, ubicacion, tipo, descripcion, id_usuario
     */
    public function index()
    {
        try {
            $eventos = DB::table('calendario')
                ->orderBy('fecha', 'asc')
                ->orderBy('hora', 'asc')
                ->get();

            return view('calendario', compact('eventos'));

        } catch (\Exception $e) {
            Log::error('CalendarioController@index: ' . $e->getMessage());
            return view('calendario', ['eventos' => collect()])
                ->with('error', 'Error al cargar el calendario.');
        }
    }

    public function store(Request $request)
    {
        if (!$this->puedeGestionar()) {
            return back()->with('error', 'No tienes permisos para crear eventos.');
        }

        $request->validate([
            'titulo'      => 'required|string|max:100',
            'fecha'       => 'required|date',
            'hora'        => 'required',
            'ubicacion'   => 'required|string|max:255',
            'tipo'        => 'required|string|max:50',
            'descripcion' => 'nullable|string',
        ]);

        try {
            DB::table('calendario')->insert([
                'titulo'      => $request->titulo,
                'fecha'       => $request->fecha,
                'hora'        => $request->hora,
                'ubicacion'   => $request->ubicacion,
                'tipo'        => $request->tipo,
                'descripcion' => $request->descripcion,
                'id_usuario'  => Auth::id(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return back()->with('mensaje', 'Evento creado con éxito.');

        } catch (\Exception $e) {
            Log::error('CalendarioController@store: ' . $e->getMessage());
            return back()->with('error', 'Error al crear el evento.');
        }
    }

    public function update(Request $request, $id)
    {
        if (!$this->puedeGestionar()) {
            return back()->with('error', 'No tienes permisos para editar eventos.');
        }

        $request->validate([
            'titulo'      => 'required|string|max:100',
            'fecha'       => 'required|date',
            'hora'        => 'required',
            'ubicacion'   => 'required|string|max:255',
            'tipo'        => 'required|string|max:50',
            'descripcion' => 'nullable|string',
        ]);

        try {
            $updated = DB::table('calendario')
                ->where('id_cal', $id)
                ->update([
                    'titulo'      => $request->titulo,
                    'fecha'       => $request->fecha,
                    'hora'        => $request->hora,
                    'ubicacion'   => $request->ubicacion,
                    'tipo'        => $request->tipo,
                    'descripcion' => $request->descripcion,
                    'updated_at'  => now(),
                ]);

            return back()->with(
                $updated ? 'mensaje' : 'error',
                $updated ? 'Evento actualizado con éxito.' : 'No se encontró el evento.'
            );

        } catch (\Exception $e) {
            Log::error('CalendarioController@update: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar el evento.');
        }
    }

    public function destroy($id)
    {
        // Solo admin puede eliminar eventos
        if (!$this->esAdmin()) {
            return back()->with('error', 'No tienes permisos para eliminar eventos.');
        }

        try {
            $deleted = DB::table('calendario')->where('id_cal', $id)->delete();

            return back()->with(
                $deleted ? 'mensaje' : 'error',
                $deleted ? 'Evento eliminado con éxito.' : 'No se encontró el evento.'
            );

        } catch (\Exception $e) {
            Log::error('CalendarioController@destroy: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el evento.');
        }
    }
}