<?php

/**
 * Allegato A DPA — Sub-responsabili del trattamento (GDPR Art. 28.4).
 *
 * Elenco autorizzato dai clienti all'atto della firma del DPA (onboarding step 4).
 *
 * Modifiche a questo file richiedono:
 *   1. Preavviso 30 gg via email a tutti i clienti attivi
 *   2. Incremento LEGAL_VERSION (es. 2026.2 → 2026.3)
 *   3. Ri-sottoscrizione DPA da parte dei clienti (onboarding step 4)
 *
 * Le modifiche ai sub-responsabili vanno tracciate nel controllo di versione.
 *
 * Campo 'legal_basis_transfer' (+ opzionale 'legal_basis_transfer_fallback'):
 *   - 'intra_ue': fornitore nell'UE/SEE — nessun trasferimento verso Paesi terzi
 *   - 'art45_adequacy': SOLO Paesi terzi con decisione di adeguatezza ex Art. 45 (≠ fornitore UE)
 *   - 'art45_dpf': EU-US Data Privacy Framework — adeguatezza ex Art. 45 (dec. UE 2023/1795)
 *   - 'art46_sccs': Clausole Contrattuali Standard ex Art. 46(2)(c) (2021/914) — fallback
 */
return [

    'allegato_a_version'    => '2026.3',
    'allegato_a_updated_at' => '2026-04-17',

    'subprocessors' => [
        [
            'name'                  => 'Aruba S.p.A.',
            'role'                  => 'Email SMTP transazionale',
            'country'               => 'IT',
            'legal_basis_transfer'  => 'intra_ue',
            'dpa_url'               => 'https://www.aruba.it/gdpr/dpa.pdf',
            'dpa_version'           => '2024.1',
            'categories'            => ['email'],
            'active_since'          => '2026-01-23',
            'notes'                 => null,
        ],
        [
            'name'                  => 'Cloudflare Inc.',
            'role'                  => 'CDN + protezione DDoS + WAF',
            'country'               => 'US',
            'legal_basis_transfer'  => 'art45_dpf',
            'legal_basis_transfer_fallback' => 'art46_sccs',
            'dpa_url'               => 'https://www.cloudflare.com/cloudflare-customer-dpa/',
            'dpa_version'           => '2024.2',
            'categories'            => ['network_transit'],
            'active_since'          => '2026-01-23',
            'notes'                 => 'Solo metadata HTTP in transito (IP, header). Il corpo delle richieste (file clinici) non è mai esposto — cifrato TLS end-to-end. TIA disponibile su richiesta.',
        ],
    ],

    /**
     * Soglia preavviso cambio sub-responsabili (Art. 28.2 GDPR + ToS art. 1-bis).
     * Il cliente può opporsi entro questo termine prima che il nuovo sub-responsabile
     * tratti i suoi dati.
     */
    'change_notice_days' => 30,

];
