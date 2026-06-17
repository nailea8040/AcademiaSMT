<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Modelos y sus políticas asociadas.
     * Si no usas Policy classes, deja este array vacío.
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Registra los Gates y Policies de la aplicación.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // ── Acceso Básico ────────────────────────────────────────────
        // Cualquier usuario autenticado con rol válido puede acceder
        // a: Inicio, Pagos, Calendario, Galería, Perfil
        Gate::define('acceso-basico', function ($user) {
            return in_array($user->rol, ['admin', 'sensei', 'alumno', 'tutor']);
        });

        // ── Acceso Gestión ───────────────────────────────────────────
        // Solo admin y sensei pueden gestionar: Usuarios, Tutores, Alumnos
        Gate::define('acceso-gestion', function ($user) {
            return in_array($user->rol, ['admin', 'sensei']);
        });

        // ── Acceso Admin ─────────────────────────────────────────────
        // Solo administradores (por si lo necesitas en el futuro)
        Gate::define('acceso-admin', function ($user) {
            return $user->rol === 'admin';
        });

        // ── Solo Alumno ──────────────────────────────────────────────
        Gate::define('acceso-alumno', function ($user) {
            return $user->rol === 'alumno';
        });

        // ── Solo Tutor ───────────────────────────────────────────────
        Gate::define('acceso-tutor', function ($user) {
            return $user->rol === 'tutor';
        });
    }
}