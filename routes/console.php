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