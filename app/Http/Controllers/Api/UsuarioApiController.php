<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class UsuarioApiController extends Controller
{
    /**
     * GET /api/usuarios
     */
    public function index(Request $request)
    {
        $this->soloAdminOSensei($request);

        $query = DB::table('usuario')
            ->select('id_usuario', 'nombre', 'apaterno', 'amaterno',
                     'correo', 'rol', 'estado', 'telefono', 'fecha_naci', 'fecha_registro',
                     'numero_control', 'grupo', 'especialidad', 'turno')
            ->orderBy('nombre');

        // Sensei NO ve administradores — igual que UsuarioController web
        if ($request->user()->rol === 'sensei') {
            $query->where('rol', '!=', 'admin');
        }

        $usuarios = $query->get();

        return response()->json(['success' => true, 'data' => $usuarios]);
    }

    /**
     * POST /api/usuarios
     */
    public function store(Request $request)
    {
        $this->soloAdminOSensei($request);

        $validated = $request->validate([
            'nombre'         => 'required|string|max:100',
            'apaterno'       => 'required|string|max:100',
            'amaterno'       => 'required|string|max:100',
            'fecha_naci'     => 'required|date',
            'tel'            => 'required|string|max:10',
            'correo'         => 'required|email|unique:usuario,correo',
            'pass'           => 'required|min:6',
            // ENUM real en BD: 'admin', 'sensei', 'tutor', 'alumno'
            'rol'            => 'required|in:admin,sensei,tutor,alumno',
            // fecha_registro se genera en el servidor — igual que UsuarioController web
            'numero_control'  => 'nullable|string|max:20',
            'grupo'           => 'nullable|string|max:10',
            'especialidad'    => 'nullable|string|max:100',
            'turno'           => 'nullable|in:Matutino,Vespertino,Nocturno',
        ]);

        // Sensei no puede crear administradores — igual que UsuarioController web
        if ($request->user()->rol === 'sensei' && $validated['rol'] === 'admin') {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para crear administradores.'], 403);
        }

        try {
            $id = DB::table('usuario')->insertGetId([
                'nombre'         => $validated['nombre'],
                'apaterno'       => $validated['apaterno'],
                'amaterno'       => $validated['amaterno'],
                'fecha_naci'     => $validated['fecha_naci'],
                'telefono'       => $validated['tel'],
                'correo'         => $validated['correo'],
                'pass'           => Hash::make($validated['pass']),
                'rol'            => $validated['rol'],
                'fecha_registro' => now()->toDateString(), // generada en servidor
                'estado'         => 1,
            ]);

            return response()->json(['success' => true, 'message' => 'Usuario registrado.', 'id' => $id], 201);

        } catch (\Exception $e) {
            Log::error('UsuarioApi@store: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar.'], 500);
        }
    }

    /**
     * GET /api/usuarios/{id}
     */
    public function show(Request $request, int|string $id)
    {
        $authUser = $request->user();

        // Admin ve cualquier usuario; un usuario solo se ve a sí mismo
        if ($authUser->rol !== 'admin' && (int) $authUser->id_usuario !== (int) $id) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        $usuario = DB::table('usuario')
            ->where('id_usuario', $id)
            ->select('id_usuario', 'nombre', 'apaterno', 'amaterno',
                     'correo', 'rol', 'estado', 'telefono', 'fecha_naci')
            ->first();

        if (!$usuario) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        return response()->json(['success' => true, 'data' => $usuario]);
    }

    /**
     * PUT /api/usuarios/{id}
     */
    public function update(Request $request, int|string $id)
    {
        $authUser = $request->user();

        if ($authUser->rol !== 'admin' && (int) $authUser->id_usuario !== (int) $id) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        $validated = $request->validate([
            'nombre'     => 'required|string|max:100',
            'apaterno'   => 'required|string|max:100',
            'amaterno'   => 'required|string|max:100',
            'fecha_naci' => 'required|date',
            'tel'        => 'required|string|max:20',
            'correo'     => 'required|email|unique:usuario,correo,' . $id . ',id_usuario',
            'rol'            => 'required|in:admin,sensei,tutor,alumno',
            'pass'           => 'nullable|min:6',
            'numero_control' => 'nullable|string|max:20',
            'grupo'          => 'nullable|string|max:10',
            'especialidad'   => 'nullable|string|max:100',
            'turno'          => 'nullable|in:Matutino,Vespertino,Nocturno',
        ]);

        try {
            $data = [
                'nombre'     => $validated['nombre'],
                'apaterno'   => $validated['apaterno'],
                'amaterno'   => $validated['amaterno'],
                'fecha_naci' => $validated['fecha_naci'],
                'telefono'   => $validated['tel'],
                'correo'     => $validated['correo'],
                'rol'        => $validated['rol'],
            ];

            if (!empty($validated['pass'])) {
                $data['pass'] = Hash::make($validated['pass']);
            }

            DB::table('usuario')->where('id_usuario', $id)->update($data);

            return response()->json(['success' => true, 'message' => 'Usuario actualizado.']);

        } catch (\Exception $e) {
            Log::error('UsuarioApi@update: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar.'], 500);
        }
    }

    /**
     * DELETE /api/usuarios/{id}
     */
    public function destroy(Request $request, int|string $id)
    {
        $this->soloAdmin($request);

        try {
            $deleted = DB::table('usuario')->where('id_usuario', $id)->delete();

            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Usuario eliminado.']);

        } catch (\Exception $e) {
            Log::error('UsuarioApi@destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: tiene registros relacionados.',
            ], 409);
        }
    }

    /**
     * PATCH /api/usuarios/{id}/toggle-estado
     */
    public function toggleEstado(Request $request, int|string $id)
    {
        $this->soloAdminOSensei($request);

        $usuario = DB::table('usuario')->where('id_usuario', $id)->first();

        if (!$usuario) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        // Sensei no puede cambiar estado de administradores — igual que UsuarioController web
        if ($request->user()->rol === 'sensei' && $usuario->rol === 'admin') {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para cambiar el estado de un administrador.'], 403);
        }

        $nuevoEstado = $usuario->estado == 1 ? 0 : 1;
        DB::table('usuario')->where('id_usuario', $id)->update(['estado' => $nuevoEstado]);

        return response()->json([
            'success'      => true,
            'message'      => 'Estado actualizado.',
            'nuevo_estado' => $nuevoEstado,
        ]);
    }

    /**
     * PUT /api/perfil
     */
    public function updatePerfil(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'nombre'     => 'required|string|max:100',
            'apaterno'   => 'required|string|max:100',
            'amaterno'   => 'required|string|max:100',
            'fecha_naci' => 'required|date',
            'correo'     => 'required|email|unique:usuario,correo,' . $usuario->id_usuario . ',id_usuario',
            'tel'        => 'required|string|digits:10',
            'password'   => ['nullable', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        try {
            $data = [
                'nombre'     => $validated['nombre'],
                'apaterno'   => $validated['apaterno'],
                'amaterno'   => $validated['amaterno'],
                'fecha_naci' => $validated['fecha_naci'],
                'correo'     => $validated['correo'],
                'telefono'   => $validated['tel'],
            ];

            if ($request->filled('password')) {
                $data['pass'] = Hash::make($validated['password']);
            }

            DB::table('usuario')->where('id_usuario', $usuario->id_usuario)->update($data);

            return response()->json(['success' => true, 'message' => 'Perfil actualizado.']);

        } catch (\Exception $e) {
            Log::error('UsuarioApi@updatePerfil: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar perfil.'], 500);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    /** Solo admin — para operaciones destructivas (destroy). */
    private function soloAdmin(Request $request)
    {
        if ($request->user()->rol !== 'admin') {
            abort(response()->json(['success' => false, 'message' => 'Acceso solo para administradores.'], 403));
        }
    }

    /**
     * Admin o sensei — igual que UsuarioController web (soloAdminOSensei).
     * Usado en: index, store, update, toggleEstado.
     */
    private function soloAdminOSensei(Request $request)
    {
        if (!in_array($request->user()->rol, ['admin', 'sensei'])) {
            abort(response()->json(['success' => false, 'message' => 'Acceso solo para administradores y senseis.'], 403));
        }
    }
}