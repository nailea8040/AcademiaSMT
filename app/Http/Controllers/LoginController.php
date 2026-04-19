<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Usuario;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'contra' => 'required|string',
        ]);

        $usuario = Usuario::where('correo', $request->correo)->first();

        // 1. Verificar que el usuario exista y la contraseña sea correcta
        if (!$usuario || !Hash::check($request->contra, $usuario->pass)) {
            Log::warning('Login fallido: ' . $request->correo);
            return back()
                ->withInput($request->only('correo'))
                ->withErrors(['login_fallido' => 'Correo o contraseña incorrectos.']);
        }

        // 2. Verificar que la cuenta esté activa
        // Usar (int) para evitar comparación string vs integer (BD devuelve '1' como string)
        if ((int) $usuario->estado !== 1) {
            Log::warning('Login con cuenta inactiva: ' . $request->correo);
            return back()
                ->withInput($request->only('correo'))
                ->withErrors(['cuenta_inactiva' => 'Su cuenta está inactiva. Contacte al administrador del sistema.']);
        }

        // 3. Login exitoso
        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->intended(route('principal'))
            ->with('success', '¡Bienvenido, ' . $usuario->nombre . '!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/landing')->with('status', 'Sesión cerrada con éxito.');
    }
}