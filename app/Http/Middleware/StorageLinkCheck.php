<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * StorageLinkCheck
 *
 * En Railway el filesystem es efímero — el symlink
 * public/storage → storage/app/public se borra en cada deploy.
 *
 * CORRECCIONES aplicadas:
 *  1. Se usa caché (60 min) para evitar llamar file_exists() en cada request.
 *  2. Solo regenera el symlink si realmente no existe o está roto.
 *  3. Si la app usa Supabase Storage de forma exclusiva para la galería,
 *     este middleware puede desactivarse poniendo STORAGE_LINK_CHECK=false
 *     en el .env, evitando cualquier overhead innecesario.
 */
class StorageLinkCheck
{
    public function handle(Request $request, Closure $next)
    {
        // Permitir deshabilitar desde .env si el proyecto usa Supabase exclusivamente
        if (!config('app.storage_link_check', true)) {
            return $next($request);
        }

        // Usar caché para no llamar file_exists() en cada request.
        // Si el symlink ya fue verificado en los últimos 60 minutos, se omite.
        $cacheKey = 'storage_link_ok';

        if (!Cache::get($cacheKey)) {
            $link = public_path('storage');

            if (!file_exists($link) || !is_link($link)) {
                try {
                    Artisan::call('storage:link');
                } catch (\Exception $e) {
                    // Silencioso — no romper la request si falla
                }
            }

            // Marcar como verificado por 60 minutos
            Cache::put($cacheKey, true, now()->addMinutes(60));
        }

        return $next($request);
    }
}