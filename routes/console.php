<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\ResetPasswordController;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Limpieza automática de tokens de recuperación vencidos
|--------------------------------------------------------------------------
|
| Elimina los tokens de recuperación de contraseña que han expirado
| directamente de la tabla 'usuario' (columnas token_recuperacion,
| token_expiracion). Se ejecuta una vez al día.
|
| En Railway: asegúrate de que el scheduler esté activo.
| En el Procfile o railway.toml agrega:
|   worker: php artisan schedule:work
|
*/
Schedule::call(function () {
    ResetPasswordController::purgarTokensVencidos();
})->daily()
  ->name('purgar-tokens-vencidos')
  ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Limpieza de tokens Sanctum expirados
|--------------------------------------------------------------------------
|
| Elimina de la tabla personal_access_tokens los tokens que han superado
| el tiempo de expiración configurado en sanctum.php ('expiration').
| --hours=720 = 30 días, igual que SANCTUM_TOKEN_EXPIRATION.
|
| Esto evita que la tabla crezca indefinidamente con tokens de usuarios
| que reinstalaron la app o cambiaron de dispositivo.
|
*/
Schedule::command('sanctum:prune-expired --hours=48')
    ->daily()
    ->name('sanctum-prune-expired')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Limpieza automática de sesiones expiradas
|--------------------------------------------------------------------------
|
| Elimina de la tabla 'sessions' las sesiones cuyo last_activity
| supera las 2 horas (SESSION_LIFETIME=120 minutos).
|
| Evita fugas de memoria/disco en producción por sesiones huérfanas.
| Se ejecuta cada 15 minutos para mantener la tabla limpia.
|
*/
Schedule::command('session:prune --hours=2')
    ->everyFifteenMinutes()
    ->name('session-prune-expired')
    ->withoutOverlapping();