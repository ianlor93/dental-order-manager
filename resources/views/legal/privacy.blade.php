@extends('legal.layout')
@section('title', 'Informativa Privacy del Sito')
@section('badge', 'Versione 2026.4')
@section('meta', 'Versione 2026.4 · in vigore dal 24 giugno 2026 · Reg. (UE) 2016/679, artt. 13-14')

@section('content')
    <p style="text-align:center"><em>Documento del sito (vetrina) — {{ config('lab.landing_url') }} · Informativa ex Art. 13 GDPR (UE) 2016/679</em></p>
    <p>La presente Informativa descrive il trattamento dei dati personali dei <strong>visitatori</strong> del sito {{ config('lab.landing_url') }} e di chi invia una <strong>richiesta di accesso</strong> alla piattaforma o una <strong>richiesta di contatto</strong> tramite i moduli presenti sul sito.</p>
    <div class="legal-notice"><strong>Ambito.</strong> Questa informativa <strong>non</strong> riguarda i <strong>dati clinici dei pazienti</strong> trattati nell'ambito dei servizi odontotecnici: per quelli valgono la <strong>Informativa Privacy dei Servizi</strong>, il <strong>Data Processing Agreement (DPA)</strong> e l'<strong>Informativa al Paziente</strong>, dove i ruoli sono diversi (il medico/studio è <strong>Titolare</strong>, {{ config('lab.manufacturer.name') }} è <strong>Responsabile</strong> ex Art. 28). Nessun dato clinico viene raccolto attraverso questo sito.</div>
    <h2>1. Titolare del Trattamento</h2>
    <p>Titolare del trattamento è <strong>{{ config('lab.manufacturer.name') }}</strong>, con sede legale in {{ config('lab.manufacturer.address') }}, {{ config('lab.manufacturer.zip') }} {{ config('lab.manufacturer.city') }}, P.IVA {{ config('lab.manufacturer.vat') }}, email <strong><a href="mailto:{{ config('lab.manufacturer.email') }}" class="legal-link">{{ config('lab.manufacturer.email') }}</a></strong>. <span class="cite">[GDPR Art. 4.7]</span></p>
    <p><strong>Responsabile della Protezione dei Dati (DPO): non nominato</strong> — alla scala attuale dell'attività non ricorrono i presupposti di designazione obbligatoria di cui all'Art. 37(1) GDPR; il Titolare riesamina periodicamente tale valutazione, con cadenza trimestrale, in attuazione del principio di responsabilizzazione (Art. 5(2) GDPR). Contatto per la protezione dei dati: <strong><a href="mailto:{{ config('lab.manufacturer.email') }}" class="legal-link">{{ config('lab.manufacturer.email') }}</a></strong>.</p>
    <h2>2. Dati Personali Trattati</h2>
    <h3>2.1 Dati dei moduli del sito</h3>
    <p>Il sito mette a disposizione due moduli; inviandoli, l'utente conferisce i seguenti dati.</p>
    <p><strong>Modulo di richiesta accesso alla piattaforma</strong></p>
    <ul>
        <li><strong>Tipo di attività</strong></li>
        <li><strong>Ragione sociale</strong></li>
        <li><strong>Partita IVA</strong></li>
        <li><strong>Nome e cognome del referente</strong></li>
        <li><strong>Email</strong></li>
        <li><strong>Telefono</strong></li>
    </ul>
    <p>Il modulo di richiesta accesso <strong>non</strong> prevede campi di testo libero, allegati o dati sanitari. Si invita a non inserire categorie particolari di dati (Art. 9 GDPR).</p>
    <p><strong>Modulo di richiesta contatto</strong></p>
    <ul>
        <li><strong>Ragione sociale</strong></li>
        <li><strong>Nome del contatto</strong></li>
        <li><strong>Email</strong></li>
        <li><strong>Telefono</strong> (facoltativo)</li>
        <li><strong>Motivo della richiesta</strong></li>
        <li><strong>Messaggio</strong></li>
    </ul>
    <p>Nel modulo di richiesta contatto l'unico campo a testo libero è «Messaggio». Si invita a non inserirvi categorie particolari di dati, inclusi dati sanitari (Art. 9 GDPR).</p>
    <p>Per le ditte individuali, ragione sociale e P.IVA possono costituire dato personale; il trattamento è comunque coperto dalla presente Informativa.</p>
    <h3>2.2 Dati di navigazione e log tecnici</h3>
    <p>I sistemi raccolgono automaticamente, per il solo funzionamento e la sicurezza del sito, dati quali indirizzo IP, tipo di browser/dispositivo (user-agent) e log di accesso al server. <span class="cite">[GDPR Art. 32]</span></p>
    <h3>2.3 Cookie e tecnologie analoghe</h3>
    <p>Il sito utilizza <strong>esclusivamente cookie tecnici</strong> e la memorizzazione locale (localStorage) per la preferenza espressa nel banner. Il sito <strong>non</strong> utilizza cookie di profilazione, strumenti di analytics o tracciamento. Per il dettaglio si rimanda alla <strong>Cookie Policy</strong>.</p>
    <h2>3. Finalità del Trattamento e Basi Giuridiche</h2>
