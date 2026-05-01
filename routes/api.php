<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\UsuarioApiController;
use App\Http\Controllers\Api\AlumnoApiController;
use App\Http\Controllers\Api\TutorApiController;
use App\Http\Controllers\Api\PagoApiController;
use App\Http\Controllers\Api\CalendarioApiController;
use App\Http\Controllers\Api\GaleriaApiController;
use App\Http\Controllers\Api\RegistroApiController;
use App\Http\Controllers\Api\ContactoApiController;
use App\Http\Controllers\Api\GradoApiController;
use App\Http\Controllers\Api\AsistenciaApiController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\Api\UbicacionApiController;

// ════════════════════════════════════════════════════════════════════════════
//  RUTAS PÚBLICAS — sin token
// ════════════════════════════════════════════════════════════════════════════

Route::post('/login',            [AuthApiController::class,    'login']);
Route::post('/registro',         [RegistroApiController::class,'store']);
Route::post('/password/forgot',  [AuthApiController::class,    'forgotPassword']);
Route::post('/password/reset',   [AuthApiController::class,    'resetPassword']);
Route::post('/contacto',         [ContactoApiController::class,'enviar']);

// Catálogos públicos (necesarios para formularios de la app antes del login)
Route::get('/calendario',        [CalendarioApiController::class,'index']);
Route::get('/galeria',           [GaleriaApiController::class,   'index']);
Route::get('/grados',            [GradoApiController::class,     'index']);  // ← NUEVO

// ════════════════════════════════════════════════════════════════════════════
//  RUTAS PROTEGIDAS — requieren Bearer token (Sanctum)
// ════════════════════════════════════════════════════════════════════════════

Route::middleware('auth:sanctum')->group(function () {

    // ── Autenticación ─────────────────────────────────────────────────────
    Route::post('/logout',      [AuthApiController::class, 'logout']);
    Route::post('/logout-all',  [AuthApiController::class, 'logoutAll']);
    Route::get('/me',           [AuthApiController::class, 'me']);

    // ── Perfil propio ─────────────────────────────────────────────────────
    Route::put('/perfil', [UsuarioApiController::class, 'updatePerfil']);

    // ── Usuarios ──────────────────────────────────────────────────────────
    Route::get('/usuarios',                       [UsuarioApiController::class, 'index']);
    Route::post('/usuarios',                      [UsuarioApiController::class, 'store']);
    Route::get('/usuarios/{id}',                  [UsuarioApiController::class, 'show']);
    Route::put('/usuarios/{id}',                  [UsuarioApiController::class, 'update']);
    Route::delete('/usuarios/{id}',               [UsuarioApiController::class, 'destroy']);
    Route::patch('/usuarios/{id}/toggle-estado',  [UsuarioApiController::class, 'toggleEstado']);

    // ── Alumnos ───────────────────────────────────────────────────────────
    Route::get('/alumnos',                        [AlumnoApiController::class, 'index']);
    Route::post('/alumnos',                       [AlumnoApiController::class, 'store']);
    Route::put('/alumnos/{id}',                   [AlumnoApiController::class, 'update']);
    Route::get('/alumnos/{id}/historial-grados',  [AlumnoApiController::class, 'historialGrados']);

    // ── Tutores ───────────────────────────────────────────────────────────
    Route::get('/tutores',         [TutorApiController::class, 'index']);
    Route::post('/tutores',        [TutorApiController::class, 'store']);
    Route::put('/tutores/{id}',    [TutorApiController::class, 'update']);
    Route::get('/ocupaciones',     [TutorApiController::class, 'ocupaciones']); // ← catálogo

    // ── Pagos ─────────────────────────────────────────────────────────────
    Route::get('/pagos',                       [PagoApiController::class, 'index']);
    Route::post('/pagos',                      [PagoApiController::class, 'store']);
    Route::get('/pagos/historial/{id}',        [PagoApiController::class, 'historialAlumno']);
    Route::get('/tipos-pago',                  [PagoApiController::class, 'tiposPago']);

    // ── Calendario ────────────────────────────────────────────────────────
    Route::post('/calendario',        [CalendarioApiController::class, 'store']);
    Route::put('/calendario/{id}',    [CalendarioApiController::class, 'update']);
    Route::delete('/calendario/{id}', [CalendarioApiController::class, 'destroy']);

    // ── Galería ───────────────────────────────────────────────────────────
    Route::post('/galeria',           [GaleriaApiController::class, 'store']);
    Route::delete('/galeria/{id}',    [GaleriaApiController::class, 'destroy']);
    Route::delete('/galeria/evento',  [GaleriaApiController::class, 'destroyEvento']);
    // ← AGREGAR ESTA RUTA NUEVA:
Route::post('/galeria/{id}/destacado', [GaleriaController::class, 'toggleDestacado'])
     ->name('galeria.destacado');

    // ── Asistencia (escaneo QR) ───────────────────────────────────────────  ← NUEVO
   // Asistencia (ya existían, actualizadas)
Route::get('/asistencia',    [AsistenciaApiController::class, 'index']);   // ?fecha=
Route::post('/asistencia',   [AsistenciaApiController::class, 'store']);
 

    // ── Ubicación del dojo ────────────────────────────────────────────────  ← NUEVO
    Route::get('/ubicacion',     [UbicacionApiController::class, 'index']);
    Route::post('/ubicacion',    [UbicacionApiController::class, 'store']);
});