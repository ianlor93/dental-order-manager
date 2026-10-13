<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>{{ !empty($order->studio->itca_code) ? 'Prescrizione Semilavorato' : 'Prescrizione DMsM' }} - {{ $order->code }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #1a1a1a; line-height: 1.4; }
        .section-title { font-weight: bold; text-transform: uppercase; font-size: 8.5pt; color: #ff6f3f; border-bottom: 1px solid #eee; margin-top: 15px; margin-bottom: 6px; }
        .data-box { background: #fdfdfd; padding: 12px; border: 1px solid #f0f0f0; border-radius: 8px; }
        .design-box { border: 1px solid #e5e7eb; padding: 15px; font-style: italic; background-color: #fcfcfc; min-height: 100px; font-family: 'Courier', monospace; font-size: 10pt; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8pt; color: #9ca3af; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        .signature-box { margin-top: 40px; border: 2px solid #1a1a1a; padding: 20px; background-color: #fafafa; }
        .ts-title { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #ff6f3f; margin-bottom: 10px; display: block; }
        .ts-data { font-family: monospace; font-size: 12px; font-weight: bold; color: #000; }
        .hash-info { font-size: 7pt; color: #888; font-family: 'Courier', monospace; word-wrap: break-word; margin-top: 10px; text-transform: uppercase; }
    </style>
</head>
<body>
    @include('pdfs.partials.branding_header')

    @php
        // LOGICA MULTI-PRESCRITTORE + LAB
        $studio = $order->studio;
        $itcaCode = $studio->itca_code ?? null;
        $isLab = !empty($itcaCode);
        
        // --- RECUPERO DATI RESPONSABILE ---
        // Se c'è un address specifico, usiamo il referente di quella sede, altrimenti quello dello studio
        $responsabileName = $order->address->dentist_name ?? ($studio->dentist_name ?? 'N/D');
        
        // --- RECUPERO DATI SEDE (LOGICA MULTI-SEDE) ---
        // Se l'ordine ha un address_id, è una sede secondaria. Altrimenti è la sede principale.
        $sede = $order->address ?? $studio;
        
        // Etichetta Sede (es. "Sede Milano")
        $sedeLabel = $order->address->label ?? ''; 

        // Indirizzo Completo (Via + CAP + Città + Prov)
        $indirizzoCompleto = $sede->address . ' - ' . $sede->zip_code . ' ' . $sede->city . ' (' . ($sede->province ?? '-') . ')';

        // --- DATI QUALIFICA ---
        $alboRef = $order->address->dentist_license ?? ($studio->dentist_license ?? 'N/D');
        $provRef = $order->address->albo_provincia ?? ($studio->albo_provincia ?? '-');
        
        // Titolo Documento
        $titoloDoc = $isLab ? 'Prescrizione Semilavorato' : 'Prescrizione DMsM';
    @endphp

    <div style="text-align: right; margin-bottom: 20px;">
        <h1 style="margin:0; font-size: 18pt; text-transform: uppercase;">{{ $titoloDoc }}</h1>
        <div style="font-size: 9pt; color: #ff6f3f; font-weight: bold;">ID PRATICA: {{ $order->code }}</div>
    </div>

    <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td width="50%" valign="top" style="padding-right: 10px;">
                <div class="section-title">{{ $isLab ? 'Responsabile Tecnico' : 'Clinico Prescrittore' }}</div>
                <div class="data-box">
                    <strong>{{ $isLab ? 'Sig.' : 'Dott.' }} {{ $responsabileName }}</strong><br>
                    Qualifica: {{ $isLab ? 'Odontotecnico / Resp. Lab' : ($order->prescriber_qualification ?? 'Odontoiatra') }}<br>
                    
                    @if($isLab)
                        ITCA: <strong style="color:#6b21a8">{{ $itcaCode }}</strong>
                    @else
                        Albo: {{ $alboRef }} ({{ $provRef }})
                    @endif
                </div>
            </td>
            <td width="50%" valign="top" style="padding-left: 10px;">
                <div class="section-title">Studio e Destinazione</div>
                <div class="data-box">
                    <strong>{{ $studio->name }}</strong> @if($sedeLabel) <span style="font-size:8pt;">({{ $sedeLabel }})</span> @endif<br>
                    {{ $indirizzoCompleto }}<br>
                    <span style="font-size: 8pt; color: #666;">Data Validazione: {{ $order->validated_at ? $order->validated_at->format('d/m/Y') : 'PENDING' }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Dati {{ $isLab ? 'Lavoro' : 'Paziente' }} e Dispositivo</div>
    <div class="data-box">
        Riferimento: <strong style="color:#ff6f3f">{{ strtoupper($order->patient_ref) }}</strong><br>
        Tipologia Manufatto: <strong>{{ str_replace('_', ' ', strtoupper($order->device_type)) }}</strong>
    </div>

    <div class="section-title">Caratteristiche Specifiche di Progettazione</div>
    <div class="design-box">
        {!! nl2br(e($order->design_features)) !!}
    </div>

    <div style="margin-top: 25px; font-size: 9pt; text-align: justify; padding: 10px; border: 1px solid #eee; border-radius: 8px;">
        @if($isLab)
            <strong>Dichiarazione:</strong> Il richiedente commissiona la fabbricazione del dispositivo semilavorato/finito secondo le specifiche tecniche fornite, assumendosene la responsabilità progettuale funzionale.
        @else
            <strong>Dichiarazione (All. XIII MDR):</strong> Il clinico dichiara che il dispositivo è destinato esclusivamente al paziente summenzionato e risponde alle sue esigenze individuali sotto la propria responsabilità.
        @endif
    </div>

    <div class="signature-box">
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td width="60%" style="vertical-align: top;">
                    <span class="ts-title">Certificazione Validazione</span>
                    <div style="font-size: 10px; line-height: 1.5; color: #333;">
                        Documento approvato elettronicamente dal {{ $isLab ? 'Responsabile' : 'Medico Prescrittore' }}.<br>
                        Sottoscritto da: <strong>{{ $isLab ? 'Sig.' : 'Dott.' }} {{ $responsabileName }}</strong><br>
                        Responsabilità: <strong>Progettazione e Prescrizione (MDR All. XIII)</strong>
                    </div>
                </td>
                <td width="40%" style="text-align: right; vertical-align: top;">
                    <span class="ts-title">Marca Temporale</span>
                    <div class="ts-data">{{ $order->validated_at ? $order->validated_at->format('d/m/Y - H:i:s') : 'N/D' }}</div>
                    <div class="hash-info">
                        FINGERPRINT SHA256:<br>
                        {{ $order->validation_hash ?? 'NON_VALIDATED' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">Documento conforme al Regolamento (UE) 2017/745 (MDR)</div>
</body>
</html>