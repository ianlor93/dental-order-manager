@extends('legal.layout')
@section('title', 'Note Legali')
@section('badge', 'Versione 2026.4')
@section('meta', 'Documento del sito (vetrina) · ' . config('lab.landing_url') . ' · Versione 2026.4')

@section('content')
    <p style="text-align:center"><em>Documento del sito (vetrina) — {{ config('lab.landing_url') }}</em></p>
    <h2>1. Titolare ed editore del sito</h2>
    <p>Il sito <strong>{{ config('lab.landing_url') }}</strong> e l'ecosistema digitale <strong>{{ config('app.name') }}</strong> sono editi da:</p>
    <p><strong>{{ config('lab.manufacturer.name') }}</strong></p>
    <p>{{ config('lab.manufacturer.address') }}, {{ config('lab.manufacturer.zip') }} {{ config('lab.manufacturer.city') }}, Italia</p>
    <p>Partita IVA: <strong>{{ config('lab.manufacturer.vat') }}</strong></p>
    <p>REA: <strong>{{ config('lab.manufacturer.rea') }}</strong></p>
    <p>PEC: <strong><a href="mailto:{{ config('lab.manufacturer.pec') }}" class="legal-link">{{ config('lab.manufacturer.pec') }}</a></strong></p>
    <p>Tel.: {{ config('lab.manufacturer.phone') }} · Email: <strong><a href="mailto:{{ config('lab.manufacturer.email') }}" class="legal-link">{{ config('lab.manufacturer.email') }}</a></strong></p>
    <p>Titolare e legale rappresentante: <strong>{{ config('lab.manufacturer.legal_representative') }}</strong>.</p>
    <h2>2. Natura dell'attività</h2>
    <p>{{ config('lab.manufacturer.name') }} è un <strong>laboratorio odontotecnico</strong> e <strong>fabbricante di dispositivi medici su misura</strong> ai sensi del Regolamento (UE) 2017/745 (MDR).</p>
    <p>Numero di registrazione come fabbricante presso il Ministero della Salute: <strong>{{ config('lab.manufacturer.mdr_registration') }}</strong>.</p>
    <p>I dispositivi sono prodotti esclusivamente su prescrizione di un professionista sanitario abilitato e destinati al singolo paziente; non sono offerti né venduti a consumatori finali.</p>
    <p>{{ config('lab.manufacturer.name') }} fornisce inoltre, a clienti professionali del settore odontoiatrico, <strong>servizi digitali</strong> a supporto della loro attività, disciplinati dalle <strong>Condizioni Generali di Fornitura</strong> e dalla relativa <strong>Informativa Privacy dei Servizi</strong>.</p>
    <h2>3. Protezione dei dati personali</h2>
    <p>Titolare del trattamento: <strong>{{ config('lab.manufacturer.name') }}</strong>.</p>
    <p><strong>Responsabile della Protezione dei Dati (DPO): non nominato</strong> — alla scala attuale dell'attività non ricorrono i presupposti di designazione obbligatoria di cui all'Art. 37(1) GDPR; il Titolare riesamina periodicamente tale valutazione, con cadenza trimestrale, in attuazione del principio di responsabilizzazione (Art. 5(2) GDPR). Contatto per la protezione dei dati: <strong><a href="mailto:{{ config('lab.manufacturer.email') }}" class="legal-link">{{ config('lab.manufacturer.email') }}</a></strong>.</p>
    <p>Per il dettaglio dei trattamenti si rimanda alla <strong>Informativa Privacy del Sito</strong> e alla <strong>Cookie Policy</strong> (in calce).</p>
    <h2>4. Hosting e infrastruttura</h2>
    <p>L'infrastruttura è <strong>self-hosted</strong> ed esposta tramite <strong>Cloudflare, Inc.</strong> (rete, CDN e sicurezza, USA).</p>
    <h2>5. Proprietà intellettuale</h2>
    <p>I contenuti del sito e dell'ecosistema <strong>{{ config('app.name') }}</strong> — testi, marchi, loghi, elementi grafici e <strong>i software sviluppati da {{ config('lab.manufacturer.name') }}</strong> — sono di proprietà riservata di {{ config('lab.manufacturer.name') }}. Ogni riproduzione, anche parziale, non autorizzata è vietata e sarà perseguita a norma di legge.</p>
    <p>L'ecosistema integra inoltre componenti software di <strong>terze parti e open source</strong>, che restano di proprietà dei rispettivi titolari e sono utilizzati nel rispetto delle relative licenze.</p>
    <h2>6. Documenti legali collegati</h2>
    <ul>
        <li><strong>Informativa Privacy del Sito</strong></li>
        <li><strong>Cookie Policy</strong></li>
        <li><em>Per i clienti professionali (area riservata):</em> Condizioni Generali di Fornitura (ToS), Informativa Privacy dei Servizi, Data Processing Agreement (DPA), Clausole ex Art. 1341 c.c.</li>
    </ul>
@endsection
