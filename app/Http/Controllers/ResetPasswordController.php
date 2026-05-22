<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\cambiarcontrasenniaMailable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * ResetPasswordController
 *
 * El token de recuperación ya NO usa una tabla separada.
 * Se guardan 3 columnas directamente en la tabla 'usuario':
 *   - token_recuperacion      VARCHAR(255)  — el token UUID
 *   - token_expiracion        DATETIME      — cuándo vence (10 min)
 *   - ultima_solicitud_token  DATETIME      — cuándo se pidió por última vez
 *
 * Flujo:
 *   1. Usuario solicita reset  → se escriben las 3 columnas en usuario
 *   2. Usuario hace clic en el enlace del correo → se valida token y expiración
 *   3. Usuario cambia contraseña → se limpian las 3 columnas (NULL)
 *
 * Claves de sesión flash utilizadas:
 *   - sessionRecuperarContrasennia → leída en olvidosucontrasennia.blade.php
 *   - sessionCambiarContrasennia   → leída en la vista del LOGIN (solo éxito final)
 */
class ResetPasswordController extends Controller
{
    // ── Mostrar formulario "olvidé mi contraseña" ─────────────────────────────

    public function showResetForm()
    {
        return view('ResetPasswordViews.olvidosucontrasennia');
    }

    // ── Mostrar formulario de nueva contraseña con token ──────────────────────

    public function showResetFormWithToken($token)
    {
        try {
            // Buscar el token directamente en tabla usuario
            $usuario = DB::table('usuario')
                ->where('token_recuperacion', $token)
                ->first();

            if (!$usuario) {
                // ✅ CORREGIDO: redirige a password.request con clave sessionRecuperarContrasennia
                //    para que olvidosucontrasennia.blade.php pueda mostrar el SweetAlert
                return redirect()->route('password.request')
                    ->with('sessionRecuperarContrasennia', 'false')
                    ->with('mensaje', 'Enlace incorrecto o ya fue utilizado. Solicita uno nuevo.');
            }

            // Verificar que no haya expirado
            if (!$usuario->token_expiracion || Carbon::parse($usuario->token_expiracion)->isPast()) {
                // Limpiar token vencido
                DB::table('usuario')
                    ->where('token_recuperacion', $token)
                    ->update([
                        'token_recuperacion' => null,
                        'token_expiracion'   => null,
                    ]);

                // ✅ CORREGIDO: redirige a password.request con clave sessionRecuperarContrasennia
                return redirect()->route('password.request')
                    ->with('sessionRecuperarContrasennia', 'false')
                    ->with('mensaje', 'El enlace ha expirado. Por favor, solicita uno nuevo.');
            }

            return view('ResetPasswordViews.cambiarcontrasennia', ['token' => $token]);

        } catch (\Exception $e) {
            Log::error('ResetPassword@showResetFormWithToken: ' . $e->getMessage());

            // ✅ CORREGIDO: redirige a password.request con clave sessionRecuperarContrasennia
            return redirect()->route('password.request')
                ->with('sessionRecuperarContrasennia', 'false')
                ->with('mensaje', 'Hubo un error en el servidor. Inténtalo de nuevo.');
        }
    }

