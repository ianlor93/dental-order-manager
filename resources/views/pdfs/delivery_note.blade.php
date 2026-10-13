<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Distinta di Consegna - {{ $note->id }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #1a1a1a; line-height: 1.4; }
        .section-title { font-weight: bold; text-transform: uppercase; font-size: 8.5pt; color: #ff6f3f; border-bottom: 1px solid #eee; margin-top: 15px; margin-bottom: 6px; }
        .data-box { background: #fdfdfd; padding: 12px; border: 1px solid #f0f0f0; border-radius: 8px; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8pt; color: #9ca3af; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        
        /* Tabella Stile Professionale */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .items-table th { text-align: left; font-size: 8pt; text-transform: uppercase; color: #ff6f3f; border-bottom: 1px solid #ff6f3f; padding: 8px 4px; }
        .items-table td { padding: 10px 4px; border-bottom: 1px solid #f0f0f0; vertical-align: top; font-size: 9pt; }
        
        .signature-box { margin-top: 30px; border: 2px solid #1a1a1a; padding: 20px; background-color: #fafafa; }
        .ts-title { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #ff6f3f; margin-bottom: 10px; display: block; }
        
        ul.mdr-list { margin: 0; padding-left: 12px; list-style-type: square; }
        ul.mdr-list li { margin-bottom: 2px; }
    </style>
</head>
<body>
    @include('pdfs.partials.branding_header')

    <div style="text-align: right; margin-bottom: 20px;">
        <h1 style="margin:0; font-size: 18pt; text-transform: uppercase;">Distinta di Consegna</h1>
        <div style="font-size: 9pt; color: #ff6f3f; font-weight: bold;">DDT N. {{ $note->id }} / {{ $note->delivery_date->format('Y') }}</div>
    </div>

    <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td width="55%" valign="top" style="padding-right: 10px;">
                <div class="section-title">Destinatario Fiscale</div>
                <div class="data-box">
                    <strong style="font-size: 11pt; text-transform: uppercase;">{{ $studio->name }}</strong><br>
                    {{ $studio->address }}<br>
                    {{ $studio->zip_code }} {{ $studio->city }} ({{ $studio->province ?? 'IT' }})<br>
                    <strong>P.IVA:</strong> {{ $studio->vat_number }}
                </div>
            </td>
            <td width="45%" valign="top" style="padding-left: 10px;">
                <div class="section-title">Dati Spedizione</div>
                <div class="data-box">
                    <strong>Data:</strong> {{ $note->delivery_date->format('d/m/Y') }}<br>
                    <strong>Vettore:</strong> {{ $note->courier }}<br>
                    <strong>Tracking:</strong> {{ $note->tracking_code ?? '---' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Elenco Analitico Dispositivi Medici su Misura (MDR 2017/745)</div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th width="15%">Codice</th>
                <th width="35%">Rif. Lavoro / Referente</th>
                <th width="50%">Dispositivo (Specifiche MDR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            @php
                // Logica per distinguere Lab da Medico nella riga della tabella
                $isLab = !empty($order->studio->itca_code);
                $referenteTitle = $isLab ? 'Resp.' : 'Dott.';
                $referenteName = $order->address?->dentist_name ?? $order->studio?->dentist_name;
            @endphp
            <tr>
                <td style="font-family: monospace; font-weight: bold;">{{ $order->code }}</td>
                <td>
                    <div style="font-weight: bold; text-transform: uppercase;">{{ $order->patient_ref }}</div>
                    <div style="font-size: 8pt; color: #666;">
                        {{ $referenteTitle }} {{ $referenteName }}
                    </div>
                </td>
                <td>
                    @if(!empty($order->manufactured_devices) && is_array($order->manufactured_devices))
                        <ul class="mdr-list">
                            @foreach($order->manufactured_devices as $dev)
                                <li>{{ is_array($dev) ? ($dev['name'] ?? 'N/D') : $dev }}</li>
                            @endforeach
                        </ul>
                    @else
                        <strong>{{ str_replace('_', ' ', strtoupper($order->device_type)) }}</strong>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 8.5pt; text-align: justify; padding: 10px; border: 1px solid #eee; border-radius: 8px;">
        <strong>Nota MDR:</strong> Il presente documento accompagna dispositivi medici conformi al Regolamento (UE) 2017/745. La documentazione tecnica (Dichiarazione di Conformità e Istruzioni) è disponibile presso i nostri archivi per 15 anni.
    </div>

    <div class="signature-box">
        <table width="100%">
            <tr>
                <td width="50%">
                    <span class="ts-title">Firma per Ricevuta</span>
                    <div style="border-bottom: 1px solid #ccc; width: 80%; margin-top: 25px;"></div>
                </td>
                <td width="50%" style="text-align: right;">
                    <span class="ts-title">Firma Vettore</span>
                    <div style="border-bottom: 1px solid #ccc; width: 80%; float: right; margin-top: 25px;"></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">Documento conforme all'Allegato XIII del Regolamento (UE) 2017/745 - {{ config('app.name') }}</div>
</body>
</html>