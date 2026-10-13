<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versione Corrente dei Protocolli Legali
    |--------------------------------------------------------------------------
    |
    | Questo valore viene confrontato con quello salvato nel database per ogni
    | medico prescrittore. Se i valori non corrispondono, il "Legal Gate" 
    | blocca l'accesso e richiede una nuova sottoscrizione.
    |
    */

    'version' => env('LEGAL_VERSION', '2026.4'),

    /*
    |--------------------------------------------------------------------------
    | Obblighi di Conservazione MDR 2017/745
    |--------------------------------------------------------------------------
    |
    | Definisce i termini di legge per la conservazione della documentazione
    | tecnica e delle firme digitali dei dispositivi su misura.
    |
    */

    'retention' => [
        'standard' => 10, // anni per dispositivi su misura standard
        'implant'  => 15, // anni per dispositivi impiantabili
    ],

];