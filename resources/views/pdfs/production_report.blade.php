<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Scheda Tecnica MDR - {{ $order->code }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; color: #1f2937; line-height: 1.4; margin: 0; padding: 0; }
        .section-title { background-color: #f9fafb; padding: 8px 12px; font-size: 10pt; font-weight: bold; text-transform: uppercase; color: #4b5563; margin-top: 20px; border-left: 4px solid #ff6f3f; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 10px; text-align: left; font-size: 11px; }
        th { background-color: #f9fafb; font-weight: bold; color: #374151; width: 35%; }
        .mdr-tag { display: inline-block; background: #ff6f3f; color: white; padding: 2px 6px; border-radius: 4px; font-weight: 900; font-size: 8px; margin-left: 5px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8pt; color: #9ca3af; border-top: 1px solid #eee; padding-top: 10px; }
        .signature-box { margin-top: 40px; border: 2px solid #1a1a1a; padding: 20px; background-color: #fafafa; }
        .ts-title { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #ff6f3f; margin-bottom: 10px; display: block; }
        .ts-data { font-family: monospace; font-size: 12px; font-weight: bold; color: #000; }
        .hash-info { font-size: 7pt; color: #888; font-family: 'Courier', monospace; word-wrap: break-word; margin-top: 10px; text-transform: uppercase; }
    </style>
</head>
<body>
    @include('pdfs.partials.branding_header')

    @php
        // Decodifica JSON sicura per evitare errori se i campi sono nulli
        $teethLib = is_array($production->cad_teeth_library) ? $production->cad_teeth_library : json_decode($production->cad_teeth_library, true) ?? [];
        $camData = is_array($production->cam_data) ? $production->cam_data : json_decode($production->cam_data, true) ?? [];
        $labMaterials = is_array($production->lab_materials) ? $production->lab_materials : json_decode($production->lab_materials, true) ?? [];
        $qcChecklist = is_array($production->qc_checklist) ? $production->qc_checklist : json_decode($production->qc_checklist, true) ?? [];
        $devices = $order->manufactured_devices ?? [];
    @endphp

    <div style="text-align: center; margin-bottom: 20px;">
        <h1 style="margin:0; font-size: 18pt; text-transform: uppercase;">Scheda Tecnica di Produzione</h1>
        <div style="font-size: 10pt; color: #ff6f3f; font-weight: bold;">Fascicolo Tecnico Pratica: {{ $order->code }}</div>
    </div>

    {{-- CONFIGURAZIONE MDR --}}
    <div style="border: 1px solid #ff6f3f; padding: 15px; border-radius: 12px; background: #fffaf8; margin-bottom: 20px;">
        <div style="font-size: 8pt; color: #ff6f3f; font-weight: 900; text-transform: uppercase; margin-bottom: 10px;">Configurazione Dispositivi Medici su Misura (Allegato XIII)</div>
        <table style="width: 100%; border:none; margin:0;">
            @if(empty($devices))
                <tr><td style="border:none; padding:5px 0;"><span style="font-size: 11pt; font-weight: bold;">{{ $order->final_device_name ?? $order->device_type }}</span><span class="mdr-tag">CLASSE {{ $order->risk_class ?? 'IIa' }}</span></td></tr>
            @else
                @foreach($devices as $dev)
                <tr>
                    <td style="border:none; padding:5px 0; border-bottom: 1px solid #eee;">
                        <div style="font-size: 11pt; font-weight: bold;">{{ $dev['name'] }}</div>
                        <div style="font-size: 8px; color: #6b7280; text-transform: uppercase;">
                            Classe Rischio: {{ $dev['risk_class'] }} | MDR Rule: {{ $dev['mdr_rule'] }} | Durata: {{ $dev['duration_use'] ?? 'Lungo Termine' }}
                        </div>
                    </td>
                </tr>
                @endforeach
            @endif
        </table>
    </div>

    {{-- SEZIONE 1: CAD --}}
    <div class="section-title">1. Progettazione Digitale (CAD)</div>
    <table>
        <tr><th>Operatore CAD</th><td>{{ $production->cad_operator ?? 'N/D' }}</td></tr>
        <tr><th>Libreria Denti (Ant)</th><td>{{ $teethLib['ant'] ?? '-' }}</td></tr>
        <tr><th>Libreria Denti (Post)</th><td>{{ $teethLib['post'] ?? '-' }}</td></tr>
        <tr><th>Libreria Impianti</th><td>{{ $production->cad_implant_library ?? '-' }}</td></tr>
        <tr><th>Note Progettazione</th><td>{{ $production->cad_notes ?? 'Nessuna specifica aggiuntiva' }}</td></tr>
    </table>

    {{-- SEZIONE 2: CAM --}}
    <div class="section-title">2. Fabbricazione CAM</div>
    <div style="font-size: 9pt; margin-top: 5px; margin-bottom: 5px;">
        <strong>Operatore CAM:</strong> {{ $camData['operator'] ?? 'N/D' }}
    </div>
    <table>
        <thead><tr><th>Materiale Grezzo</th><th>Lotto / Batch</th><th>Macchina / Unità</th></tr></thead>
        <tbody>
            @foreach(($camData['lots'] ?? []) as $lot)
            <tr>
                <td>{{ $lot['material_name'] ?? '-' }}</td>
                <td><strong>{{ $lot['material_id'] ?? '-' }}</strong></td>
                <td>{{ $lot['machine'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SEZIONE 3: LAB --}}
    <div class="section-title">3. Finalizzazione LAB & Finitura</div>
    <table>
        <tr><th>Operatore LAB</th><td>{{ $production->lab_operator ?? 'N/D' }}</td></tr>
        <tr><th>Colore Finale</th><td><strong>{{ $production->lab_color ?? 'N/D' }}</strong></td></tr>
    </table>
    
    {{-- Tabella Materiali LAB (Aggiunta) --}}
    @if(!empty($labMaterials))
    <table style="margin-top: 5px;">
        <thead><tr><th style="background-color: #fff7ed; color: #c2410c;">Materiale Finitura / Componente</th><th style="background-color: #fff7ed; color: #c2410c;">Lotto</th></tr></thead>
        <tbody>
            @foreach($labMaterials as $mat)
            <tr>
                <td>{{ $mat['material_name'] ?? '' }}</td>
                <td>{{ $mat['material_id'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- SEZIONE 4: QC --}}
    <div class="section-title">4. Controllo Qualità & Validazione (QC)</div>
    <table>
        <tr>
            <th>Checklist Esito</th>
            <td>
                @php
                    $checks = [];
                    if(($qcChecklist['conformity'] ?? '') == 'OK') $checks[] = "Prescrizione: CONFORME";
                    if(($qcChecklist['dimensional'] ?? '') == 'OK') $checks[] = "Dimensioni: CONFORME";
                    if(($qcChecklist['functional'] ?? '') == 'OK') $checks[] = "Occlusione: CONFORME";
                @endphp
                {{ implode(' | ', $checks) ?: 'Nessun check registrato' }}
            </td>
        </tr>
        <tr><th>Responsabile Rilascio</th><td><strong>{{ $production->qc_verifier ?? 'N/D' }}</strong></td></tr>
    </table>

    {{-- FIRME E TIMESTAMPS --}}
    <div class="signature-box">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td width="60%" style="vertical-align: top;">
                    <span class="ts-title">Chiusura Fascicolo Tecnico</span>
                    <div style="font-size: 10px; line-height: 1.5; color: #333;">
                        Documentazione Tecnica approvata elettronicamente.<br>
                        Responsabile QC: <strong>{{ $production->qc_verifier }}</strong><br>
                        Protocollo: <strong>MDR 2017/745 - Tracciabilità Completa</strong>
                    </div>
                </td>
                <td width="40%" style="text-align: right; vertical-align: top;">
                    <span class="ts-title">Marca Temporale di Chiusura</span>
                    <div class="ts-data">{{ $order->production->released_at ? $order->production->released_at->format('d/m/Y - H:i:s') : 'NON_CONSOLIDATO' }}</div>
                    <div class="hash-info">
                        FINGERPRINT SHA256:<br>
                        {{ $order->production_hash ?? 'NON_VALIDATED' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">Documentazione Tecnica MDR ai sensi del Regolamento (UE) 2017/745 • {{ config('app.name') }}</div>
</body>
</html>