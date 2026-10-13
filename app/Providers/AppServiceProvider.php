<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Order;
use App\Policies\OrderPolicy;

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
        /**
         * 🛡️ FORCE HTTPS
         * Fondamentale per l'Iframe in Nextcloud.
         */
        if (app()->environment('production') || env('FORCE_HTTPS', true)) {
            URL::forceScheme('https');
        }

        // 🛡️ Policy ordini (fix IDOR): proprietà a livello studio + bypass Staff.
        Gate::policy(Order::class, OrderPolicy::class);

        /**
         * ⚖️ MDR ACCESS CONTROL GATE
         * Regola l'accesso alle funzioni di drafting tecnico e produzione.
         * La prescrizione deve essere redatta sulla base di dati tecnici documentati 
         * dal fabbricante (Staff)[cite: 16, 17].
         */
        Gate::define('access-production', function ($user) {
            // Il permesso è concesso solo se l'utente è identificato come STAFF
            return \App\Support\AccessContext::for($user)->isStaff();
        });
    }
}