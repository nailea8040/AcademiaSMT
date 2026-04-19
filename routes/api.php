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

/*
|--------------------------------------------------------------------------
| API Routes - Academia de Karate-Do
|--------------------------------------------------------------------------
|
| Prefijo base: /api
| Autenticación: Laravel Sanctum  →  Header: Authorization: Bearer {token}
|
| Rutas públicas (sin token):
|   POST   /api/login
|   POST   /api/registro
|   POST   /api/password/forgot
|   POST   /api/password/reset
|   POST   /api/contacto
|   GET    /api/calendario          (ver eventos sin login)
|   GET    /api/galeria             (ver galería sin login)
|
| Rutas protegidas (requieren token):
|   Todo lo demás
|
*/

// ── Rutas públicas ────────────────────────────────────────────────────────
Route::post('/login',            [AuthApiController::class, 'login']);
Route::post('/registro',         [RegistroApiController::class, 'store']);
Route::post('/password/forgot',  [AuthApiController::class, 'forgotPassword']);
Route::post('/password/reset',   [AuthApiController::class, 'resetPassword']);
Route::post('/contacto',         [ContactoApiController::class, 'enviar']);
Route::get('/calendario',        [CalendarioApiController::class, 'index']);
Route::get('/galeria',           [GaleriaApiController::class, 'index']);


// ── Rutas protegidas ──────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Autenticación ──────────────────────────────────────────
    Route::post('/logout',      [AuthApiController::class, 'logout']);
    Route::post('/logout-all',  [AuthApiController::class, 'logoutAll']);
    Route::get('/me',           [AuthApiController::class, 'me']);

    // ── Perfil (cualquier usuario autenticado) ─────────────────
    Route::put('/perfil', [UsuarioApiController::class, 'updatePerfil']);

    // ── Usuarios (CRUD para admin) ─────────────────────────────
    Route::get('/usuarios',                    [UsuarioApiController::class, 'index']);
    Route::post('/usuarios',                   [UsuarioApiController::class, 'store']);
    Route::get('/usuarios/{id}',               [UsuarioApiController::class, 'show']);
    Route::put('/usuarios/{id}',               [UsuarioApiController::class, 'update']);
    Route::delete('/usuarios/{id}',            [UsuarioApiController::class, 'destroy']);
    Route::patch('/usuarios/{id}/toggle-estado', [UsuarioApiController::class, 'toggleEstado']);

    // ── Alumnos ────────────────────────────────────────────────
    Route::get('/alumnos',                          [AlumnoApiController::class, 'index']);
    Route::post('/alumnos',                         [AlumnoApiController::class, 'store']);
    Route::put('/alumnos/{id}',                     [AlumnoApiController::class, 'update']);
    Route::get('/alumnos/{id}/historial-grados',    [AlumnoApiController::class, 'historialGrados']);

    // ── Tutores ────────────────────────────────────────────────
    Route::get('/tutores',         [TutorApiController::class, 'index']);
    Route::post('/tutores',        [TutorApiController::class, 'store']);
    Route::put('/tutores/{id}',    [TutorApiController::class, 'update']);

    // ── Pagos ──────────────────────────────────────────────────
    Route::get('/pagos',                         [PagoApiController::class, 'index']);
    Route::post('/pagos',                        [PagoApiController::class, 'store']);
    Route::get('/pagos/historial/{idUsuario}',   [PagoApiController::class, 'historialAlumno']);
    Route::get('/tipos-pago',                    [PagoApiController::class, 'tiposPago']);

    // ── Calendario (escritura solo admin) ──────────────────────
    Route::post('/calendario',         [CalendarioApiController::class, 'store']);
    Route::put('/calendario/{id}',     [CalendarioApiController::class, 'update']);
    Route::delete('/calendario/{id}',  [CalendarioApiController::class, 'destroy']);

    // ── Galería (escritura solo admin) ─────────────────────────
    Route::post('/galeria',         [GaleriaApiController::class, 'store']);
    Route::delete('/galeria/{id}',  [GaleriaApiController::class, 'destroy']);
    // Agregar estas rutas que son nuevas:
Route::get('/ocupaciones', [TutorApiController::class, 'ocupaciones']);
Route::delete('/galeria/evento', [GaleriaApiController::class, 'destroyEvento']);

});