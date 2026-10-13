<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowNextcloudFrame
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. RIMUOVIAMO X-Frame-Options
        // I browser moderni usano la CSP. X-Frame-Options può causare conflitti se impostato su ALLOW-FROM.
        $response->headers->remove('X-Frame-Options');

        // 2. IMPOSTIAMO LA CSP MODERNA
        // frame-ancestors è il comando standard che dice al browser: 
        // "Puoi caricarmi solo se sei dentro l'origine di Nextcloud o me stesso".
        $ncUrl = (string) config('services.nextcloud.url');
        $response->headers->set(
            'Content-Security-Policy',
            "frame-ancestors 'self'" . ($ncUrl !== '' ? ' ' . $ncUrl : '')
        );

        return $response;
    }
}