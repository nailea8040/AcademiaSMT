<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;

class PerfilController extends Controller
{
    /**
     * Mostrar el perfil del usuario autenticado.
     */
    public function index()
    {
        $usuario = Auth::user();
        return view('perfil', compact('usuario'));
    }

    /**
     * Actualizar el perfil del usuario.
     * Columnas reales en BD: nombre, apaterno, amaterno, fecha_naci, telefono, correo, pass
     * NOTA: 'tel' del formulario se mapea a columna 'telefono' en BD.
     */
    public function update(Request $request)
    {
        $usuario = Auth::user();

        $validated = $request->validate([
            'nombre'     => 'required|string|max:100',
            'apaterno'   => 'required|string|max:100',
            'amaterno'   => 'required|string|max:100',
            'fecha_naci' => 'required|date',
            // Tabla correcta: 'usuario' (no 'usuarios')
            'correo'     => 'required|email|unique:usuario,correo,' . $usuario->id_usuario . ',id_usuario',
            // Campo del form: 'tel' → columna BD: 'telefono'
            'tel'        => 'required|string|digits:10',
            'password'   => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'nombre.required'     => 'El nombre es obligatorio.',
            'apaterno.required'   => 'El apellido paterno es obligatorio.',
            'amaterno.required'   => 'El apellido materno es obligatorio.',
            'fecha_naci.required' => 'La fecha de nacimiento es obligatoria.',
            'correo.required'     => 'El correo electrónico es obligatorio.',
            'correo.email'        => 'Debe ser un correo válido.',
            'correo.unique'       => 'Este correo ya está registrado.',
            'tel.required'        => 'El teléfono es obligatorio.',
            'tel.digits'          => 'El teléfono debe tener exactamente 10 dígitos.',
            'password.confirmed'  => 'Las contraseñas no coinciden.',
        ]);

        try {
            $usuario->nombre     = $validated['nombre'];
            $usuario->apaterno   = $validated['apaterno'];
            $usuario->amaterno   = $validated['amaterno'];
            $usuario->fecha_naci = $validated['fecha_naci'];
            $usuario->correo     = $validated['correo'];
            // Columna real en BD: 'telefono'
            $usuario->telefono   = $validated['tel'];

            if ($request->filled('password')) {
                $usuario->pass = Hash::make($validated['password']);
            }

            $usuario->save();

            return redirect()->route('perfil')
                ->with('success', 'Perfil actualizado correctamente.');

        } catch (\Exception $e) {
            Log::error('PerfilController@update: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al actualizar el perfil.');
        }
    }

    /**
     * Actualizar foto de perfil.
     * NOTA: La columna 'avatar' no existe en la tabla 'usuario' del SQL actual.
     *       Para activar esta funcionalidad ejecutar:
     *       ALTER TABLE usuario ADD COLUMN avatar VARCHAR(255) NULL;
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $usuario = Auth::user();

        try {
            if ($request->hasFile('avatar')) {
                $imagen      = $request->file('avatar');
                $nombreImagen = 'avatar_' . $usuario->id_usuario . '.' . $imagen->getClientOriginalExtension();
                $imagen->move(public_path('avatars'), $nombreImagen);

                // Requiere columna avatar en tabla usuario
                $usuario->avatar = 'avatars/' . $nombreImagen;
                $usuario->save();
            }

            return redirect()->route('perfil')
                ->with('success', 'Foto de perfil actualizada correctamente.');

        } catch (\Exception $e) {
            Log::error('PerfilController@updateAvatar: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al actualizar la foto de perfil.');
        }
    }
}