    // ── Enviar correo con enlace de recuperación ──────────────────────────────

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
        ], [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email'    => 'Por favor ingresa un correo válido.',
        ]);

        $correo = $request->input('correo');

        try {
            $usuario = DB::table('usuario')
                ->select('id_usuario', 'nombre', 'correo', 'ultima_solicitud_token')
                ->where('correo', $correo)
                ->where('estado', 1)
                ->first();

            // Respuesta genérica — no revelar si el correo existe
            // ✅ CORREGIDO: redirige a password.request (no al login) para que el
            //    SweetAlert de olvidosucontrasennia.blade.php pueda mostrarse
            if (!$usuario) {
                return redirect()->route('password.request')
                    ->with('sessionRecuperarContrasennia', 'true')
                    ->with('mensaje', '¡Listo! Si el correo está registrado, recibirás el enlace de recuperación.');
            }

            /*
            // Prevenir spam: mínimo 2 minutos entre solicitudes
            if ($usuario->ultima_solicitud_token) {
                $ultimaSolicitud = Carbon::parse($usuario->ultima_solicitud_token);
                if ($ultimaSolicitud->diffInMinutes(Carbon::now()) < 2) {
                    return redirect()->route('password.request')
                        ->with('sessionRecuperarContrasennia', 'true')
                        ->with('mensaje', '¡Listo! Si el correo está registrado, recibirás el enlace de recuperación.');
                }
            }*/

            $token    = Str::uuid()->toString();
            $expiraEn = Carbon::now()->addMinutes(10);

            // Guardar token directamente en la tabla usuario
            DB::table('usuario')
                ->where('id_usuario', $usuario->id_usuario)
                ->update([
                    'token_recuperacion'     => $token,
                    'token_expiracion'       => $expiraEn,
                    'ultima_solicitud_token' => Carbon::now(),
                ]);

            Mail::to($correo)->send(new cambiarcontrasenniaMailable($usuario->nombre, $token));

            Log::info("Token de recuperación generado para: {$correo}");

            // ✅ CORREGIDO: redirige a password.request para que el SweetAlert se muestre
            return redirect()->route('password.request')
                ->with('sessionRecuperarContrasennia', 'true')
                ->with('mensaje', '¡Listo! Revisa tu correo para el enlace de recuperación.');

        } catch (\Exception $e) {
            Log::error('ResetPassword@sendResetLinkEmail: ' . $e->getMessage());

            // ✅ CORREGIDO: redirige a password.request con clave sessionRecuperarContrasennia
            return redirect()->route('password.request')
                ->with('sessionRecuperarContrasennia', 'false')
                ->with('mensaje', 'Hubo un error al enviar el correo. Inténtalo de nuevo.');
        }
    }

    // ── Actualizar contraseña ─────────────────────────────────────────────────

    public function resetPassword(Request $request)
    {
        Log::info('=== INICIO resetPassword ===');

        try {
            $validated = $request->validate([
                'contrasennia'   => 'required|min:8',
                'recontrasennia' => 'required|same:contrasennia',
                'mytoken'        => 'required',
            ]);

            $token = $request->mytoken;

            // Buscar usuario con este token en la tabla usuario
            $usuario = DB::table('usuario')
                ->where('token_recuperacion', $token)
                ->first();

            if (!$usuario) {
                Log::warning('Token no encontrado: ' . $token);

                // ✅ CORREGIDO: redirige a password.request con clave sessionRecuperarContrasennia
                return redirect()->route('password.request')
                    ->with('sessionRecuperarContrasennia', 'false')
                    ->with('mensaje', 'El enlace no es válido o ya fue utilizado. Solicita uno nuevo.');
            }

            // Verificar expiración
            if (!$usuario->token_expiracion || Carbon::parse($usuario->token_expiracion)->isPast()) {
                Log::warning('Token expirado: ' . $token);

                // Limpiar token vencido
                DB::table('usuario')
                    ->where('id_usuario', $usuario->id_usuario)
                    ->update([
                        'token_recuperacion' => null,
                        'token_expiracion'   => null,
                    ]);

                // ✅ CORREGIDO: redirige a password.request con clave sessionRecuperarContrasennia
                return redirect()->route('password.request')
                    ->with('sessionRecuperarContrasennia', 'false')
                    ->with('mensaje', 'El enlace ha expirado. Solicita uno nuevo.');
            }

            DB::beginTransaction();

            // Actualizar contraseña y limpiar las 3 columnas de token
            DB::table('usuario')
                ->where('id_usuario', $usuario->id_usuario)
                ->update([
                    'pass'               => Hash::make($request->contrasennia),
                    'token_recuperacion' => null,
                    'token_expiracion'   => null,
                    // ultima_solicitud_token se mantiene (registro de auditoría)
                ]);

            DB::commit();

            Log::info('Contraseña actualizada para usuario ID: ' . $usuario->id_usuario);

            // ✅ ÉXITO: este es el único caso que redirige al login.
            //    Asegúrate de agregar el SweetAlert en tu vista de login que lea
            //    sessionCambiarContrasennia == 'true' / 'false'
            return redirect()->route('login')
                ->with('sessionCambiarContrasennia', 'true')
                ->with('mensaje', '¡Contraseña cambiada exitosamente! Ya puedes iniciar sesión.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validación resetPassword: ', $e->errors());
            return back()->withErrors($e->errors())->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ResetPassword@resetPassword: ' . $e->getMessage());

            // ✅ CORREGIDO: redirige a password.request con clave sessionRecuperarContrasennia
            return redirect()->route('password.request')
                ->with('sessionRecuperarContrasennia', 'false')
                ->with('mensaje', 'Hubo un error al actualizar la contraseña. Inténtalo de nuevo.');
        }
    }

    // ── Limpiar tokens vencidos (llamar desde Scheduler) ─────────────────────
    // En routes/console.php o Kernel.php:
    // Schedule::call([ResetPasswordController::class, 'purgarTokensVencidos'])->daily();

    public static function purgarTokensVencidos(): void
    {
        $afectados = DB::table('usuario')
            ->whereNotNull('token_recuperacion')
            ->where('token_expiracion', '<', Carbon::now())
            ->update([
                'token_recuperacion' => null,
                'token_expiracion'   => null,
            ]);

        Log::info("Tokens vencidos limpiados: {$afectados} usuario(s).");
    }
}