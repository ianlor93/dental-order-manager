<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 9.5pt; line-height: 1.5; color: #333; }

        .doc-title    { font-size: 15pt; font-weight: bold; text-transform: uppercase; color: #111827; margin-bottom: 3px; }
        .doc-subtitle { font-size: 9pt; color: #ff6f3f; font-weight: bold; margin-bottom: 20px; }

        .section-title { background: #f3f4f6; padding: 6px 10px; font-size: 9pt; font-weight: bold;
                         text-transform: uppercase; color: #4b5563; margin-top: 22px; margin-bottom: 8px;
                         border-left: 4px solid #ff6f3f; }

        .meta-table   { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta-table td { padding: 6px 10px; border-bottom: 1px solid #eee; font-size: 9pt; }
        .meta-label   { font-weight: bold; width: 38%; color: #555; font-size: 8.5pt; text-transform: uppercase; }

        .summary-grid { width: 100%; border-collapse: collapse; margin: 10px 0 18px; }
        .summary-grid td { padding: 10px 14px; text-align: center; border: 1px solid #e5e7eb; }
        .sum-num  { font-size: 20pt; font-weight: bold; display: block; line-height: 1.1; }
        .sum-lbl  { font-size: 7.5pt; text-transform: uppercase; color: #6b7280; display: block; margin-top: 2px; }
        .sum-ok   { background: #ecfdf5; color: #065f46; }
        .sum-warn { background: #fffbeb; color: #92400e; }
        .sum-err  { background: #fff1f2; color: #991b1b; }
        .sum-neu  { background: #f9fafb; color: #374151; }

        .esito-ok   { background: #d1fae5; color: #065f46; padding: 8px 14px; font-weight: bold;
                      font-size: 10pt; text-align: center; margin: 14px 0; }
        .esito-err  { background: #fee2e2; color: #991b1b; padding: 8px 14px; font-weight: bold;
                      font-size: 10pt; text-align: center; margin: 14px 0; }

        .order-block { margin-bottom: 14px; page-break-inside: avoid; }
        .order-hdr   { background: #1f2937; color: #fff; padding: 5px 10px; font-size: 8.5pt;
                       font-weight: bold; display: flex; justify-content: space-between; }
        .order-hdr-anomaly { background: #991b1b; }

        .files-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        .files-table td { padding: 4px 8px; border-bottom: 1px solid #f0f0f0; }
        .files-table tr:nth-child(even) td { background: #fafafa; }
        .files-table .fname { width: 45%; }
        .files-table .fstatus { width: 12%; text-align: center; }
        .files-table .fhash { font-family: 'Courier', monospace; font-size: 6.5pt; color: #6b7280;
                              word-break: break-all; }

        .badge { display: inline-block; padding: 1px 7px; border-radius: 8px; font-size: 7pt;
                 font-weight: bold; text-transform: uppercase; }
        .b-ok       { background: #d1fae5; color: #065f46; }
        .b-missing  { background: #fee2e2; color: #991b1b; }
        .b-tampered { background: #7f1d1d; color: #fff; }
        .b-nohash   { background: #fef3c7; color: #92400e; }
        .b-skip     { background: #e5e7eb; color: #6b7280; }

        .anomaly-list { margin: 10px 0; }
        .anomaly-item { padding: 5px 10px; border-left: 3px solid #dc2626;
                        background: #fff1f2; font-size: 8.5pt; margin-bottom: 4px; }

        .signature-box { margin-top: 40px; border: 2px solid #1a1a1a; padding: 20px; background: #fafafa; }
        .sig-title  { font-size: 8pt; font-weight: bold; text-transform: uppercase; color: #ff6f3f;
                      margin-bottom: 8px; display: block; }
        .sig-line   { border-bottom: 1px solid #aaa; width: 240px; display: inline-block; margin-top: 30px; }
        .sig-label  { font-size: 7.5pt; color: #6b7280; margin-top: 3px; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 7.5pt;
                  color: #9ca3af; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>

    @include('pdfs.partials.branding_header')

    <div style="text-align: center; margin-bottom: 20px;">
        <div class="doc-title">Report Integrità Archivio MDR</div>
        <div class="doc-subtitle">Verifica SHA-256 — Art. 10(8) MDR 2017/745 — ISO 13485 §4.2.5</div>
    </div>

    {{-- METADATI --}}
    <table class="meta-table">
        <tr><td class="meta-label">Fabbricante</td><td><strong>{{ $lab }}</strong></td></tr>
        <tr><td class="meta-label">PRRC (Art. 15 MDR)</td><td>{{ $prrc_name }} — <span style="font-size:8.5pt; color:#6b7280;">{{ $prrc_role }}</span></td></tr>
        <tr><td class="meta-label">Mese di verifica</td><td><strong>{{ $month }}</strong></td></tr>
        <tr><td class="meta-label">Data generazione</td><td>{{ $generato_il->format('d/m/Y \a\l\l\e H:i:s') }}</td></tr>
        <tr><td class="meta-label">Durata verifica</td><td>{{ $duration }}s</td></tr>
        <tr><td class="meta-label">Ordini verificati</td><td>{{ count($ordersResults) }}</td></tr>
    </table>

    {{-- SOMMARIO CONTATORI --}}
    <div class="section-title">Sommario</div>

    <table class="summary-grid">
        <tr>
            <td class="sum-ok">
                <span class="sum-num">{{ $ok }}</span>
                <span class="sum-lbl">File integri</span>
            </td>
            <td class="sum-err">
                <span class="sum-num">{{ $tampered }}</span>
                <span class="sum-lbl">File alterati</span>
            </td>
            <td class="sum-err">
                <span class="sum-num">{{ $missing }}</span>
                <span class="sum-lbl">File mancanti</span>
            </td>
            <td class="sum-warn">
                <span class="sum-num">{{ $noHash }}</span>
                <span class="sum-lbl">Senza hash</span>
            </td>
            <td class="sum-neu">
                <span class="sum-num">{{ $checked }}</span>
                <span class="sum-lbl">Totale verificati</span>
            </td>
        </tr>
    </table>

    @if($tampered + $missing === 0)
    <div class="esito-ok">✓ ARCHIVIO INTEGRO — Nessuna anomalia rilevata</div>
    @else
    <div class="esito-err">⚠ ANOMALIE RILEVATE — {{ $tampered }} file alterati, {{ $missing }} file mancanti</div>
    @endif

    {{-- ANOMALIE IN EVIDENZA (se presenti) --}}
    @if(!empty($anomalies))
    <div class="section-title">Anomalie Rilevate</div>
    <div class="anomaly-list">
        @foreach($anomalies as $a)
        <div class="anomaly-item">{{ $a }}</div>
        @endforeach
    </div>
    @endif

    {{-- DETTAGLIO PER ORDINE --}}
    <div class="section-title">Dettaglio Verifica per Ordine</div>

    @foreach($ordersResults as $res)
    <div class="order-block">
        <table style="width:100%; border-collapse:collapse; margin-bottom:1px;">
            <tr>
                <td style="background:{{ $res['has_anomaly'] ? '#991b1b' : '#1f2937' }}; color:#fff;
                            padding:5px 10px; font-size:8.5pt; font-weight:bold;">
                    Ordine: <strong>{{ $res['code'] }}</strong>
                </td>
                <td style="background:{{ $res['has_anomaly'] ? '#991b1b' : '#1f2937' }}; color:#d1d5db;
                            padding:5px 10px; font-size:7.5pt; text-align:right;">
                    Archiviato: {{ $res['archived_at'] ?? '—' }} &nbsp;|&nbsp; Retention: {{ $res['retention_until'] ?? '—' }}
                    @if($res['has_anomaly']) &nbsp;|&nbsp; ⚠ ANOMALIE @endif
                </td>
            </tr>
        </table>

        @if($res['skip'])
        <table class="files-table">
            <tr><td colspan="3" style="color:#9ca3af; font-style:italic; padding:6px 8px;">
                Nessun file associato all'ordine.
            </td></tr>
        </table>
        @else
        <table class="files-table">
            <thead style="background:#f3f4f6;">
                <tr>
                    <td class="fname" style="font-weight:bold; font-size:7.5pt; text-transform:uppercase; padding:4px 8px;">File</td>
                    <td class="fstatus" style="font-weight:bold; font-size:7.5pt; text-transform:uppercase;">Esito</td>
                    <td style="font-weight:bold; font-size:7.5pt; text-transform:uppercase; padding:4px 8px;">Hash SHA-256</td>
                </tr>
            </thead>
            <tbody>
                @foreach($res['files'] as $f)
                <tr @if(in_array($f['status'], ['tampered','missing'])) style="background:#fff1f2;" @endif>
                    <td class="fname">{{ $f['name'] }}</td>
                    <td class="fstatus">
                        @if($f['status'] === 'ok')
                            <span class="badge b-ok">OK</span>
                        @elseif($f['status'] === 'missing')
                            <span class="badge b-missing">Mancante</span>
                        @elseif($f['status'] === 'tampered')
                            <span class="badge b-tampered">Alterato</span>
                        @elseif($f['status'] === 'no_hash')
                            <span class="badge b-nohash">No hash</span>
                        @endif
                    </td>
                    <td class="fhash">
                        @if($f['status'] === 'ok')
                            {{ $f['hash'] }}
                        @elseif($f['status'] === 'tampered')
                            <span style="color:#dc2626;">Atteso:&nbsp;</span>{{ $f['expected'] }}<br>
                            <span style="color:#dc2626;">Trovato:</span>{{ $f['actual'] }}
                        @elseif($f['status'] === 'missing')
                            <span style="color:#dc2626;">File non trovato su ARCH_L</span>
                        @else
                            <span style="color:#92400e;">Hash assente nel DB — verifica impossibile</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endforeach

    {{-- FIRMA --}}
    <div class="signature-box">
        <table width="100%" style="border-collapse:collapse;">
            <tr>
                <td width="55%" style="vertical-align:top;">
                    <span class="sig-title">Firma PRRC — Persona Responsabile Conformità Regolamentare</span>
                    <div style="font-size:9.5pt; line-height:1.8; color:#333;">
                        <strong>{{ $prrc_name }}</strong><br>
                        <span style="font-size:8.5pt; color:#6b7280;">{{ $prrc_role }}</span><br>
                        {{ $lab }}
                    </div>
                    <div style="margin-top:28px;">
                        <div class="sig-line"></div>
                        <div class="sig-label">Firma</div>
                    </div>
                </td>
                <td width="45%" style="text-align:right; vertical-align:top;">
                    <span class="sig-title">Data di Firma e Archiviazione</span>
                    <div style="font-size:9pt; color:#6b7280; margin-bottom:24px;">
                        Il presente report deve essere firmato<br>
                        dal PRRC e archiviato nel fascicolo MDR.
                    </div>
                    <div class="sig-line" style="width:180px;"></div>
                    <div class="sig-label" style="text-align:right;">Data (gg/mm/aaaa)</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Report Integrità Archivio MDR Art. 10(8) — {{ $lab }} — {{ $month }} —
        Generato il {{ $generato_il->format('d/m/Y H:i') }}
    </div>

</body>
</html>