<table>
<thead><tr><th>Finalità</th><th>Base giuridica</th><th>Conferimento</th></tr></thead>
<tbody>
<tr><td>Dare riscontro alle richieste di accesso e di contatto e valutare l'eventuale avvio di un rapporto professionale</td><td>Esecuzione di misure precontrattuali adottate su richiesta dell'interessato. <span class="cite">[GDPR Art. 6.1.b]</span></td><td>Necessario per dar seguito alla richiesta</td></tr>
<tr><td>Garantire la sicurezza del sito e prevenire abusi e incidenti</td><td>Legittimo interesse del Titolare. <span class="cite">[GDPR Art. 6.1.f]</span></td><td>Necessario per la sicurezza</td></tr>
</tbody></table>
    <p>Il sito <strong>non</strong> effettua marketing, profilazione né processi decisionali automatizzati (Art. 22 GDPR). Il mancato conferimento dei dati dei moduli impedisce di evadere la richiesta.</p>
    <h2>4. Modalità del Trattamento e Sicurezza</h2>
    <p>I dati sono trattati con strumenti elettronici, adottando misure adeguate ai sensi dell'Art. 32 GDPR: connessioni cifrate (TLS 1.3), controllo degli accessi secondo il principio del minimo privilegio e tracciamento delle operazioni. I dati dei moduli sono trattati su infrastruttura propria situata in Italia. <span class="cite">[GDPR Art. 32]</span></p>
    <h2>5. Destinatari dei Dati</h2>
    <p>I dati non sono diffusi né ceduti a terzi per finalità commerciali. Possono essere trattati, in qualità di responsabili o per obbligo di legge, da:</p>
    <ul>
        <li><strong>Cloudflare, Inc.</strong> (rete, CDN e sicurezza del sito, USA);</li>
        <li><strong>Aruba S.p.A.</strong> (invio delle email transazionali di conferma della richiesta, Italia);</li>
        <li>consulenti e professionisti incaricati, ove necessario.</li>
    </ul>
    <h2>6. Trasferimenti verso Paesi Terzi</h2>
    <p>I dati dei moduli sono conservati in Italia. Per i soli dati tecnici di navigazione, il fornitore statunitense <strong>Cloudflare, Inc.</strong> (rete/CDN/sicurezza) opera in forza dell'<strong>EU-U.S. Data Privacy Framework</strong> (decisione di adeguatezza UE 2023/1795) e di <strong>Clausole Contrattuali Standard</strong> (decisione UE 2021/914). Copia disponibile su richiesta.</p>
    <h2>7. Conservazione dei Dati</h2>
    <p>I dati dei moduli di richiesta accesso e di richiesta contatto sono conservati per il tempo necessario a gestire la richiesta. In caso di mancato avvio del rapporto, sono cancellati entro <strong>24 mesi</strong>, salvo obblighi di legge. In caso di avvio del rapporto, confluiscono nel trattamento disciplinato dalla <strong>Privacy dei Servizi</strong>. I log tecnici sono conservati per <strong>6 mesi</strong>.</p>
    <h2>8. Diritti dell'Interessato</h2>
    <p>L'utente può esercitare in ogni momento i diritti previsti dal Capo III del GDPR (Artt. 15–22): accesso, rettifica, cancellazione, limitazione, opposizione e portabilità. Le richieste vanno inoltrate a <strong><a href="mailto:{{ config('lab.manufacturer.email') }}" class="legal-link">{{ config('lab.manufacturer.email') }}</a></strong>.</p>
    <p>L'interessato ha inoltre diritto di proporre reclamo al <strong>Garante per la Protezione dei Dati Personali</strong>, Piazza Venezia 11, 00187 Roma — <a href="mailto:protocollo@gpdp.it" class="legal-link">protocollo@gpdp.it</a> · <a href="mailto:protocollo@pec.gpdp.it" class="legal-link">protocollo@pec.gpdp.it</a> · www.gpdp.it.</p>
    <h2>9. Modifiche</h2>
    <p>La presente Informativa può essere aggiornata; la versione in vigore è quella pubblicata sul sito con la relativa data.</p>
@endsection
