<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Funnel di registrazione — gruppi assegnabili al vaglio
    |--------------------------------------------------------------------------
    |
    | Mappa: gid Nextcloud  =>  etichetta mostrata nel selettore "Approva" in admin.
    |
    | La scelta del gruppo al vaglio è MANUALE e autoritativa — mai derivata dal
    | `tipo_attivita` autodichiarato dal lead. Includere SOLO gruppi assegnabili che
    | ESISTONO davvero in Nextcloud: T4b farà `occ group:adduser` su questi gid,
    | e un gid inesistente farebbe fallire l'abilitazione.
    |
    | Gruppi assegnabili attuali: `Clienti`, `Clienti-Laboratorio`, `Agenti`.
    | Aggiungere qui eventuali altri gruppi assegnabili (es. altri ruoli) una volta creati
    | su NC. Dopo ogni modifica: `php artisan config:cache`.
    |
    */
    'assignable_groups' => [
        'Clienti'             => 'Cliente — Studio',
        'Clienti-Laboratorio' => 'Cliente — Laboratorio',
        'Agenti'              => 'Agente / Rappresentante',
    ],

];
