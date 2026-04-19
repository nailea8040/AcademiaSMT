<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * EditarUsuController — API REST
 *
 * En el proyecto web original estos métodos vivían en UsuarioController.
 * En la API se mantienen aquí para seguir la estructura del proyecto.
 *
 * Endpoints:
 *   GET  /api/v1/usuarios/{id}/editar  → show() — datos para precargar el form
 *   PUT  /api/v1/usuarios/{id}         → update() — ya definido en UsuarioController
 *
 * CORRECCIONES aplicadas:
 *   - Columna 'tel' → 'telefono' (nombre real en BD)
 *   - Rol 'administrador' → 'admin' (valor real del ENUM)
 *   - unique:usuario (no unique:usuarios)
 *   - Devuelve JSON en lugar de view/redirect
 */
class EditarUsuController extends Controller
{
    /**
     * GET /api/v1/usuarios/{id}/editar
     * Devuelve los datos actuales del usuario para precargar el formulario
     * en el frontend o la app móvil.
     */
    public function edit($id)
    {
        $usuario = DB::table('usuario')
            ->select(
                'id_usuario', 'nombre', 'apaterno', 'amaterno',
                'fecha_naci', 'telefono', 'correo', 'rol',
                'estado', 'fecha_registro'
            )
            ->where('id_usuario', $id)
            ->first();

        if (!$usuario) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Usuario no encontrado.',
            ], 404);
        }

        return response()->json([
            'ok'      => true,
            'usuario' => $usuario,
        ]);
    }

    /**
     * PUT /api/v1/usuarios/{id}/editar
     * Body JSON (todos los campos son opcionales excepto los que se quieran cambiar):
     * {
     *   "nombre":    "Carlos",
     *   "apaterno":  "Ramírez",
     *   "amaterno":  "López",
     *   "fecha_naci":"1990-05-20",
     *   "telefono":  "5512345678",
     *   "correo":    "nuevo@email.com",
     *   "rol":       "sensei",
     *   "pass":      "NuevaPass1!"   (opcional — solo si se quiere cambiar)
     * }
     */
    public function update(Request $request, $id)
    {
        $usuario = DB::table('usuario')->where('id_usuario', $id)->first();

        if (!$usuario) {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Usuario no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'nombre'     => 'sometimes|string|max:100',
            'apaterno'   => 'sometimes|string|max:100',
            'amaterno'   => 'sometimes|string|max:100',
            'fecha_naci' => 'sometimes|date',
            'telefono'   => 'sometimes|string|max:20',   // columna real: telefono
            'correo'     => 'sometimes|email|unique:usuario,correo,' . $id . ',id_usuario',
            'rol'        => 'sometimes|in:admin,sensei,tutor,alumno', // ENUM real
            'pass'       => 'sometimes|min:8',
        ]);

        try {
            $data = collect($validated)->except('pass')->toArray();

            if (!empty($validated['pass'])) {
                $data['pass'] = Hash::make($validated['pass']);
            }

            if (empty($data)) {
                return response()->json([
                    'ok'      => false,
                    'mensaje' => 'No se enviaron datos para actualizar.',
                ], 422);
            }

            DB::table('usuario')->where('id_usuario', $id)->update($data);

            $actualizado = DB::table('usuario')
                ->select(
                    'id_usuario', 'nombre', 'apaterno', 'amaterno',
                    'fecha_naci', 'telefono', 'correo', 'rol',
                    'estado', 'fecha_registro'
                )
                ->where('id_usuario', $id)
                ->first();

            return response()->json([
                'ok'      => true,
                'mensaje' => "Usuario ID {$id} actualizado.",
                'usuario' => $actualizado,
            ]);

        } catch (\Exception $e) {
            Log::error("EditarUsuController@update ID {$id}: " . $e->getMessage());
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Error al actualizar: ' . $e->getMessage(),
            ], 500);
        }
    }
}