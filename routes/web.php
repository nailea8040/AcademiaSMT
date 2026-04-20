<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\GaleriaController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\PerfilController;

// ════════════════════════════════════════════════════════════════════════════
//  RUTAS PÚBLICAS — sin login
// ════════════════════════════════════════════════════════════════════════════

// Landing
Route::get('/',        fn() => view('landing'))->name('landing');
Route::get('/landing', fn() => view('landing'));

// Contacto (formulario del landing)
Route::post('/contacto/enviar', [ContactoController::class, 'enviar'])->name('contacto.enviar');

// Login / logout
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::get('/logout', fn() => redirect()->route('login'));
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// Alias legacy (algunos blade usan route('verLogin'))
Route::get('/ver-login', [LoginController::class, 'showLoginForm'])->name('verLogin');

// Registro
Route::get('/registro',  [RegistroController::class, 'create'])->name('registro.create');
Route::post('/registro', [RegistroController::class, 'store'])->name('registro.store');

// Recuperación de contraseña
// Las 3 columnas (token_recuperacion, token_expiracion, ultima_solicitud_token)
// ya están en la tabla usuario — no se usa tabla password_resets
Route::get('/olvido-contrasennia',     [ResetPasswordController::class, 'showResetForm'])->name('password.request');
Route::post('/olvido-contrasennia',    [ResetPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetFormWithToken'])->name('password.reset');
Route::put('/password/update',        [ResetPasswordController::class, 'resetPassword'])->name('password.update');

// Galería y calendario — lectura pública
Route::get('/galeria',    [GaleriaController::class,    'index'])->name('galeria.index');
Route::get('/calendario', [CalendarioController::class, 'index'])->name('calendario.index');

// ════════════════════════════════════════════════════════════════════════════
//  RUTAS PROTEGIDAS — requieren login (middleware 'auth')
//  El guard 'web' usa sesión Laravel con el modelo Usuario
// ════════════════════════════════════════════════════════════════════════════

Route::middleware('auth')->group(function () {

    // ── Dashboard ─────────────────────────────────────────────────────────
    Route::get('/principal', fn() => view('usuariosViews.principal'))->name('principal');

    // ── Perfil ────────────────────────────────────────────────────────────
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');

    // ── Galería (escritura) ───────────────────────────────────────────────
    Route::post('/galeria',            [GaleriaController::class, 'store'])->name('galeria.store');
    Route::delete('/galeria/{id}',     [GaleriaController::class, 'destroy'])->name('galeria.destroy');
    Route::delete('/galeria-evento',   [GaleriaController::class, 'destroyEvento'])->name('galeria.destroyEvento');

    // ── Calendario (escritura) ────────────────────────────────────────────
    // PK real en BD: id_cal
    Route::post('/calendario',         [CalendarioController::class, 'store'])->name('calendario.store');
    Route::put('/calendario/{id}',     [CalendarioController::class, 'update'])->name('calendario.update');
    Route::delete('/calendario/{id}',  [CalendarioController::class, 'destroy'])->name('calendario.destroy');

    // ── Alumnos ───────────────────────────────────────────────────────────
    Route::get('/alumnos',                    [AlumnoController::class, 'index'])->name('alumnos.index');
    Route::post('/alumnos',                   [AlumnoController::class, 'store'])->name('alumnos.store');
    Route::put('/alumnos/{id}',               [AlumnoController::class, 'update'])->name('alumnos.update');
    Route::get('/alumnos/{id}/historial',     [AlumnoController::class, 'historialGrados'])->name('alumnos.historial');

    // ── Pagos ─────────────────────────────────────────────────────────────
    Route::get('/pagos',                      [PagoController::class, 'index'])->name('pagos.index');
    Route::post('/pagos',                     [PagoController::class, 'store'])->name('pagos.store');
    Route::get('/pagos/{id}/historial',       [PagoController::class, 'historialAlumno'])->name('pagos.historial');

    // ── Tutores ───────────────────────────────────────────────────────────
    Route::get('/tutor',                      [TutorController::class, 'index'])->name('tutor.index');
    Route::post('/tutor',                     [TutorController::class, 'store'])->name('tutor.store');
    Route::put('/tutor/{id}',                 [TutorController::class, 'update'])->name('tutor.update');

    // ── Usuarios (CRUD completo) ──────────────────────────────────────────
    Route::get('/usuarios',                   [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios',                  [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}/edit',         [UsuarioController::class, 'edit'])->name('editarUsu');
    Route::put('/usuarios/{id}',              [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}',           [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    Route::post('/usuarios/{id}/toggle-active', [UsuarioController::class, 'toggleActive'])->name('usuarios.toggleActive');

    // ── Eventos multimedia (tabla evento: imágenes y videos) ──────────────
    // Distinto de CalendarioController — este maneja archivos multimedia
    Route::get('/eventos',                    [EventoController::class, 'index'])->name('eventos.index');
    Route::post('/eventos',                   [EventoController::class, 'store'])->name('eventos.store');
    Route::put('/eventos/{id}',               [EventoController::class, 'update'])->name('eventos.update');
    Route::delete('/eventos/{id}',            [EventoController::class, 'destroy'])->name('eventos.destroy');
});