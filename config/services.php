<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Questo file è destinato alla memorizzazione delle credenziali per i 
    | servizi di terze parti. Usiamo config() invece di env() nei bridge 
    | per garantire che l'autenticazione funzioni anche con la cache attiva.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // --- Integrazione Nextcloud Bridge ---
    // Integrazione Nextcloud
    'nextcloud' => [
        'url' => env('NEXTCLOUD_URL', ''),
        'sso_pubkey_b64' => env('NEXTCLOUD_SSO_PUBKEY_B64', ''),
    ],

    // --- Provisioning utenti NC (funnel T3) — canale firmato NC→OM (pull) ---
    // OM VERIFICA soltanto. `jwt_pubkey` = chiave PUBBLICA ES256 della coppia
    // DEDICATA dell'app NC `enamelprovisioning`. È PUBBLICA, NON un segreto.
    // Vuota ⇒ gli endpoint /api/provisioning/* rifiutano tutto (verify fallisce).
    // Da incollare qui (PEM) al deploy, dopo averla generata sul lato NC.
    'nextcloud_provisioning' => [
        // base64 (UNA riga) della PEM pubblica ES256 dedicata. PUBBLICA, non un segreto.
        // Decodificata a runtime. Vuota ⇒ endpoint /api/provisioning/* rifiutano tutto.
        // Single-line per non far crashare il terminale Unraid sui PEM multilinea.
        'jwt_pubkey_b64' => env('NC_PROVISIONING_PUBKEY_B64', ''),
        'max_ttl'        => 120, // finestra massima ammessa exp-iat dei token (secondi)
    ],

    // --- Gate NC (funnel) — stato onboarding per l'app Nextcloud `enamel_gate` ---
    // Segreto CONDIVISO (simmetrico) con cui l'app NC si autentica verso
    // /api/gate/onboarding-status (header Authorization: Bearer <secret>).
    // Server-side su ENTRAMBI i lati, MAI nel browser. VUOTO ⇒ endpoint disabilitato
    // (rifiuta tutto) ⇒ il gate NC va in FAIL-OPEN (non blocca). Lo imposta Lorenzo in
    // .env (GATE_SHARED_SECRET=...) + `php artisan config:cache` nel container, al momento
    // dell'abilitazione del gate. Finché è vuoto, l'endpoint resta inerte (sicuro).
    'nextcloud_gate' => [
        'shared_secret' => env('GATE_SHARED_SECRET'),
    ],

];