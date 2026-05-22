<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UsuarioController extends Controller
{
    // ── Helpers de rol ───────────────────────────────────────────────────────

    private function esAdmin(): bool
    {
        return Auth::check() && Auth::user()->rol === 'admin';
    }

    private function esSensei(): bool
    {
        return Auth::check() && Auth::user()->rol === 'sensei';
    }

    /**
     * Verifica que el usuario autenticado sea admin o sensei.
     * Si no, aborta con 403.
     */
    private function soloAdminOSensei(): void
    {
        if (!$this->esAdmin() && !$this->esSensei()) {
            abort(403, 'Acceso no autorizado.');
        }
    }

    // ── index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->soloAdminOSensei();

        $query = DB::table('usuario');

        // Sensei NO puede ver administradores
        if ($this->esSensei()) {
            $query->where('rol', '!=', 'admin');
        }

        // Filtros opcionales (buscar, rol, estado)
        // Usar ?? para garantizar que todas las claves existen aunque no vengan en la URL
        $filtros = [
            'buscar' => $request->input('buscar', ''),
            'rol'    => $request->input('rol',    ''),
            'estado' => $request->input('estado', ''),
        ];

        if (!empty($filtros['buscar'])) {
            $b = $filtros['buscar'];
            $query->where(function ($q) use ($b) {
                $q->where('nombre',   'like', "%{$b}%")
                  ->orWhere('apaterno', 'like', "%{$b}%")
                  ->orWhere('amaterno', 'like', "%{$b}%")
                  ->orWhere('correo',   'like', "%{$b}%");
            });
        }

        if (!empty($filtros['rol'])) {
            // Sensei no puede filtrar por 'admin' aunque lo intente
            if ($this->esSensei() && $filtros['rol'] === 'admin') {
                $filtros['rol'] = '';
            } else {
                $query->where('rol', $filtros['rol']);
            }
        }

        if ($filtros['estado'] !== '') {
            $query->where('estado', (int) $filtros['estado']);
        }

        $usuarios = $query->get();

        return view('usuariosViews.usuarios', compact('usuarios', 'filtros'));
    }

    // ── store ────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $this->soloAdminOSensei();

        $validated = $request->validate([
            'nombre'         => 'required|string|max:100',
            'apaterno'       => 'required|string|max:100',
            'amaterno'       => 'required|string|max:100',
            'fecha_naci'     => 'required|date',
            'tel'            => 'required|string|max:10',
            'correo'         => 'required|email|unique:usuario,correo',
            'pass'           => 'required|min:6',
            'rol'            => 'required|in:admin,sensei,tutor,alumno',
        ]);

        // Sensei NO puede crear administradores
        if ($this->esSensei() && $validated['rol'] === 'admin') {
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'No tienes permisos para crear usuarios administradores.');
        }

        try {
            DB::table('usuario')->insert([
                'nombre'         => $validated['nombre'],
                'apaterno'       => $validated['apaterno'],
                'amaterno'       => $validated['amaterno'],
                'fecha_naci'     => $validated['fecha_naci'],
                'telefono'       => $validated['tel'],
                'correo'         => $validated['correo'],
                'pass'           => Hash::make($validated['pass']),
                'rol'            => $validated['rol'],
                'fecha_registro' => now()->toDateString(),
                'estado'         => 1,
            ]);

            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', '¡Usuario registrado con éxito!');

        } catch (\Exception $e) {
            Log::error('UsuarioController@store: ' . $e->getMessage());
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Hubo un error al registrar el usuario.');
        }
    }

    // ── edit ─────────────────────────────────────────────────────────────────

    public function edit(int $id)
    {
        $this->soloAdminOSensei();

        $usuario = DB::table('usuario')->where('id_usuario', $id)->first();

        if (!$usuario) {
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Usuario no encontrado para edición.');
        }

        // Sensei no puede editar administradores
        if ($this->esSensei() && $usuario->rol === 'admin') {
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'No tienes permisos para editar administradores.');
        }

        return view('usuariosViews.editarUsu', compact('usuario'));
    }

    // ── update ───────────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $this->soloAdminOSensei();

        // Obtener el usuario actual para validar restricciones
        $usuarioActual = DB::table('usuario')->where('id_usuario', $id)->first();

        if (!$usuarioActual) {
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Usuario no encontrado.');
        }

        // Sensei no puede editar administradores
        if ($this->esSensei() && $usuarioActual->rol === 'admin') {
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'No tienes permisos para editar administradores.');
        }

        $validated = $request->validate([
            'nombre'         => 'required|string|max:100',
            'apaterno'       => 'required|string|max:100',
            'amaterno'       => 'required|string|max:100',
            'fecha_naci'     => 'required|date',
            'tel'            => 'required|string|max:20',
            'correo'         => 'required|email|unique:usuario,correo,' . $id . ',id_usuario',
            'rol'            => 'required|in:admin,sensei,tutor,alumno',
            'pass'           => 'nullable|min:6',
        ]);

        // Sensei no puede asignar ni escalar a rol admin
        if ($this->esSensei() && $validated['rol'] === 'admin') {
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'No tienes permisos para asignar el rol de administrador.');
        }

        try {
            $data = [
                'nombre'         => $validated['nombre'],
                'apaterno'       => $validated['apaterno'],
                'amaterno'       => $validated['amaterno'],
                'fecha_naci'     => $validated['fecha_naci'],
                'telefono'       => $validated['tel'],
                'correo'         => $validated['correo'],
                'rol'            => $validated['rol'],
            ];

            if (!empty($validated['pass'])) {
                $data['pass'] = Hash::make($validated['pass']);
            }

            DB::table('usuario')->where('id_usuario', $id)->update($data);

            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', '¡Usuario con ID ' . $id . ' actualizado con éxito!');

        } catch (\Exception $e) {
            Log::error("UsuarioController@update ID $id: " . $e->getMessage());
            return redirect()->route('editarUsu', $id)
                ->withInput()
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    // ── destroy ──────────────────────────────────────────────────────────────

    public function destroy(int $id)
{
    // Solo admin puede eliminar
    if (!$this->esAdmin()) {
        return redirect()->route('usuarios.index')
            ->with('sessionInsertado', 'false')
            ->with('mensaje', 'No tienes permisos para eliminar usuarios.');
    }

    try {
        $usuario = DB::table('usuario')->where('id_usuario', $id)->first();

        if (!$usuario) {
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'No se encontró el usuario con ID ' . $id . ' para eliminar.');
        }

        // Evitar que el admin se elimine a sí mismo
        if (Auth::id() === $id) {
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'No puedes eliminarte a ti mismo.');
        }

        // ── 1. Eliminar registros hijos en orden correcto ──────────────────

        // abono.registrado_por → luego los abonos de los pagos del usuario
        DB::table('abono')->where('registrado_por', $id)->delete();

        $pagoIds = DB::table('pago')->where('id_usuario', $id)->pluck('id_pago');
        if ($pagoIds->isNotEmpty()) {
            DB::table('abono')->whereIn('id_pago', $pagoIds)->delete();
        }
        DB::table('pago')->where('id_usuario', $id)->delete();

        // asistencia.id_usuario / asistencia.registrado_por
        DB::table('asistencia')
            ->where('id_usuario', $id)
            ->orWhere('registrado_por', $id)
            ->delete();

        // historial_seminarios.id_usuario
        // (seminario tiene ON DELETE CASCADE desde historial_seminarios,
        //  así que al borrar historial se borran los seminarios relacionados)
        DB::table('historial_seminarios')->where('id_usuario', $id)->delete();

        // historial_grados.id_usuario
        DB::table('historial_grados')->where('id_usuario', $id)->delete();

        // registro_fisico.id_usuario
        DB::table('registro_fisico')->where('id_usuario', $id)->delete();

        // calendario.id_usuario
        DB::table('calendario')->where('id_usuario', $id)->delete();

        // evento.id_usuario
        DB::table('evento')->where('id_usuario', $id)->delete();

        // tutor.id_tutor
        DB::table('tutor')->where('id_tutor', $id)->delete();

        // ubicacion_dojo tiene ON DELETE CASCADE, pero por limpieza explícita:
        DB::table('ubicacion_dojo')->where('guardado_por', $id)->delete();

        // ── 2. Eliminar el usuario ─────────────────────────────────────────
        DB::table('usuario')->where('id_usuario', $id)->delete();

        return redirect()->route('usuarios.index')
            ->with('sessionInsertado', 'true')
            ->with('mensaje', '¡Usuario con ID ' . $id . ' eliminado con éxito!');

    } catch (\Exception $e) {
        Log::error("UsuarioController@destroy ID $id: " . $e->getMessage());
        return redirect()->route('usuarios.index')
            ->with('sessionInsertado', 'false')
            ->with('mensaje', 'Error al eliminar el usuario: ' . $e->getMessage());
    }
}

    // ── toggleActive ─────────────────────────────────────────────────────────

    public function toggleActive(int $id)
    {
        $this->soloAdminOSensei();

        try {
            $usuario = DB::table('usuario')->where('id_usuario', $id)->first();

            if (!$usuario) {
                return redirect()->route('usuarios.index')
                    ->with('sessionInsertado', 'false')
                    ->with('mensaje', 'Usuario no encontrado.');
            }

            // Nadie puede desactivarse a sí mismo
            if (Auth::id() === $id) {
                return redirect()->route('usuarios.index')
                    ->with('sessionInsertado', 'false')
                    ->with('mensaje', 'No puedes cambiar tu propio estado de acceso.');
            }

            // Sensei no puede activar/desactivar administradores
            if ($this->esSensei() && $usuario->rol === 'admin') {
                return redirect()->route('usuarios.index')
                    ->with('sessionInsertado', 'false')
                    ->with('mensaje', 'No tienes permisos para cambiar el estado de un administrador.');
            }

            $nuevoEstado = $usuario->estado == 1 ? 0 : 1;
            $accion      = $nuevoEstado == 1 ? 'Activado' : 'Desactivado';

            DB::table('usuario')->where('id_usuario', $id)->update(['estado' => $nuevoEstado]);

            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'true')
                ->with('mensaje', "¡Usuario ID $id ha sido $accion con éxito!");

        } catch (\Exception $e) {
            Log::error("UsuarioController@toggleActive ID $id: " . $e->getMessage());
            return redirect()->route('usuarios.index')
                ->with('sessionInsertado', 'false')
                ->with('mensaje', 'Error al cambiar el estado del usuario.');
        }
    }

    // ── Métodos legacy ───────────────────────────────────────────────────────

    public function VerLogin()
    {
        return view('login');
    }

    public function show() {}

    public function confirmMail($correo) {}
}