<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── 1. Longitud por defecto de strings en MySQL ───────────────────
        // Necesario para MySQL < 5.7.7 o MariaDB < 10.2.2
        // Evita el error "Specified key was too long" al correr migraciones
        Schema::defaultStringLength(191);

        // ── 2. Forzar HTTPS en producción ────────────────────────────────
        // Cuando está detrás de un proxy (Railway, Heroku, Nginx)
        // Laravel no detecta HTTPS automáticamente sin esto.
        // Genera URLs correctas en asset(), route(), url(), etc.
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // ── 3. Guard de autenticación ─────────────────────────────────────
        // El modelo Usuario usa 'id_usuario' como PK y 'correo' en vez de 'email'.
        // Laravel Auth ya está configurado en config/auth.php para usar el
        // provider 'usuarios' que apunta a App\Models\Usuario.
        // No se necesita configuración adicional aquí.

        // ── 4. Paginación con Bootstrap (si usas paginate() en controllers) ─
        // Descomenta si usas $query->paginate() en algún controller web:
        // \Illuminate\Pagination\Paginator::useBootstrapFive();
    }
}