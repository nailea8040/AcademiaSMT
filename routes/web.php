<?php
// routes/web.php — REEMPLAZA COMPLETO

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\Api\PagoApiController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\GaleriaController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\PrincipalController;

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
Route::get('/olvido-contrasennia',     [ResetPasswordController::class, 'showResetForm'])->name('password.request');
Route::post('/olvido-contrasennia',    [ResetPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetFormWithToken'])->name('password.reset');
Route::put('/password/update',        [ResetPasswordController::class, 'resetPassword'])->name('password.update');

// Galería y calendario — lectura pública
Route::get('/galeria',    [GaleriaController::class,    'index'])->name('galeria.index');
Route::get('/calendario', [CalendarioController::class, 'index'])->name('calendario.index');

// ════════════════════════════════════════════════════════════════════════════
//  RUTAS PROTEGIDAS — requieren login (middleware 'auth')
// ════════════════════════════════════════════════════════════════════════════

Route::middleware('auth')->group(function () {

    // ── Dashboard ─────────────────────────────────────────────────────────
    Route::get('/principal', [PrincipalController::class, 'index'])->name('principal');

    // ── Perfil ────────────────────────────────────────────────────────────
    Route::get('/perfil', [PerfilController::class, 'index'])->name('perfil');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');

    // ── Galería (escritura) ───────────────────────────────────────────────
    Route::post('/galeria',                        [GaleriaController::class, 'store'])->name('galeria.store');
    Route::delete('/galeria/{id}',                 [GaleriaController::class, 'destroy'])->name('galeria.destroy');
    Route::delete('/galeria-evento',               [GaleriaController::class, 'destroyEvento'])->name('galeria.destroyEvento');
    Route::post('/galeria/{id}/destacado',         [GaleriaController::class, 'toggleDestacado'])->name('galeria.destacado');

    // ── Calendario (escritura) ────────────────────────────────────────────
    Route::post('/calendario',         [CalendarioController::class, 'store'])->name('calendario.store');
    Route::put('/calendario/{id}',     [CalendarioController::class, 'update'])->name('calendario.update');
    Route::delete('/calendario/{id}',  [CalendarioController::class, 'destroy'])->name('calendario.destroy');

    // ── Alumnos ───────────────────────────────────────────────────────────
    Route::get('/alumnos',                    [AlumnoController::class, 'index'])->name('alumnos.index');
    Route::post('/alumnos',                   [AlumnoController::class, 'store'])->name('alumnos.store');
    Route::put('/alumnos/{id}',               [AlumnoController::class, 'update'])->name('alumnos.update');
    Route::get('/alumnos/{id}/historial',     [AlumnoController::class, 'historialGrados'])->name('alumnos.historial');

    // Catálogo de grados
Route::post('/grados',       [AlumnoController::class, 'storeGrado'])->name('grados.store');
Route::put ('/grados/{id}',  [AlumnoController::class, 'updateGrado'])->name('grados.update');

// Catálogo de seminarios
Route::post  ('/seminarios',       [AlumnoController::class, 'storeSeminario'])  ->name('seminarios.store');
Route::put   ('/seminarios/{id}',  [AlumnoController::class, 'updateSeminario']) ->name('seminarios.update');
Route::delete('/seminarios/{id}',  [AlumnoController::class, 'destroySeminario'])->name('seminarios.destroy');

// Historial de seminarios por alumno
Route::get   ('/alumnos/{id}/historial-seminarios',   [AlumnoController::class, 'historialSeminarios'])      ->name('alumnos.historial-seminarios');
Route::post  ('/alumnos/{id}/historial-seminarios',   [AlumnoController::class, 'storeHistorialSeminario'])  ->name('alumnos.historial-seminarios.store');
Route::delete('/alumnos/historial-seminarios/{id}',   [AlumnoController::class, 'destroyHistorialSeminario'])->name('alumnos.historial-seminarios.destroy');

    // ── Pagos ─────────────────────────────────────────────────────────────
    // IMPORTANTE: las rutas con segmentos estáticos ('resultado', 'pagar')
    // deben ir ANTES de las rutas con parámetros dinámicos ({id})
    // para que Laravel no interprete 'resultado' como un {id}.

    Route::get('/pagos',                       [PagoController::class, 'index'])->name('pagos.index');
    Route::post('/pagos',                      [PagoController::class, 'store'])->name('pagos.store');

    // Resultado de pago MP (back_url) — estático, va primero
    Route::get('/pagos/resultado',             [PagoController::class, 'resultado'])->name('pagos.resultado');

    // Procesar pago con Payment Brick — estático, va antes de /{id}
    Route::post('/pagos/procesar',               [PagoApiController::class, 'procesar']);

    // Rutas con {id} — van después de las estáticas
    Route::get('/pagos/{id}/pagar',            [PagoController::class, 'pagar'])->name('pagos.pagar');
    Route::get('/pagos/{id}/historial',        [PagoController::class, 'historialAlumno'])->name('pagos.historial');
    Route::get('/pagos/{id}/abonos',           [PagoController::class, 'listarAbonos'])->name('pagos.abonos');

    // Admin/sensei marcan pago como Completado tras verificar
    Route::post('/pagos/{id}/completar',       [PagoController::class, 'completar'])->name('pagos.completar');

    // Registrar abono (efectivo → admin/sensei | en_línea → cualquier rol)
    Route::post('/pagos/{id}/abono',           [PagoController::class, 'abono'])->name('pagos.abono');

    // Admin: eliminar pago permanentemente (hard delete)
    Route::delete('/pagos/{id}',               [PagoController::class, 'destroy'])->name('pagos.destroy');

    // Sensei: suspender pago (soft delete → estado 'Suspendido')
    Route::patch('/pagos/{id}/suspender',      [PagoController::class, 'suspender'])->name('pagos.suspender');

    Route::post('/conceptos-pago',     [PagoController::class, 'storeConcepto'])->name('conceptos.store');
Route::put('/conceptos-pago/{id}', [PagoController::class, 'updateConcepto'])->name('conceptos.update');

    // ── Tutores ───────────────────────────────────────────────────────────
    Route::get('/tutor',                       [TutorController::class, 'index'])->name('tutor.index');
    Route::post('/tutor',                      [TutorController::class, 'store'])->name('tutor.store');
    Route::put('/tutor/{id}',                  [TutorController::class, 'update'])->name('tutor.update');

    // ── Usuarios (CRUD completo) ──────────────────────────────────────────
    Route::get('/usuarios',                          [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('/usuarios',                         [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}/edit',                [UsuarioController::class, 'edit'])->name('editarUsu');
    Route::put('/usuarios/{id}',                     [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}',                  [UsuarioController::class, 'destroy'])->name('usuarios.destroy');
    Route::post('/usuarios/{id}/toggle-active',      [UsuarioController::class, 'toggleActive'])->name('usuarios.toggleActive');

    // ── Eventos multimedia ────────────────────────────────────────────────
    Route::get('/eventos',                     [EventoController::class, 'index'])->name('eventos.index');
    Route::post('/eventos',                    [EventoController::class, 'store'])->name('eventos.store');
    Route::put('/eventos/{id}',                [EventoController::class, 'update'])->name('eventos.update');
    Route::delete('/eventos/{id}',             [EventoController::class, 'destroy'])->name('eventos.destroy');

    // ── Asistencia ────────────────────────────────────────────────────────
    Route::get('/asistencia',                  [AsistenciaController::class, 'index'])->name('asistencia.index');
    Route::get('/asistencia/pdf',              [AsistenciaController::class, 'descargarPdf'])->name('asistencia.pdf');
    Route::get('/asistencia/excel',            [AsistenciaController::class, 'descargarExcel'])->name('asistencia.excel');
});