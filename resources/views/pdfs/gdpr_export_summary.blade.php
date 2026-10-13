<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #333; line-height: 1.5; }
        .title { font-size: 16pt; font-weight: bold; color: #ff6f3f; text-transform: uppercase; margin-bottom: 5px; }
        .subtitle { font-size: 9pt; color: #6b7280; margin-bottom: 25px; }
        .section-title { background: #f3f4f6; padding: 6px 10px; font-size: 9pt; font-weight: bold;
                         text-transform: uppercase; color: #4b5563; margin-top: 20px;
                         border-left: 4px solid #ff6f3f; }
        .data-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .data-table td { padding: 7px 10px; border-bottom: 1px solid #f0f0f0; font-size: 9.5pt; }
        .label { font-weight: bold; width: 35%; color: #555; font-size: 8.5pt; text-transform: uppercase; }
        .orders-table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 8.5pt; }
        .orders-table th { background: #ff6f3f; color: white; padding: 6px 8px; text-align: left; }
        .orders-table td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        .orders-table tr:nth-child(even) td { background: #fafafa; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 7.5pt;
                 font-weight: bold; text-transform: uppercase; }
        .badge-archived { background: #d1fae5; color: #065f46; }
        .badge-locked { background: #fee2e2; color: #991b1b; }
        .badge-other { background: #e0e7ff; color: #3730a3; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 7.5pt;
                  color: #9ca3af; border-top: 1px solid #eee; padding-top: 8px; }
        .notice-box { border: 1px solid #fcd34d; background: #fffbeb; padding: 12px 15px;
                      font-size: 9pt; margin: 15px 0; }
    </style>
</head>
<body>
    @include('pdfs.partials.branding_header')

    <div class="title">Export Dati Personali</div>
    <div class="subtitle">
        Portabilità dei dati ai sensi dell'Art. 20 GDPR 2016/679 —
        Generato il {{ $generato_il->format('d/m/Y \a\l\l\e H:i:s') }}
    </div>

    <div class="notice-box">
        <strong>Nota:</strong> Questo documento è un riepilogo leggibile dell'export.
        I dati completi in formato strutturato (JSON) sono inclusi negli altri file del pacchetto ZIP.
        I file CAD/CAM originali non sono inclusi nell'export (dati tecnici del laboratorio).
    </div>

    <div class="section-title">Dati Utente</div>
    <table class="data-table">
        <tr><td class="label">Identificativo</td><td>{{ $user->uid }}</td></tr>
        <tr><td class="label">Nome</td><td>{{ $user->displayname }}</td></tr>
        <tr><td class="label">Email</td><td>{{ $user->email ?? '—' }}</td></tr>
    </table>

    @if($studio)
    <div class="section-title">Studio / Laboratorio</div>
    <table class="data-table">
        <tr><td class="label">Ragione Sociale</td><td>{{ $studio->name }}</td></tr>
        <tr><td class="label">Partita IVA</td><td>{{ $studio->vat_number }}</td></tr>
        <tr><td class="label">PEC</td><td>{{ $studio->pec_email }}</td></tr>
        <tr><td class="label">Dentista (Master)</td><td>{{ $studio->dentist_name }} — Albo {{ $studio->dentist_license }} ({{ $studio->albo_provincia }})</td></tr>
        <tr><td class="label">Livello</td><td>{{ $studio->tier_level }}</td></tr>
    </table>

    <div class="section-title">Consensi e Date di Firma</div>
    <table class="data-table">
        <tr><td class="label">Privacy</td><td>{{ $studio->privacy_accepted_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="label">Termini di Servizio</td><td>{{ $studio->tos_accepted_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="label">Protocollo MDR</td><td>{{ $studio->mdr_accepted_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="label">DPA</td><td>{{ $studio->dpa_accepted_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="label">Versione documenti</td><td>{{ $studio->legal_version }}</td></tr>
    </table>
    @endif

    <div class="section-title">Ordini ({{ $ordini->count() }} totali)</div>

    @if($ordini->isEmpty())
        <p style="color:#6b7280; font-size:9pt;">Nessun ordine presente.</p>
    @else
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Codice</th>
                    <th>Paziente</th>
                    <th>Dispositivo</th>
                    <th>Stato</th>
                    <th>Data</th>
                    <th>Archiviare fino al</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ordini as $order)
                <tr>
                    <td><strong>{{ $order->code }}</strong></td>
                    <td>{{ $order->patient_ref }}</td>
                    <td style="font-size:8pt;">{{ strtoupper(str_replace('_', ' ', $order->final_device_name ?? $order->device_type)) }}</td>
                    <td>
                        @if($order->status === 'archived')
                            <span class="badge badge-archived">Archiviato</span>
                        @elseif($order->is_locked)
                            <span class="badge badge-locked">Protetto MDR</span>
                        @else
                            <span class="badge badge-other">{{ $order->status }}</span>
                        @endif
                    </td>
                    <td>{{ $order->created_at?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $order->legal_retention_until?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p style="font-size:8.5pt; color:#6b7280; margin-top:8px;">
            Nota: gli ordini con stato "Protetto MDR" non possono essere cancellati
            per obbligo di conservazione ai sensi dell'Art. 10 par. 8 MDR 2017/745.
            Il diritto alla cancellazione (Art. 17 GDPR) è limitato da questo obbligo.
        </p>
    @endif

    {{-- ── DESTINATARI E SUB-RESPONSABILI (Art. 15(1)(c)) ── --}}
    <div class="section-title">Destinatari dei Dati (Art. 15(1)(c) GDPR)</div>
    <p style="font-size:8.5pt; color:#6b7280; margin: 6px 0 8px;">
        I tuoi dati possono essere comunicati ai seguenti soggetti in qualità di sub-responsabili
        del trattamento ex Art. 28 GDPR (Allegato A DPA v{{ config('compliance.allegato_a_version') }},
        aggiornato il {{ config('compliance.allegato_a_updated_at') }}):
    </p>
    <table class="orders-table">
        <thead>
            <tr>
                <th>Sub-responsabile</th>
                <th>Ruolo</th>
                <th>Paese</th>
                <th>Dati trattati</th>
            </tr>
        </thead>
        <tbody>
            @foreach(config('compliance.subprocessors', []) as $sp)
            <tr>
                <td><strong>{{ $sp['name'] }}</strong></td>
                <td style="font-size:8pt;">{{ $sp['role'] }}</td>
                <td>{{ $sp['country'] }}</td>
                <td style="font-size:8pt;">{{ implode(', ', $sp['categories']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── TRASFERIMENTI EXTRA-UE (Art. 15(1)(c) + Art. 13(1)(f)) ── --}}
    <div class="section-title">Trasferimenti Extra-UE (Artt. 44-49 GDPR)</div>
    <table class="data-table">
        @foreach(config('compliance.subprocessors', []) as $sp)
            @if(!str_starts_with($sp['country'], 'IT'))
            <tr>
                <td class="label">{{ $sp['name'] }}</td>
                <td style="font-size:8.5pt;">
                    {{ $sp['country'] }} —
                    @if($sp['legal_basis_transfer'] === 'art45_dpf') Base: EU-US Data Privacy Framework — adeguatezza ex Art. 45 (dec. UE 2023/1795); in subordine SCC ex Art. 46(2)(c) (2021/914)
                    @elseif($sp['legal_basis_transfer'] === 'art46_sccs') Base: Clausole Contrattuali Standard (Art. 46(2)(c))
                    @else Base: adeguatezza (Art. 45) @endif
                    @if($sp['notes'])<br><span style="color:#6b7280; font-size:8pt;">{{ $sp['notes'] }}</span>@endif
                </td>
            </tr>
            @endif
        @endforeach
    </table>

    {{-- ── PERIODO DI CONSERVAZIONE (Art. 15(1)(d)) ── --}}
    <div class="section-title">Periodo di Conservazione per Categoria (Art. 15(1)(d) GDPR)</div>
    <table class="orders-table">
        <thead>
            <tr><th>Categoria di dati</th><th>Durata conservazione</th><th>Base</th></tr>
        </thead>
        <tbody>
            <tr><td>Dati account / anagrafica studio</td><td>Durata del contratto + 10 anni</td><td>Art. 6(1)(b)+(c) GDPR — obblighi contabili</td></tr>
            <tr><td>Fatture e dati di pagamento</td><td>10 anni dalla data documento</td><td>Art. 2220 c.c. — obblighi fiscali</td></tr>
            <tr><td>Dati ordini — dispositivi medici standard</td><td>{{ config('legal.retention.standard', 10) }} anni dall'archiviazione</td><td>MDR 2017/745 Art. 10(8)</td></tr>
            <tr><td>Dati ordini — dispositivi impiantabili</td><td>{{ config('legal.retention.implant', 15) }} anni dall'archiviazione</td><td>MDR 2017/745 Art. 10(8)</td></tr>
            <tr><td>Accettazioni documenti legali</td><td>10 anni dalla firma</td><td>Art. 6(1)(c) — prova adempimento GDPR</td></tr>
            <tr><td>Log applicativi (sanitizzati)</td><td>30 giorni</td><td>Art. 6(1)(f) — sicurezza sistema + obbligo notifica breach 72h</td></tr>
            <tr><td>Sessioni autenticate</td><td>120 minuti (inattività)</td><td>Necessità tecnica</td></tr>
            <tr><td>Richieste esercizio diritti GDPR</td><td>5 anni dalla richiesta</td><td>Art. 6(1)(c) — accountability Art. 5(2)</td></tr>
        </tbody>
    </table>

    {{-- ── DIRITTI DELL'INTERESSATO (Art. 15(1)(e)+(f)+(g)+(h)) ── --}}
    <div class="section-title">I Tuoi Diritti (Artt. 15-22 GDPR)</div>
    <table class="data-table">
        <tr>
            <td class="label">Art. 15 — Accesso</td>
            <td style="font-size:8.5pt;">Ottenere conferma del trattamento e copia dei dati. <em>Canale: richiesta formale a {{ config('lab.manufacturer.email') }}</em></td>
        </tr>
        <tr>
            <td class="label">Art. 16 — Rettifica</td>
            <td style="font-size:8.5pt;">Correggere dati inesatti o incompleti. <em>Canale: sezione Privacy del tuo profilo o email al Titolare.</em></td>
        </tr>
        <tr>
            <td class="label">Art. 17 — Cancellazione</td>
            <td style="font-size:8.5pt;">Richiedere la rimozione dei dati. <strong>Limitato</strong> per i dati MDR soggetti a conservazione obbligatoria (Art. 10(8) MDR). <em>Canale: sezione "Diritto all'Oblio" del tuo profilo.</em></td>
        </tr>
        <tr>
            <td class="label">Art. 18 — Restrizione</td>
            <td style="font-size:8.5pt;">Ottenere la marcatura logica dei dati senza ulteriore trattamento (es. in caso di contestazione). <em>Canale: sezione Privacy del tuo profilo.</em></td>
        </tr>
        <tr>
            <td class="label">Art. 20 — Portabilità</td>
            <td style="font-size:8.5pt;">Ricevere i tuoi dati in formato strutturato e leggibile da dispositivo automatico. <em>Canale: questo export (</em><em>GET /gdpr/export</em><em>).</em></td>
        </tr>
        <tr>
            <td class="label">Art. 21 — Opposizione</td>
            <td style="font-size:8.5pt;">Opporsi al trattamento basato su legittimo interesse. Non applicabile ai trattamenti basati su contratto o obbligo legale (es. dati MDR). <em>Canale: sezione Privacy del tuo profilo.</em></td>
        </tr>
        <tr>
            <td class="label">Art. 22 — Decisioni automatizzate</td>
            <td style="font-size:8.5pt;">Non applicabile: il sistema non effettua profilazione né decisioni automatizzate con effetti giuridici.</td>
        </tr>
        <tr>
            <td class="label">Reclamo al Garante</td>
            <td style="font-size:8.5pt;">Hai diritto di proporre reclamo al Garante per la Protezione dei Dati Personali (www.gpdp.it — protocollo@pec.gpdp.it) se ritieni che il trattamento violi il GDPR.</td>
        </tr>
    </table>

    <div class="section-title">Titolare e Contatti Privacy</div>
    <table class="data-table">
        <tr><td class="label">Titolare del Trattamento</td><td>{{ config('lab.manufacturer.name') }}</td></tr>
        <tr><td class="label">Email</td><td>{{ config('lab.manufacturer.email') }}</td></tr>
        @if(config('lab.dpo.name'))
        <tr><td class="label">DPO</td><td>{{ config('lab.dpo.name') }} — {{ config('lab.dpo.email') }}</td></tr>
        @else
        <tr><td class="label">Contatto Privacy</td><td>{{ config('lab.manufacturer.email') }} (DPO in corso di nomina)</td></tr>
        @endif
    </table>

    <div class="footer">
        Export GDPR Art. 20 — {{ config('lab.manufacturer.name') }} —
        Generato il {{ $generato_il->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
