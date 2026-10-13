<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.4; }
        .section-title { font-size: 10pt; font-weight: bold; color: #4b5563; background: #f9fafb; padding: 6px 10px; margin-top: 20px; border-left: 4px solid #ff6f3f; text-transform: uppercase; }
        .section-title-alert { font-size: 10pt; font-weight: bold; color: #fff; background: #dc2626; padding: 6px 10px; margin-top: 20px; border-left: 4px solid #991b1b; text-transform: uppercase; }
        .data-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .data-table td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .label { font-weight: bold; width: 40%; color: #6b7280; font-size: 8.5pt; text-transform: uppercase; }
        .event-card { margin-top: 10px; padding: 12px; border: 1px solid #eee; border-radius: 8px; background: #fcfcfc; }
        .event-card-alert { margin-top: 10px; padding: 12px; border: 1px solid #fca5a5; border-radius: 8px; background: #fef2f2; }
        .event-date { font-size: 8pt; font-weight: bold; color: #ff6f3f; }
        .event-date-alert { font-size: 8pt; font-weight: bold; color: #dc2626; }
        .badge-imminente { background: #7f1d1d; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 8pt; font-weight: bold; }
        .badge-grave { background: #dc2626; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 8pt; font-weight: bold; }
        .badge-non_grave { background: #d97706; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 8pt; font-weight: bold; }
        .badge-active { background: #16a34a; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 8pt; font-weight: bold; }
        .deadline-box { background: #fef2f2; border: 2px solid #dc2626; border-radius: 8px; padding: 12px; margin-top: 10px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8pt; color: #9ca3af; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    @include('pdfs.partials.branding_header')

    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="margin:0; font-size: 18pt; text-transform: uppercase;">Rapporto di Sintesi PMS</h1>
        <div style="font-size: 9pt; color: #6b7280;">Sorveglianza Post-Commercializzazione (Art. 83-87 MDR 2017/745)</div>
    </div>

    {{-- SEZIONE ALLERTA se incidente aperto --}}
    @if($order->pms_status === 'incident_reported')
    <div class="deadline-box">
        <strong style="color:#dc2626; font-size: 11pt;">⚠️ INCIDENTE IN CORSO — AZIONE RICHIESTA</strong><br>
        @php
            $deadlineDays = match($order->incident_severity ?? 'grave') {
                'imminente' => 2,
                'grave'     => 15,
                default     => 30,
            };
            $daysLeft = $order->incident_notification_deadline
                ? (int) now()->diffInDays($order->incident_notification_deadline, false)
                : null;
        @endphp
        <table style="width:100%; margin-top:8px; font-size:9pt;">
            <tr>
                <td><strong>Segnalato il:</strong> {{ $order->incident_reported_at?->format('d/m/Y H:i') ?? 'N/D' }}</td>
                <td><strong>Scadenza notifica Ministero:</strong>
                    <span style="color:#dc2626; font-weight:bold;">
                        {{ $order->incident_notification_deadline?->format('d/m/Y') ?? 'N/D' }}
                        @if($daysLeft !== null)
                            ({{ $daysLeft < 0 ? 'SCADUTA' : ($daysLeft === 0 ? 'OGGI' : $daysLeft . ' giorni rimanenti') }})
                        @endif
                    </span>
                </td>
            </tr>
        </table>
        <div style="margin-top:6px; font-size:8.5pt; color:#7f1d1d;">
            Il fabbricante è tenuto a notificare l'incidente al Ministero della Salute entro i termini perentori dell'Art. 87 MDR.
        </div>
    </div>
    @endif

    {{-- IDENTIFICAZIONE DISPOSITIVO --}}
    <div class="section-title">Identificazione Dispositivo</div>
    <table class="data-table">
        <tr>
            <td class="label">Codice Pratica:</td>
            <td><strong style="color:#ff6f3f">{{ $order->code }}</strong></td>
        </tr>
        <tr>
            <td class="label">Tipologia:</td>
            <td>{{ strtoupper(str_replace('_', ' ', $order->device_type)) }}</td>
        </tr>
        <tr>
            <td class="label">Classe di Rischio:</td>
            <td>Classe {{ $order->risk_class ?? 'IIa' }}</td>
        </tr>
        <tr>
            <td class="label">Numero Lotto:</td>
            <td>{{ $order->lot_number ?? 'N/D' }}</td>
        </tr>
        <tr>
            <td class="label">Data Immissione sul Mercato:</td>
            <td>{{ $order->archived_at?->format('d/m/Y') ?? 'Non ancora archiviato' }}</td>
        </tr>
        <tr>
            <td class="label">Stato PMS:</td>
            <td>
                @if($order->pms_status === 'incident_reported')
                    <span class="badge-{{ $order->incident_severity ?? 'grave' }}">
                        INCIDENTE {{ strtoupper(str_replace('_', ' ', $order->incident_severity ?? 'GRAVE')) }}
                    </span>
                @elseif($order->pms_status === 'review_due')
                    <span class="badge-non_grave">REVISIONE SCADUTA</span>
                @else
                    <span class="badge-active">SORVEGLIANZA ATTIVA</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Ultima Revisione:</td>
            <td>{{ $order->pms_last_review_at?->format('d/m/Y') ?? 'Nessuna revisione registrata' }}</td>
        </tr>
        <tr>
            <td class="label">Prossima Revisione Prevista:</td>
            <td>{{ $order->pms_next_review_at?->format('d/m/Y') ?? 'Non pianificata' }}</td>
        </tr>
    </table>

    {{-- DETTAGLIO INCIDENTE (solo se aperto o risolto) --}}
    @if($order->incident_reported_at)
    <div class="{{ $order->pms_status === 'incident_reported' ? 'section-title-alert' : 'section-title' }}">
        Dettaglio Incidente MDR (Art. 87)
    </div>
    <table class="data-table">
        <tr>
            <td class="label">Data Segnalazione:</td>
            <td>{{ $order->incident_reported_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Gravità:</td>
            <td>
                @php
                    $label = match($order->incident_severity) {
                        'imminente' => 'Rischio grave imminente per salute pubblica (termine: 2 giorni)',
                        'grave'     => 'Incidente grave (termine: 15 giorni)',
                        'non_grave' => 'Incidente non grave / problema prestazioni (termine: 30 giorni)',
                        default     => 'N/D',
                    };
                @endphp
                {{ $label }}
            </td>
        </tr>
        <tr>
            <td class="label">Scadenza Notifica Ministero:</td>
            <td style="color: {{ $order->pms_status === 'incident_reported' ? '#dc2626' : 'inherit' }}; font-weight: bold;">
                {{ $order->incident_notification_deadline?->format('d/m/Y') ?? 'N/D' }}
            </td>
        </tr>
        @if($order->incident_resolved_at)
        <tr>
            <td class="label">Data Chiusura Indagine:</td>
            <td>{{ $order->incident_resolved_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Misure Correttive Adottate:</td>
            <td>{{ $order->incident_resolution_notes }}</td>
        </tr>
        @endif
    </table>
    @endif

    {{-- REGISTRO EVENTI --}}
    <div class="section-title">Registro Eventi (Audit Trail)</div>
    @forelse($order->comments as $comment)
        @php
            $isIncidentEvent = str_contains($comment->body, 'VIGILANZA') || str_contains($comment->body, '🚨');
        @endphp
        <div class="{{ $isIncidentEvent ? 'event-card-alert' : 'event-card' }}">
            <span class="{{ $isIncidentEvent ? 'event-date-alert' : 'event-date' }}">
                {{ $comment->created_at->format('d/m/Y H:i') }}
            </span>
            <div style="margin-top: 5px; font-size: 9.5pt;">
                {{ str_replace(['PMS FEEDBACK: ', '🤖 SISTEMA: '], '', $comment->body) }}
            </div>
        </div>
    @empty
        <p style="font-style: italic; color: #9ca3af; text-align: center; margin-top: 20px;">Nessuna segnalazione o evento avverso registrato.</p>
    @endforelse

    {{-- CONCLUSIONE (condizionale) --}}
    <div style="margin-top: 50px; font-size: 9pt; border-top: 1px dashed #ccc; padding-top: 20px;">
        @if($order->pms_status === 'incident_reported')
            <p style="color:#dc2626; font-weight:bold;">
                ⚠️ INDAGINE IN CORSO: Il presente documento non attesta la conclusione del monitoraggio.
                Il fabbricante è tenuto a notificare l'incidente al Ministero della Salute entro i termini perentori dell'Art. 87 MDR.
            </p>
        @elseif($order->incident_reported_at && $order->incident_resolved_at)
            <p>Il presente documento attesta la conclusione dell'indagine sull'incidente segnalato in data
            {{ $order->incident_reported_at->format('d/m/Y') }} e le misure correttive adottate.
            Il monitoraggio proattivo del dispositivo continua ai sensi dell'Allegato XIII, punto 5.</p>
        @else
            <p>Il presente documento attesta il monitoraggio proattivo del dispositivo ai sensi dell'Allegato XIII, punto 5.
            Non sono stati rilevati incidenti o eventi avversi nel periodo di sorveglianza.</p>
        @endif

        <table width="100%" style="margin-top: 20px;">
            <tr>
                <td>Generato il: <strong>{{ now()->format('d/m/Y H:i') }}</strong></td>
                <td style="text-align:right;">
                    PRRC: <strong>{{ config('lab.prrc.name') }}</strong><br>
                    <span style="border-bottom: 1px solid #000; display:inline-block; width: 200px; padding-top: 25px;">Firma</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">Documento conforme al Regolamento (UE) 2017/745 (MDR) — Art. 83-87 Sorveglianza Post-Commercializzazione</div>
</body>
</html>
