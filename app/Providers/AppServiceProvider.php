<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Mail\MailManager;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ── 1. Longitud por defecto de strings en MySQL ───────────────────
        Schema::defaultStringLength(191);

        // ── 2. Forzar HTTPS en producción ────────────────────────────────
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // ── 3. Registrar transport Brevo manualmente ──────────────────────
        $this->app->resolving(MailManager::class, function (MailManager $manager) {
            $manager->extend('brevo', function (array $config) {
                $factory = new BrevoTransportFactory();
                $dsn = Dsn::fromString('brevo+api://' . $config['key'] . '@default');
                return $factory->create($dsn);
            });
        });
    }
}