<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /**
         * 🚀 DISCO UNRAID / NEXTCLOUD (ING_L)
         * Host: /<percorso-host>/ING_L -> Container: /app/storage/unraid_ing_l
         * Utilizzo: Ingresso ordini e asset originali.
         */
        'unraid' => [
            'driver' => 'local',
            'root' => env('UNRAID_STORAGE_PATH', '/app/storage/unraid_ing_l'),
            'visibility' => 'private',
            'throw' => true,
            'report' => true,
        ],

        /**
         * 🛠️ DISCO UNRAID BAC_L (Banco Lavoro)
         * Host: /<percorso-host>/BAC_L -> Container: /app/storage/unraid_bac_l
         */
        'unraid_bac_l' => [
            'driver' => 'local',
            'root' => env('BAC_L_STORAGE_PATH', '/app/storage/unraid_bac_l'),
            'visibility' => 'private',
            'throw' => true, 
            'report' => true,
        ],

        /**
         * 📦 DISCO UNRAID FIN_L (Finitura e Spedizione)
         * Host: /<percorso-host>/FIN_L -> Container: /app/storage/unraid_fin_l
         * MDR Annex XIII: Generazione Dichiarazione di Conformità e Scheda Tecnica.
         * GDPR Art. 13: Generazione Informativa Paziente.
         */
        'unraid_fin_l' => [
            'driver' => 'local',
            'root' => env('FIN_L_STORAGE_PATH', '/app/storage/unraid_fin_l'),
            'visibility' => 'private',
            'throw' => true,
            'report' => true,
        ],

        /**
         * 🏛️ DISCO UNRAID ARCH_L (Archivio Storico MDR)
         * Host: /<percorso-host>/ARCH_L -> Container: /app/storage/unraid_arch_l
         * MDR Art. 10 par. 8: Conservazione obbligatoria 10 anni (15 per impiantabili).
         * GDPR Art. 5: Integrità e Delete Lock (Sola Lettura).
         */
        'unraid_arch_l' => [
            'driver' => 'local',
            'root' => env('ARCH_L_STORAGE_PATH', '/app/storage/unraid_arch_l'),
            'visibility' => 'private',
            'throw' => true,
            'report' => true,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];