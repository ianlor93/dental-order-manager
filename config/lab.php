<?php

/**
 * Identità del Laboratorio — Order Manager
 *
 * Questi valori vengono usati nei PDF MDR (Dichiarazione di Conformità,
 * Scheda Tecnica, Informativa Paziente) e nei documenti di compliance.
 *
 * Per aggiornare: modifica le variabili corrispondenti in .env
 * poi esegui: php artisan config:cache
 *
 * PRRC (Persona Responsabile Conformità Regolamentare):
 *   Figura obbligatoria ai sensi dell'Art. 15 MDR 2017/745.
 *   Responsabile del rispetto degli obblighi del Reg. (UE) 2017/745.
 *
 * DPO (Data Protection Officer):
 *   Valutare obbligo nomina ex Art. 37 GDPR 2016/679.
 *   Contatto da rendere disponibile agli interessati (Art. 13 GDPR).
 *   Se non ancora nominato, lasciare vuoto — i PDF useranno il fallback.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Identità del Fabbricante
    |--------------------------------------------------------------------------
    |
    | Dati del laboratorio odontotecnico in qualità di fabbricante
    | di dispositivi medici su misura (MDR 2017/745 Art. 2 par. 30).
    |
    */

    'manufacturer' => [
        'name'    => env('LAB_MANUFACTURER_NAME', ''),
        'address' => env('LAB_ADDRESS', ''),
        'city'    => env('LAB_CITY', ''),
        'zip'     => env('LAB_ZIP', ''),
        'country' => env('LAB_COUNTRY', 'IT'),
        'vat'     => env('LAB_VAT', ''),
        'phone'   => env('LAB_PHONE', ''),
        'email'   => env('LAB_EMAIL', ''),
        'mdr_registration'     => env('LAB_MDR_REGISTRATION', ''),
        'rea'                  => env('LAB_REA', ''),
        'court'                => env('LAB_COURT', ''),
        'pec'                  => env('LAB_PEC', ''),
        'legal_representative' => env('LAB_LEGAL_REP', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | PRRC — Persona Responsabile Conformità Regolamentare
    |--------------------------------------------------------------------------
    |
    | Obbligatoria ex Art. 15 MDR 2017/745.
    | Deve possedere competenze in dispositivi medici e
    | conoscenza dei requisiti normativi vigenti.
    |
    */

    'prrc' => [
        'name'  => env('LAB_PRRC_NAME', ''),
        'role'  => env('LAB_PRRC_ROLE', 'Responsabile Conformità Regolamentare'),
        'email' => env('LAB_PRRC_EMAIL', ''),
        'phone' => env('LAB_PRRC_PHONE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | DPO — Data Protection Officer
    |--------------------------------------------------------------------------
    |
    | Valutare obbligo nomina ex Art. 37 GDPR 2016/679.
    |
    | Se non ancora nominato, lasciare LAB_DPO_NAME vuoto.
    | I PDF useranno il fallback: email del laboratorio.
    |
    */

    'dpo' => [
        'name'  => env('LAB_DPO_NAME', ''),
        'email' => env('LAB_DPO_EMAIL', ''),
        'phone' => env('LAB_DPO_PHONE', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sito vetrina
    |--------------------------------------------------------------------------
    |
    | LANDING_ORIGINS: elenco separato da virgole delle origini accettate dalle
    | whitelist CORS (includere tutte le varianti, con e senza `www.`).
    |
    */

    'landing_url'     => env('LANDING_URL', ''),
    'landing_origins' => array_values(array_filter(array_map('trim',
                            explode(',', (string) env('LANDING_ORIGINS', ''))))),

];
