<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; line-height: 1.5; color: #333; }

        .doc-title   { font-size: 16pt; font-weight: bold; text-transform: uppercase; color: #111827; margin-bottom: 3px; }
        .doc-subtitle { font-size: 9pt; color: #ff6f3f; font-weight: bold; margin-bottom: 20px; }

        .section-title { background: #f3f4f6; padding: 6px 10px; font-size: 9pt; font-weight: bold;
                         text-transform: uppercase; color: #4b5563; margin-top: 22px; margin-bottom: 8px;
                         border-left: 4px solid #ff6f3f; }

        .meta-table   { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .meta-table td { padding: 7px 10px; border-bottom: 1px solid #eee; font-size: 9.5pt; }
        .meta-label   { font-weight: bold; width: 38%; color: #555; font-size: 8.5pt; text-transform: uppercase; }

        .data-table   { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 9pt; }
        .data-table th { background: #ff6f3f; color: #fff; padding: 6px 10px; text-align: left; font-size: 8.5pt;
                         font-weight: bold; text-transform: uppercase; }
        .data-table td { padding: 6px 10px; border-bottom: 1px solid #f0f0f0; }
        .data-table tr:nth-child(even) td { background: #fafafa; }
        .data-table .num { text-align: right; font-weight: bold; width: 90px; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 7.5pt;
                 font-weight: bold; text-transform: uppercase; }
        .badge-open    { background: #fee2e2; color: #991b1b; }
        .badge-closed  { background: #d1fae5; color: #065f46; }
        .badge-iia     { background: #dbeafe; color: #1e40af; }
        .badge-iib     { background: #ede9fe; color: #5b21b6; }

        .notice-box { border: 1px solid #fcd34d; background: #fffbeb; padding: 10px 14px;
                      font-size: 9pt; margin: 12px 0; }
        .notice-ok  { border-color: #6ee7b7; background: #ecfdf5; }
        .notice-warn { border-color: #fca5a5; background: #fff1f2; }

        .text-block { font-size: 9.5pt; text-align: justify; margin: 6px 0; }
        .mdr-ref    { font-size: 8pt; color: #9ca3af; font-style: italic; }

        .signature-box { margin-top: 40px; border: 2px solid #1a1a1a; padding: 20px; background: #fafafa; }
        .sig-title  { font-size: 8pt; font-weight: bold; text-transform: uppercase; color: #ff6f3f; margin-bottom: 8px; display: block; }
        .sig-line   { border-bottom: 1px solid #aaa; width: 240px; display: inline-block; margin-top: 30px; }
        .sig-label  { font-size: 7.5pt; color: #6b7280; margin-top: 3px; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 7.5pt;
                  color: #9ca3af; border-top: 1px solid #eee; padding-top: 8px; }

        .two-col { width: 100%; border-collapse: collapse; }
        .two-col td { vertical-align: top; padding: 0 8px 0 0; }
    </style>
</head>
<body>

    @include('pdfs.partials.branding_header')

    <div style="text-align: center; margin-bottom: 20px;">
        <div class="doc-title">Post-Market Surveillance Update Report</div>
        <div class="doc-subtitle">PSUR — Art. 86 Regolamento (UE) 2017/745</div>
    </div>

    {{-- METADATI DOCUMENTO --}}
    <table class="meta-table">
        <tr><td class="meta-label">Fabbricante</td><td><strong>{{ $lab }}</strong></td></tr>
        <tr><td class="meta-label">PRRC (Art. 15 MDR)</td><td>{{ $prrc_name }} — <span style="font-size:8.5pt; color:#6b7280;">{{ $prrc_role }}</span></td></tr>
        <tr><td class="meta-label">Periodo di riferimento</td><td><strong>{{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}</strong></td></tr>
        <tr><td class="meta-label">Frequenza</td><td>{{ $freq }}</td></tr>
        <tr><td class="meta-label">Data generazione</td><td>{{ $generato_il->format('d/m/Y \a\l\l\e H:i') }}</td></tr>
        <tr><td class="meta-label">Riferimento normativo</td><td class="mdr-ref">MDR Art. 86 — ISO 13485:2016 §8.2.1</td></tr>
    </table>

    {{-- SEZIONE 1: PRODUZIONE --}}
    <div class="section-title">1. Sommario Produzione</div>

    <p class="text-block">
        Nel periodo di riferimento risultano <strong>{{ $total }}</strong>
        {{ $total === 1 ? 'dispositivo archiviato' : 'dispositivi archiviati' }}.
    </p>

    @if($total > 0)
    <table class="two-col">
        <tr>
            <td style="width: 48%;">
                <table class="data-table">
                    <thead><tr><th>Classe di Rischio</th><th class="num">N°</th></tr></thead>
                    <tbody>
                        @foreach($byRiskClass->sortKeys() as $class => $items)
                        <tr>
                            <td>
                                <span class="badge {{ $class === 'IIb' ? 'badge-iib' : 'badge-iia' }}">
                                    Classe {{ $class }}
                                </span>
                            </td>
                            <td class="num">{{ $items->count() }}</td>
                        </tr>
                        @endforeach
                        @if($byRiskClass->isEmpty())
                        <tr><td colspan="2" style="color:#9ca3af; font-style:italic;">Nessun dato</td></tr>
                        @endif
                    </tbody>
                </table>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%;">
                <table class="data-table">
                    <thead><tr><th>Tipo Dispositivo</th><th class="num">N°</th></tr></thead>
                    <tbody>
                        @foreach($byDeviceType->sortByDesc(fn($g) => $g->count()) as $type => $items)
                        <tr>
                            <td style="font-size:8.5pt;">{{ strtoupper(str_replace('_', ' ', $type)) }}</td>
                            <td class="num">{{ $items->count() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    @else
    <div class="notice-box">
        Nessun dispositivo archiviato nel periodo. Il report è stato generato come documento di evidenza per il dossier PMS.
    </div>
    @endif

    {{-- SEZIONE 2: PMS --}}
    <div class="section-title">2. Sorveglianza Post-Commercializzazione (PMS)</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Revisioni PMS completate</td>
            <td><strong>{{ $pmsReviewed->count() }}</strong> su {{ $total }}</td>
        </tr>
        <tr>
            <td class="meta-label">Revisioni PMS scadute</td>
            <td>
                @if($pmsOverdue->count() > 0)
                    <strong style="color:#dc2626;">{{ $pmsOverdue->count() }}</strong>
                    — <span style="color:#dc2626; font-size:8.5pt;">Pianificare entro 30 giorni</span>
                @else
                    <span style="color:#059669;">Nessuna</span>
                @endif
            </td>
        </tr>
    </table>

    @if($pmsOverdue->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr><th>Codice Ordine</th><th>Studio</th><th>Scadenza PMS</th></tr>
        </thead>
        <tbody>
            @foreach($pmsOverdue as $order)
            @php $pmsDate = $order->pms_next_review_at?->format('d/m/Y') ?? '—'; @endphp
            <tr>
                <td><strong>{{ $order->code }}</strong></td>
                <td style="font-size:8.5pt;">{{ $order->studio?->name ?? '—' }}</td>
                <td style="color:#dc2626; font-weight:bold;">{{ $pmsDate }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- SEZIONE 3: INCIDENTI --}}
    <div class="section-title">3. Reclami e Incidenti (MDR Art. 87)</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Incidenti segnalati nel periodo</td>
            <td><strong>{{ $withIncidents->count() }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Incidenti non risolti</td>
            <td>
                @if($unresolvedIncidents->count() > 0)
                    <strong style="color:#dc2626;">{{ $unresolvedIncidents->count() }}</strong>
                @else
                    <span style="color:#059669;">Nessuno</span>
                @endif
            </td>
        </tr>
    </table>

    @if($withIncidents->isNotEmpty())
    <table class="data-table">
        <thead>
            <tr>
                <th>Codice</th>
                <th>Gravità</th>
                <th>Segnalato il</th>
                <th>Scad. notifica</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody>
            @foreach($withIncidents as $order)
            @php
                $severityLabel = match($order->incident_severity) {
                    'imminente' => 'Imminente',
                    'grave'     => 'Grave',
                    'non_grave' => 'Non grave',
                    default     => strtoupper($order->incident_severity ?? 'N/D'),
                };
                $isOpen = $order->incident_resolved_at === null;
            @endphp
            <tr>
                <td><strong>{{ $order->code }}</strong></td>
                <td>{{ $severityLabel }}</td>
                <td>{{ $order->incident_reported_at?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $order->incident_notification_deadline?->format('d/m/Y') ?? '—' }}</td>
                <td>
                    @if($isOpen)
                        <span class="badge badge-open">Aperto</span>
                    @else
                        <span class="badge badge-closed">Chiuso {{ $order->incident_resolved_at?->format('d/m/Y') }}</span>
                    @endif
                </td>
            </tr>
            @if(!$isOpen && $order->incident_resolution_notes)
            <tr>
                <td colspan="5" style="font-size:8pt; color:#6b7280; padding-top:2px; padding-bottom:8px;">
                    <strong>Esito indagine:</strong> {{ Str::limit($order->incident_resolution_notes, 200) }}
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>
    @else
    <div class="notice-box notice-ok">
        Nessun incidente segnalato nel periodo di riferimento.
    </div>
    @endif

    {{-- SEZIONE 4: BENEFICIO/RISCHIO --}}
    <div class="section-title">4. Valutazione Beneficio/Rischio (MDR Allegato XIV)</div>

    @if($withIncidents->isEmpty())
    <p class="text-block">
        In assenza di segnalazioni di incidenti nel periodo, il profilo beneficio/rischio dei dispositivi
        fabbricati risulta <strong>invariato</strong> rispetto alla documentazione tecnica corrente
        (Allegato XIII MDR). Non emergono elementi che richiedano una rivalutazione del rapporto rischio/beneficio
        dichiarato nella DPIA e nei documenti tecnici di prodotto.
    </p>
    @else
    <div class="notice-box notice-warn">
        <strong>Attenzione:</strong> {{ $withIncidents->count() }} incidente/i rilevato/i nel periodo —
        vedere Sezione 3 per il dettaglio. La valutazione beneficio/rischio deve essere aggiornata
        sulla base dell'esito delle indagini prima della chiusura del presente PSUR.
    </div>
    @if($unresolvedIncidents->isNotEmpty())
    <p class="text-block">
        <strong style="color:#dc2626;">{{ $unresolvedIncidents->count() }} indagine/i ancora aperta/e.</strong>
        Il PRRC deve completare l'analisi e aggiornare il presente documento prima della firma.
    </p>
    @endif
    @endif

    {{-- SEZIONE 5: CONCLUSIONI E CAPA --}}
    <div class="section-title">5. Conclusioni e Azioni Correttive (CAPA)</div>

    @if($unresolvedIncidents->isEmpty() && $pmsOverdue->isEmpty())
    <div class="notice-box notice-ok">
        Nessuna azione correttiva o preventiva necessaria per il periodo in esame.
        Il sistema di sorveglianza post-commercializzazione è operativo e aggiornato.
    </div>
    @else
    <table class="data-table">
        <thead><tr><th>Area</th><th>Descrizione</th><th>Priorità</th></tr></thead>
        <tbody>
            @if($unresolvedIncidents->isNotEmpty())
            <tr>
                <td><strong>Incidenti aperti</strong></td>
                <td style="font-size:8.5pt;">{{ $unresolvedIncidents->count() }} indagine/i in attesa di chiusura — completare analisi causa e misure correttive</td>
                <td><span class="badge badge-open">Alta</span></td>
            </tr>
            @endif
            @if($pmsOverdue->isNotEmpty())
            <tr>
                <td><strong>Revisione PMS</strong></td>
                <td style="font-size:8.5pt;">{{ $pmsOverdue->count() }} revisione/i PMS scaduta/e — pianificare entro 30 giorni</td>
                <td><span class="badge" style="background:#fef3c7; color:#92400e;">Media</span></td>
            </tr>
            @endif
        </tbody>
    </table>
    @endif

    {{-- BOX FIRMA --}}
    <div class="signature-box">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td width="55%" style="vertical-align: top;">
                    <span class="sig-title">Firma PRRC — Persona Responsabile Conformità Regolamentare</span>
                    <div style="font-size: 9.5pt; line-height: 1.8; color: #333;">
                        <strong>{{ $prrc_name }}</strong><br>
                        <span style="font-size:8.5pt; color:#6b7280;">{{ $prrc_role }}</span><br>
                        {{ $lab }}
                    </div>
                    <div style="margin-top: 28px;">
                        <div class="sig-line"></div>
                        <div class="sig-label">Firma</div>
                    </div>
                </td>
                <td width="45%" style="text-align: right; vertical-align: top;">
                    <span class="sig-title">Data di Firma e Archiviazione</span>
                    <div style="font-size: 9pt; color: #6b7280; margin-bottom: 24px;">
                        Il presente PSUR deve essere firmato dal PRRC<br>
                        entro 30 giorni dalla generazione e archiviato<br>
                        nel fascicolo MDR del fabbricante.
                    </div>
                    <div class="sig-line" style="width: 180px;"></div>
                    <div class="sig-label" style="text-align: right;">Data (gg/mm/aaaa)</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        PSUR Art. 86 MDR 2017/745 — {{ $lab }} —
        Periodo {{ $from->format('d/m/Y') }} / {{ $to->format('d/m/Y') }} —
        Generato il {{ $generato_il->format('d/m/Y H:i') }}
    </div>

</body>
</html>
