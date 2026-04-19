<?php

// ════════════════════════════════════════════════════════════════════════════
//  routes/web.php  —  AcademiaSMT-API  (backend puro)
// ════════════════════════════════════════════════════════════════════════════
//
//  Este proyecto es una API pura. Las vistas Blade ya NO viven aquí.
//  Viven en Frontend-api (otro proyecto Laravel).
//
//  Este web.php solo conserva:
//    1. Una ruta de salud/status para verificar que el servidor está activo.
//    2. Las rutas de reset de contraseña por EMAIL (el enlace del correo
//       apunta a este dominio para validar el token y redirigir a la app).
//
//  Todas las demás rutas están en routes/api.php
//
// ════════════════════════════════════════════════════════════════════════════

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResetPasswordController;

// ── Health check ─────────────────────────────────────────────────────────────
// GET /  →  confirma que la API está corriendo
Route::get('/', function () {
    return response()->json([
        'app'     => 'Academia SMT — API',
        'status'  => 'online',
        'version' => '1.0',
    ]);
});

// ── Reset de contraseña por enlace de email ───────────────────────────────────
// El correo de recuperación envía un enlace como:
//   https://api.academia.com/password/reset/{token}
//
// Esta ruta NO devuelve una vista Blade. Redirige a la app móvil/web con el token
// para que el usuario ingrese su nueva contraseña desde la app.
//
// Opción A (si solo tienes app móvil):
//   → Redirigir a un deep link:  academia://reset-password?token={token}
//
// Opción B (si tienes frontend web separado):
//   → Redirigir al Frontend-api:  http://frontend.com/nueva-contrasenna?token={token}
//
// Descomenta la opción que uses:

Route::get('/password/reset/{token}', function (string $token) {

    // ── OPCIÓN A: deep link para app móvil ─────────────────────────────
    // return redirect('academia://reset-password?token=' . $token);

    // ── OPCIÓN B: redirigir al frontend web ────────────────────────────
    $frontendUrl = config('app.frontend_url', 'http://localhost:8001');
    return redirect($frontendUrl . '/nueva-contrasenna?token=' . $token);

})->name('password.reset');