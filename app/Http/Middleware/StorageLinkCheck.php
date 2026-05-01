<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * StorageLinkCheck
 *
 * En Railway el filesystem es efímero — el symlink
 * public/storage → storage/app/public se borra en cada deploy.
 * Este middleware lo regenera automáticamente si no existe.
 */
class StorageLinkCheck
{
    public function handle(Request $request, Closure $next)
    {
        $link = public_path('storage');

        // Si el symlink no existe o está roto, regenerarlo
        if (!file_exists($link) || !is_link($link)) {
            try {
                Artisan::call('storage:link');
            } catch (\Exception $e) {
                // Silencioso — no romper la request si falla
            }
        }

        return $next($request);
    }
}