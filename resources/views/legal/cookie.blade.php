@extends('legal.layout')
@section('title', 'Informativa Estesa sui Cookie')
@section('badge', 'Versione 2026.4')
@section('meta', 'Versione 2026.4 · in vigore dal 24 giugno 2026 · Direttive Garante + Reg. (UE) 2016/679')

@section('content')
    <p style="text-align:center"><em>Conformità alle Direttive del Garante Privacy e al Regolamento (UE) 2016/679</em></p>
    <p style="text-align:center"><em>Versione: 2026.4 | Ultimo aggiornamento: 22 giugno 2026</em></p>
    <p>La presente Informativa sui Cookie spiega in modo trasparente e dettagliato come {{ config('lab.manufacturer.name') }} utilizza i cookie e tecnologie simili per garantire la sicurezza, la stabilità e la corretta funzionalità dei propri servizi digitali, con particolare riferimento all'ecosistema di gestione ordini e alla piattaforma cloud integrata.</p>
    <h2>1. Definizioni e Funzionamento dei Cookie</h2>
    <p>1.1 I cookie sono stringhe di testo di piccole dimensioni che i siti visitati dall'utente inviano al suo terminale (solitamente al browser), dove vengono memorizzati per essere poi ritrasmessi agli stessi siti alla successiva visita del medesimo utente.</p>
    <p>1.2 Nel corso della navigazione su questo portale, l'utente può ricevere sia cookie inviati direttamente da {{ config('lab.manufacturer.name') }} (cookie di prima parte), sia cookie inviati da siti o da web server diversi (cookie di terze parti), sui quali possono risiedere alcuni elementi (quali, ad esempio, immagini, mappe, suoni, specifici link a pagine di altri domini) presenti sul sito che lo stesso sta visitando.</p>
    <p>1.3 La presente Informativa è <strong>unica per l'intero dominio {{ config('session.domain') }}</strong>: si applica a <strong>{{ config('lab.landing_url') }}</strong> e a ogni servizio, sottodominio o indirizzo raggiungibile sotto lo stesso dominio. I cookie tecnici sono impostati a livello di dominio ({{ config('session.domain') }}) e valgono quindi per l'intero dominio e per tutti i suoi sottodomini; la presente Informativa li disciplina in modo unitario, senza necessità di informative separate per i singoli servizi.</p>
    <h2>2. Tipologie di Cookie Utilizzati</h2>
    <p>In linea con i principi di minimizzazione dei dati e protezione della privacy fin dalla progettazione, il portale adotta esclusivamente le seguenti categorie di strumenti:</p>
    <h3>2.1 Cookie Tecnici e di Sessione</h3>
    <p>Questi strumenti sono essenziali per il corretto funzionamento dell'architettura {{ config('app.name') }}. Senza l'utilizzo di tali cookie, alcune operazioni non potrebbero essere compiute o sarebbero più complesse e meno sicure.</p>
    <ul>
        <li><strong>Autenticazione:</strong> Gestione della sessione di accesso protetta per medici e laboratori.</li>
        <li><strong>Sicurezza:</strong> Prevenzione di attacchi di tipo Cross-Site Request Forgery (CSRF).</li>
        <li><strong>Funzionalità:</strong> Memorizzazione delle preferenze dell'interfaccia durante la sessione di lavoro.</li>
    </ul>
    <h3>2.2 Memorizzazione Locale (localStorage)</h3>
    <p>Il portale e le sue pagine collegate (inclusa la pagina informativa su {{ config('lab.landing_url') }}) utilizzano localStorage del browser, una tecnologia analoga ai cookie, per memorizzare esclusivamente le preferenze espresse nel banner informativo. Nessun dato viene inviato a server terzi tramite localStorage.</p>
    <h2>3. Tabella Dettagliata dei Cookie</h2>
    <p>I cookie utilizzati sono esclusivamente <strong>tecnici</strong>. Di seguito il dettaglio.</p>
<table>
<thead><tr><th>Nome cookie</th><th>Fornitore</th><th>Finalità</th><th>Durata</th></tr></thead>
<tbody>
<tr><td>enamel_order_manager_session</td><td>{{ config('lab.manufacturer.name') }}</td><td>Cookie di sessione del portale Order Manager (solo route web).</td><td>2 ore (inattività)</td></tr>
<tr><td>XSRF-TOKEN</td><td>{{ config('lab.manufacturer.name') }}</td><td>Token anti-CSRF del portale Order Manager (Laravel).</td><td>Sessione</td></tr>
<tr><td>nc_session_id</td><td>{{ config('lab.manufacturer.name') }}</td><td>Identificazione della sessione autenticata su Nextcloud.</td><td>Fine sessione</td></tr>
<tr><td>nc_token</td><td>{{ config('lab.manufacturer.name') }}</td><td>Token «ricordami» (remember-me) di Nextcloud.</td><td>~2 giorni</td></tr>
<tr><td>Altri cookie tecnici Nextcloud (nc_username, oc_sessionPassphrase, oc&lt;instanceid&gt;, __Host-nc_sameSiteCookie*)</td><td>{{ config('lab.manufacturer.name') }}</td><td>Gestione della sessione, autenticazione e protezione SameSite di Nextcloud.</td><td>Sessione</td></tr>
<tr><td>__cf_bm, cf_clearance, _cfuvid</td><td>Cloudflare, Inc.</td><td>Cookie tecnici di sicurezza e gestione del traffico (bot management), presenti su tutti gli host dell'ecosistema.</td><td>Variabile (da minuti a mesi)</td></tr>
</tbody></table>
    <p>I cookie tecnici di prima parte sono impostati con domain={{ config('session.domain') }}: sono quindi validi per l'intero dominio e condivisi tra i suoi sottodomini (fanno eccezione i cookie host-only __Host-nc_*, privi di attributo Domain). I cookie di Cloudflare sono impostati per singolo host.</p>
    <p>Le preferenze espresse nel banner <strong>non sono cookie</strong>: sono salvate nella memoria locale del browser (localStorage), come indicato al § 2.2.</p>
    <h2>4. Gestione del Consenso e Opzioni del Browser</h2>
    <p>4.1 Ai sensi del GDPR, l'installazione dei cookie tecnici non richiede il consenso preventivo dell'utente, in quanto necessari all'erogazione del servizio richiesto. <span class="cite">[GDPR Art. 6.1.b]</span></p>
    <p>4.2 È comunque possibile configurare il proprio browser per bloccare o eliminare i cookie. Di seguito i link alle istruzioni dei principali software di navigazione:</p>
    <ul>
        <li><strong>Google Chrome:</strong> Gestione cookie e dati dei siti.</li>
        <li><strong>Mozilla Firefox:</strong> Protezione antitracciamento avanzata.</li>
        <li><strong>Apple Safari:</strong> Gestione di cookie e dati dei siti web su Mac.</li>
        <li><strong>Microsoft Edge:</strong> Eliminare e gestire i cookie.</li>
    </ul>
    <h2>5. Privacy by Design</h2>
    <p>Applichiamo il principio di protezione dei dati fin dalla progettazione. Il nostro sistema non utilizza cookie di profilazione, non effettua tracciamento cross-site e non cede dati di navigazione a terze parti per finalità commerciali o di marketing. Ogni strumento tecnico è finalizzato esclusivamente alla resilienza e alla sicurezza del trattamento dei dati clinici. <span class="cite">[GDPR Art. 25]</span></p>
@endsection
