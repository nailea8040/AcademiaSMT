<?php
// routes/api.php — REEMPLAZA COMPLETO

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
use App\Http\Controllers\Api\UbicacionApiController;
use App\Http\Controllers\Api\SeminarioApiController;

// ════════════════════════════════════════════════════════════════════════════
//  RUTAS PÚBLICAS — sin token
// ════════════════════════════════════════════════════════════════════════════

// Rate limiting: 5 intentos de login por minuto, 3 solicitudes de recuperación por minuto
Route::post('/login',           [AuthApiController::class,    'login'])->middleware('throttle:5,1');
Route::post('/registro',        [RegistroApiController::class,'store'])->middleware('throttle:10,1');
Route::post('/password/forgot', [AuthApiController::class,    'forgotPassword'])->middleware('throttle:3,1');
Route::post('/password/reset',  [AuthApiController::class,    'resetPassword'])->middleware('throttle:5,1');
Route::post('/contacto',        [ContactoApiController::class,'enviar'])->middleware('throttle:10,1');

// Catálogos públicos
Route::get('/calendario',  [CalendarioApiController::class,'index']);
Route::get('/galeria',     [GaleriaApiController::class,   'index']);
Route::get('/grados',      [GradoApiController::class,     'index']);
Route::get('/seminarios',  [SeminarioApiController::class, 'index']);

// ── Webhook de MercadoPago ────────────────────────────────────────────────
// DEBE ser público: MP no envía token Bearer.
// CRÍTICO 3 FIX: la verificación de firma x-signature se hace DENTRO
// del método webhook() del controlador, no aquí en la ruta.
Route::post('/pagos/webhook', [PagoApiController::class, 'webhook']);

// ════════════════════════════════════════════════════════════════════════════
//  RUTAS PROTEGIDAS — requieren Bearer token (Sanctum)
// ════════════════════════════════════════════════════════════════════════════

Route::middleware('auth:sanctum')->group(function () {

    // ── Autenticación ─────────────────────────────────────────────────────
    Route::post('/logout',     [AuthApiController::class, 'logout']);
    Route::post('/logout-all', [AuthApiController::class, 'logoutAll']);
    Route::get('/me',          [AuthApiController::class, 'me']);

    // ── Perfil propio ─────────────────────────────────────────────────────
    Route::put('/perfil', [UsuarioApiController::class, 'updatePerfil']);

    // ── Alumnos vinculados al tutor autenticado ───────────────────────────
    Route::get('/me/alumnos', [TutorApiController::class, 'alumnosRelacionados']);

    // ── Usuarios ──────────────────────────────────────────────────────────
    Route::get('/usuarios',                      [UsuarioApiController::class, 'index']);
    Route::post('/usuarios',                     [UsuarioApiController::class, 'store']);
    Route::get('/usuarios/{id}',                 [UsuarioApiController::class, 'show']);
    Route::put('/usuarios/{id}',                 [UsuarioApiController::class, 'update']);
    Route::delete('/usuarios/{id}',              [UsuarioApiController::class, 'destroy']);
    Route::patch('/usuarios/{id}/toggle-estado', [UsuarioApiController::class, 'toggleEstado']);

    // ── Alumnos ───────────────────────────────────────────────────────────
    Route::get('/alumnos',                           [AlumnoApiController::class, 'index']);
    Route::post('/alumnos',                          [AlumnoApiController::class, 'store']);
    Route::put('/alumnos/{id}',                      [AlumnoApiController::class, 'update']);
    Route::get('/alumnos/{id}/historial-grados',     [AlumnoApiController::class, 'historialGrados']);
    Route::get('/alumnos/{id}/historial-seminarios', [AlumnoApiController::class, 'historialSeminarios']);

    // ── Seminarios ────────────────────────────────────────────────────────
    Route::post  ('/seminarios',                        [SeminarioApiController::class, 'store']);
    Route::put   ('/seminarios/{id}',                   [SeminarioApiController::class, 'update']);
    Route::delete('/seminarios/{id}',                   [SeminarioApiController::class, 'destroy']);
    Route::post  ('/alumnos/{id}/historial-seminarios', [SeminarioApiController::class, 'storeHistorial']);
    Route::delete('/historial-seminarios/{id}',         [SeminarioApiController::class, 'destroyHistorial']);

    // ── Tutores ───────────────────────────────────────────────────────────
    Route::get('/tutores',      [TutorApiController::class, 'index']);
    Route::post('/tutores',     [TutorApiController::class, 'store']);
    Route::put('/tutores/{id}', [TutorApiController::class, 'update']);
    Route::get('/ocupaciones',  [TutorApiController::class, 'ocupaciones']);

    // ── Pagos ─────────────────────────────────────────────────────────────
    // IMPORTANTE: rutas estáticas ANTES de las dinámicas {id}

    Route::get('/pagos',  [PagoApiController::class, 'index']);
    Route::post('/pagos', [PagoApiController::class, 'store']);

    Route::get('/tipos-pago', [PagoApiController::class, 'tiposPago']);

    // Pagos de un alumno específico (tutor)
    Route::get('/pagos/alumno/{id_alumno}', [PagoApiController::class, 'pagosAlumnoTutor']);

    // Historial por usuario
    Route::get('/pagos/historial/{idUsuario}', [PagoApiController::class, 'historialAlumno']);

    // Rutas con {id} — van después de las estáticas
    Route::get('/pagos/{id}/preference', [PagoApiController::class, 'getPreference']);
    Route::get('/pagos/{id}/abonos',     [PagoApiController::class, 'listarAbonos']);
    Route::post('/pagos/{id}/abono',     [PagoApiController::class, 'abono']);
    Route::post('/pagos/{id}/completar', [PagoApiController::class, 'completar']);

    // ── CRÍTICO 1 FIX: conceptos-pago DENTRO del middleware auth:sanctum ──
    // Antes estaban en el bloque público — cualquiera sin token podía
    // crear y editar conceptos de pago. Ahora requieren autenticación.
    Route::get('/conceptos-pago',         [PagoApiController::class, 'conceptosPago']);
    Route::post('/conceptos-pago',        [PagoApiController::class, 'storeConcepto']);
    Route::put('/conceptos-pago/{id}',    [PagoApiController::class, 'updateConcepto']);

    // ── Calendario ────────────────────────────────────────────────────────
    Route::post('/calendario',        [CalendarioApiController::class, 'store']);
    Route::put('/calendario/{id}',    [CalendarioApiController::class, 'update']);
    Route::delete('/calendario/{id}', [CalendarioApiController::class, 'destroy']);

    // ── Galería ───────────────────────────────────────────────────────────
    Route::post('/galeria',           [GaleriaApiController::class, 'store']);
    Route::delete('/galeria/evento',  [GaleriaApiController::class, 'destroyEvento']);
    Route::delete('/galeria/{id}',    [GaleriaApiController::class, 'destroy']);

    // ── Asistencia ────────────────────────────────────────────────────────
    Route::get('/asistencia',  [AsistenciaApiController::class, 'index']);
    Route::post('/asistencia', [AsistenciaApiController::class, 'store']);

    // ── Ubicación del dojo ────────────────────────────────────────────────
    Route::get('/ubicacion',  [UbicacionApiController::class, 'index']);
    Route::post('/ubicacion', [UbicacionApiController::class, 'store']);
});