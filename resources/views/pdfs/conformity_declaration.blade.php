<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; line-height: 1.5; color: #333; }
        .section-title { background-color: #f3f4f6; padding: 6px 10px; font-size: 10px; font-weight: bold; text-transform: uppercase; color: #4b5563; margin-top: 20px; border-left: 4px solid #ff6f3f; }
        .title { font-size: 16pt; font-weight: bold; text-transform: uppercase; color: #111827; margin-bottom: 5px; }
        .data-table { width: 100%; margin: 15px 0; border-collapse: collapse; }
        .data-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .label { font-weight: bold; width: 35%; color: #666; font-size: 9pt; text-transform: uppercase; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8pt; color: #9ca3af; border-top: 1px solid #eee; padding-top: 10px; }
        .mdr-accent { color: #ff6f3f; font-weight: bold; }
        .device-item { margin-bottom: 4px; font-weight: bold; font-size: 10pt; }
        .device-meta { font-size: 8pt; color: #666; font-weight: normal; text-transform: uppercase; }
        .signature-box { margin-top: 50px; border: 2px solid #1a1a1a; padding: 20px; background-color: #fafafa; }
        .ts-title { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #ff6f3f; margin-bottom: 10px; display: block; }
        .ts-data { font-family: monospace; font-size: 12px; font-weight: bold; color: #000; }
        .hash-info { font-size: 7pt; color: #888; font-family: 'Courier', monospace; word-wrap: break-word; margin-top: 10px; text-transform: uppercase; }
    </style>
</head>
<body>
    @include('pdfs.partials.branding_header')

    @php
        $studio = $order->studio;
        $isLab = !empty($studio->itca_code);
        
        // Differenziazione pura basata sulla tipologia di cliente
        $tipoConformita = $isLab ? 'Semilavorato / Conto Terzi' : 'su Misura';
        $labelRiferimento = $isLab ? 'Lavoro' : 'Paziente';
        $labelSoggetto = $isLab ? 'Responsabile Tecnico' : 'Clinico Prescrittore';
    @endphp

    <div style="text-align: center; margin-bottom: 25px;">
        <div class="title">Dichiarazione di Conformità UE</div>
        <div style="font-size: 9pt; color: #ff6f3f; font-weight: bold;">
            Dispositivo Medico {{ $tipoConformita }} - Regolamento (UE) 2017/745 All. XIII
        </div>
    </div>

    <p>Il fabbricante dichiara sotto la propria esclusiva responsabilità che il dispositivo medico identificato di seguito è conforme ai requisiti generali di sicurezza e prestazione enunciati all'Allegato I del Regolamento (UE) 2017/745.</p>

    <div class="section-title">Identificazione Dispositivo/i</div>
    <table class="data-table">
        <tr><td class="label">Codice Identificativo:</td><td><strong class="mdr-accent">{{ $order->code }}</strong></td></tr>
        <tr><td class="label">Riferimento {{ $labelRiferimento }}:</td><td>{{ strtoupper($order->patient_ref) }}</td></tr>
        <tr>
            <td class="label" valign="top">Descrizione Dispositivo/i:</td>
            <td>
                @php $devices = $order->manufactured_devices ?? []; @endphp
                @if(empty($devices))
                    <div class="device-item">{{ strtoupper(str_replace('_', ' ', $order->final_device_name ?? $order->device_type)) }}</div>
                    <div class="device-meta">Classe {{ $order->risk_class ?? 'IIa' }} | {{ $order->mdr_rule_applied ?? 'Regola 8' }}</div>
                @else
                    @foreach($devices as $dev)
                        <div class="device-item">{{ strtoupper($dev['name']) }}</div>
                        <div class="device-meta" style="margin-bottom: 8px;">
                            Classe {{ $dev['risk_class'] }} | {{ $dev['mdr_rule'] }} | Durata: {{ $dev['duration_use'] ?? 'Lungo Termine' }}
                        </div>
                    @endforeach
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">{{ $labelSoggetto }}:</td>
            <td>
                {{ $isLab ? '' : 'Dott.' }} {{ $order->address->dentist_name ?? ($order->studio->dentist_name ?? 'N/D') }}
                @if($isLab) <br><span style="font-size:8pt; color:#666;">(ITCA: {{ $order->studio->itca_code }})</span> @endif
            </td>
        </tr>
    </table>

    <div class="section-title">Dichiarazione Legale</div>
    <p style="font-size: 9.5pt; text-align: justify;">Il fabbricante si impegna a conservare e mettere a disposizione delle autorità competenti la documentazione tecnica (Allegato XIII) per un periodo di <strong class="mdr-accent">15 anni</strong> dalla data di immissione in commercio del dispositivo.</p>

    <div class="signature-box">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td width="60%" style="vertical-align: top;">
                    <span class="ts-title">Certificazione di Rilascio Fabbricante</span>
                    <div style="font-size: 10px; line-height: 1.5; color: #333;">
                        Fabbricante: <strong>{{ config('lab.manufacturer.name') }}</strong><br>
                        PRRC: <strong>{{ config('lab.prrc.name') }}</strong>
                        — <span style="font-size:9px;">{{ config('lab.prrc.role') }}</span><br>
                        Verificatore QC: <strong>{{ $order->production->qc_verifier ?? 'Responsabile Rilascio' }}</strong><br>
                        Conformità: <strong>Regolamento (UE) 2017/745 - Allegato XIII</strong>
                    </div>
                </td>
                <td width="40%" style="text-align: right; vertical-align: top;">
                    <span class="ts-title">Data di Emissione Originaria</span>
                    <div class="ts-data">{{ $order->production->released_at ? $order->production->released_at->format('d/m/Y - H:i:s') : 'NON_VALIDATO' }}</div>
                    <div class="hash-info">
                        FINGERPRINT SHA256:<br>
                        {{ $order->conformity_hash ?? 'NON_VALIDATED' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">Documentazione conforme all'Allegato XIII MDR (UE) 2017/745 • {{ config('app.name') }}</div>
</body>
</html>