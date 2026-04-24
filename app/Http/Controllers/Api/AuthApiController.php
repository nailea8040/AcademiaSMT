<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Usuario;
use App\Mail\cambiarcontrasenniaMailable;

class AuthApiController extends Controller
{
    /**
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'correo'      => 'required|email',
            'contra'      => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $usuario = Usuario::where('correo', $request->correo)->first();

        if (!$usuario || !Hash::check($request->contra, $usuario->pass)) {
            return response()->json([
                'success' => false,
                'message' => 'Correo o contraseña incorrectos.',
            ], 401);
        }

        if ((int) $usuario->estado !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Su cuenta está inactiva. Contacte al administrador.',
            ], 403);
        }

        $deviceName = $request->device_name ?? 'mobile';
        $usuario->tokens()->where('name', $deviceName)->delete();

        $token = $usuario->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'usuario' => [
                'id_usuario' => $usuario->id_usuario,
                'nombre'     => $usuario->nombre,
                'apaterno'   => $usuario->apaterno,
                'amaterno'   => $usuario->amaterno,
                'correo'     => $usuario->correo,
                'rol'        => $usuario->rol,       // devuelve 'admin', 'sensei', etc.
                'estado'     => $usuario->estado,
                'telefono'   => $usuario->telefono,
                'fecha_naci' => $usuario->fecha_naci,
                'avatar'        => $usuario->avatar ?? null,
                'numero_control' => $usuario->numero_control ?? null,
                'grupo'          => $usuario->grupo          ?? null,
                'especialidad'   => $usuario->especialidad   ?? null,
                'turno'          => $usuario->turno          ?? null,
            ],
        ]);
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true, 'message' => 'Sesión cerrada.']);
    }

    /**
     * POST /api/logout-all
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['success' => true, 'message' => 'Sesión cerrada en todos los dispositivos.']);
    }

    /**
     * GET /api/me
     */
    public function me(Request $request)
    {
        $usuario = $request->user();

        $gradoActual = DB::table('historial_grados as hg')
            ->join('grado as g', 'hg.id_grado', '=', 'g.id_grado')
            ->where('hg.id_usuario', $usuario->id_usuario)
            ->orderBy('hg.fecha_obtencion', 'desc')
            ->select('g.id_grado', 'g.nombreGrado', 'hg.fecha_obtencion')
            ->first();

        return response()->json([
            'success' => true,
            'usuario' => [
                'id_usuario'   => $usuario->id_usuario,
                'nombre'       => $usuario->nombre,
                'apaterno'     => $usuario->apaterno,
                'amaterno'     => $usuario->amaterno,
                'correo'       => $usuario->correo,
                'rol'          => $usuario->rol,
                'estado'       => $usuario->estado,
                'telefono'     => $usuario->telefono,
                'fecha_naci'   => $usuario->fecha_naci,
                'avatar'       => $usuario->avatar ?? null,
                'grado_actual' => $gradoActual,
            ],
        ]);
    }

    /**
     * POST /api/password/forgot
     * Token guardado en tabla usuario (sin tabla password_resets)
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['correo' => 'required|email']);

        $correo = $request->correo;

        try {
            $usuario = DB::table('usuario')
                ->select('id_usuario', 'nombre', 'correo', 'ultima_solicitud_token')
                ->where('correo', $correo)
                ->where('estado', 1)
                ->first();

            // Respuesta genérica por seguridad
            if (!$usuario) {
                return response()->json([
                    'success' => true,
                    'message' => 'Si el correo está registrado, recibirás el enlace de recuperación.',
                ]);
            }

            // Anti-spam: mínimo 2 minutos entre solicitudes
            if ($usuario->ultima_solicitud_token) {
                $diff = Carbon::parse($usuario->ultima_solicitud_token)->diffInMinutes(Carbon::now());
                if ($diff < 2) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Si el correo está registrado, recibirás el enlace de recuperación.',
                    ]);
                }
            }

            $token    = Str::uuid()->toString();
            $expiraEn = Carbon::now()->addMinutes(10);

            // Guardar token en tabla usuario
            DB::table('usuario')
                ->where('id_usuario', $usuario->id_usuario)
                ->update([
                    'token_recuperacion'     => $token,
                    'token_expiracion'       => $expiraEn,
                    'ultima_solicitud_token' => Carbon::now(),
                ]);

            Mail::to($correo)->send(new cambiarcontrasenniaMailable($usuario->nombre, $token));

            return response()->json([
                'success' => true,
                'message' => 'Correo de recuperación enviado.',
            ]);

        } catch (\Exception $e) {
            Log::error('AuthApi@forgotPassword: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al enviar el correo.'], 500);
        }
    }

    /**
     * POST /api/password/reset
     * Token verificado en tabla usuario
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required|string',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        try {
            $usuario = DB::table('usuario')
                ->where('token_recuperacion', $request->token)
                ->first();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o ya fue utilizado.',
                ], 422);
            }

            if (!$usuario->token_expiracion || Carbon::parse($usuario->token_expiracion)->isPast()) {
                DB::table('usuario')
                    ->where('id_usuario', $usuario->id_usuario)
                    ->update(['token_recuperacion' => null, 'token_expiracion' => null]);

                return response()->json([
                    'success' => false,
                    'message' => 'El token ha expirado.',
                ], 422);
            }

            DB::beginTransaction();

            DB::table('usuario')
                ->where('id_usuario', $usuario->id_usuario)
                ->update([
                    'pass'               => Hash::make($request->password),
                    'token_recuperacion' => null,
                    'token_expiracion'   => null,
                ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Contraseña actualizada.']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AuthApi@resetPassword: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar la contraseña.'], 500);
        }
    }
}