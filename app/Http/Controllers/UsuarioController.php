<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuario = DB::table('usuario')->get();
        return view('usuariosViews.usuarios', ['usuarios' => $usuario]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|max:100',
            'apaterno'       => 'required|string|max:100',
            'amaterno'       => 'required|string|max:100',
            'fecha_naci'     => 'required|date',
            'tel'            => 'required|string|max:10',
            'correo'         => 'required|email|unique:usuario,correo',
            'pass'           => 'required|min:6',
            // ✅ BD usa 'admin' no 'administrador'
            'rol'            => 'required|in:admin,sensei,tutor,alumno',
            // ✅ fecha_registro ya NO es requerida del form — se genera aquí
            // ✅ 'estado' => 1 era el bug — no es una regla de validación
            // Campos bachiller opcionales
            'es_bachiller'   => 'nullable|boolean',
            'numero_control' => 'nullable|string|max:20',
            'grupo'          => 'nullable|string|max:10',
            'especialidad'   => 'nullable|string|max:100',
            'turno'          => 'nullable|in:Matutino,Vespertino,Nocturno',
        ]);

        try {
            $esBachiller = $request->boolean('es_bachiller');

            DB::table('usuario')->insert([
                'nombre'         => $validated['nombre'],
                'apaterno'       => $validated['apaterno'],
                'amaterno'       => $validated['amaterno'],
                'fecha_naci'     => $validated['fecha_naci'],
                // ✅ columna real en BD es 'telefono', no 'tel'
                'telefono'       => $validated['tel'],
                'correo'         => $validated['correo'],
                'pass'           => Hash::make($validated['pass']),
                'rol'            => $validated['rol'],
                // ✅ generado en servidor
                'fecha_registro' => now()->toDateString(),
                'estado'         => 1,
                // Bachiller — null si no aplica
                'numero_control' => $esBachiller ? ($validated['numero_control'] ?? null) : null,
                'grupo'          => $esBachiller ? ($validated['grupo']          ?? null) : null,
                'especialidad'   => $esBachiller ? ($validated['especialidad']   ?? null) : null,
                'turno'          => $esBachiller ? ($validated['turno']          ?? null) : null,
            ]);

            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', '¡Usuario registrado con éxito!');

        } catch (\Exception $e) {
            Log::error('UsuarioController@store: ' . $e->getMessage());
            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Hubo un error al registrar el usuario.');
        }
    }

    public function VerLogin()
    {
        return view('login');
    }

    public function show() {}

    public function edit($id)
    {
        $usuario = DB::table('usuario')->where('id_usuario', $id)->first();

        if (!$usuario) {
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Usuario no encontrado para edición.');
        }

        return view('usuariosViews.editarUsu', compact('usuario'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre'         => 'required|string|max:100',
            'apaterno'       => 'required|string|max:100',
            'amaterno'       => 'required|string|max:100',
            'fecha_naci'     => 'required|date',
            'tel'            => 'required|string|max:20',
            'correo'         => 'required|email|unique:usuario,correo,' . $id . ',id_usuario',
            // ✅ BD usa 'admin' no 'administrador'
            'rol'            => 'required|in:admin,sensei,tutor,alumno',
            'pass'           => 'nullable|min:6',
            // Bachiller
            'es_bachiller'   => 'nullable|boolean',
            'numero_control' => 'nullable|string|max:20',
            'grupo'          => 'nullable|string|max:10',
            'especialidad'   => 'nullable|string|max:100',
            'turno'          => 'nullable|in:Matutino,Vespertino,Nocturno',
        ]);

        try {
            $esBachiller = $request->boolean('es_bachiller');

            $data = [
                'nombre'         => $validated['nombre'],
                'apaterno'       => $validated['apaterno'],
                'amaterno'       => $validated['amaterno'],
                'fecha_naci'     => $validated['fecha_naci'],
                // ✅ columna real en BD es 'telefono'
                'telefono'       => $validated['tel'],
                'correo'         => $validated['correo'],
                'rol'            => $validated['rol'],
                'numero_control' => $esBachiller ? ($validated['numero_control'] ?? null) : null,
                'grupo'          => $esBachiller ? ($validated['grupo']          ?? null) : null,
                'especialidad'   => $esBachiller ? ($validated['especialidad']   ?? null) : null,
                'turno'          => $esBachiller ? ($validated['turno']          ?? null) : null,
            ];

            if (!empty($validated['pass'])) {
                $data['pass'] = Hash::make($validated['pass']);
            }

            DB::table('usuario')->where('id_usuario', $id)->update($data);

            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', '¡Usuario con ID ' . $id . ' actualizado con éxito!');

        } catch (\Exception $e) {
            Log::error("UsuarioController@update ID $id: " . $e->getMessage());
            return redirect()
                ->route('editarUsu', $id)
                ->withInput()
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $deleted = DB::table('usuario')->where('id_usuario', $id)->delete();

            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', $deleted ? 'true' : 'false')
                ->with('mensaje', $deleted
                    ? '¡Usuario con ID ' . $id . ' eliminado con éxito!'
                    : 'No se encontró el usuario con ID ' . $id . ' para eliminar.'
                );

        } catch (\Exception $e) {
            Log::error("UsuarioController@destroy ID $id: " . $e->getMessage());
            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al eliminar el usuario. Es posible que tenga registros relacionados.');
        }
    }

    public function toggleActive($id)
    {
        try {
            $usuario = DB::table('usuario')->where('id_usuario', $id)->first();

            if (!$usuario) {
                return redirect()->route('usuarios.index')
                    ->with('sessionInsertado', 'false')
                    ->with('mensaje', 'Usuario no encontrado.');
            }

            $nuevoEstado = $usuario->estado == 1 ? 0 : 1;
            $accion      = $nuevoEstado == 1 ? 'Activado' : 'Desactivado';

            DB::table('usuario')
                ->where('id_usuario', $id)
                ->update(['estado' => $nuevoEstado]);

            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', "¡Usuario ID $id ha sido $accion con éxito!");

        } catch (\Exception $e) {
            Log::error("UsuarioController@toggleActive ID $id: " . $e->getMessage());
            return redirect()
                ->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al cambiar el estado del usuario.');
        }
    }

    public function confirmMail($correo) {}
}