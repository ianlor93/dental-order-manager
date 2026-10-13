<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Richiesta Lavorazione - {{ $order->code }}</title>
    <style>
        @page { margin: 30px; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            color: #1a1a1a; 
            line-height: 1.3; 
            font-size: 10px; 
            margin: 0; 
            padding: 0; 
        }
        
        .header-table { width: 100%; border-bottom: 3px solid #ff6f3f; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { font-size: 18px; font-weight: 700; color: #111827; } 
        
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 15px; text-transform: uppercase; color: #111827; font-weight: 700; }
        .doc-title p { margin: 0; font-size: 8px; color: #ff6f3f; font-weight: 700; text-transform: uppercase; }

        .info-section { width: 100%; margin-bottom: 15px; border-collapse: collapse; table-layout: fixed; }
        .info-box { border: 1px solid #e5e7eb; padding: 10px; background: #f9fafb; min-height: 110px; vertical-align: top; }
        .label { font-size: 7px; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 3px; }
        .value { font-size: 10px; font-weight: 700; color: #111827; margin-bottom: 4px; }
        .sub-value { font-size: 9px; color: #4b5563; font-weight: 400; line-height: 1.2; }

        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th { background: #f3f4f6; text-align: left; padding: 6px 8px; font-size: 8px; text-transform: uppercase; border-bottom: 2px solid #e5e7eb; font-weight: 700; }
        .data-table td { padding: 8px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }

        .notes-section { padding: 10px; border: 1px solid #e5e7eb; background: #fff; font-style: italic; color: #4b5563; min-height: 70px; }

        .footer { position: fixed; bottom: -10px; left: 0; right: 0; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 7px; color: #9ca3af; text-align: center; }
        
        .security-row { 
            margin-bottom: 8px; 
            border-bottom: 1px solid #f3f4f6; 
            padding-bottom: 8px; 
        }
        .file-name { font-size: 9px; font-weight: 700; color: #374151; margin-bottom: 2px; }
        .tech-data { font-family: 'Courier', monospace; font-size: 7px; color: #6b7280; width: 100%; }
        .status-ok { color: #059669; font-weight: 700; }
        .status-err { color: #dc2626; font-weight: 700; }
    </style>
</head>
<body>

    @php
        // LOGICA MULTI-PRESCRITTORE + LAB
        $isLab = !empty($order->studio->itca_code);
        
        // 1. Il nome del responsabile/medico viene PREFERIBILMENTE dall'indirizzo specifico (Sede Operativa)
        // Se non c'è nell'indirizzo, usa quello dello studio Master.
        $responsabileName = $order->address?->dentist_name ?? $order->studio->dentist_name;

        // 2. Dati della sede operativa specifica (Indirizzo di spedizione/lavoro)
        $sedeLabel = $order->address?->label ?? 'Sede Principale';
        $sedeIndirizzo = $order->address?->address ?? $order->studio->address;
        $sedeCitta = ($order->address?->zip_code ?? $order->studio->zip_code) . ' ' . ($order->address?->city ?? $order->studio->city);

        // 3. Dati Qualifica (Albo o ITCA)
        // Se è medico, prendiamo l'Albo specifico della sede (se diverso dal master)
        $alboRef = $order->address?->dentist_license ?? $order->studio->dentist_license;
        $provRef = $order->address?->albo_provincia ?? $order->studio->albo_provincia;
        
        // Se è laboratorio, l'ITCA è unico per l'azienda (di solito), quindi prendiamo dallo Studio
        $itcaRef = $order->studio->itca_code; 
    @endphp

    <table class="header-table">
        <tr>
            <td class="logo">{{ config('app.name') }}</td>
            <td class="doc-title">
                <h1>{{ $isLab ? 'Commissione Tecnica' : 'Richiesta Lavorazione' }}</h1>
                <p>Protocollo MDR 2017/745 - Allegato XIII</p>
            </td>
        </tr>
    </table>

    <table class="info-section">
        <tr>
            <td width="28%" style="padding-right: 5px;">
                <div class="info-box">
                    <div class="label">Dati Pratica</div>
                    <div class="value" style="font-size: 13px; color: #ff6f3f;">{{ $order->code }}</div>
                    <div style="margin-top: 10px;" class="label">Ricezione</div>
                    <div class="value">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </td>
            
            <td width="36%" style="padding: 0 5px;">
                <div class="info-box">
                    {{-- Titolo dinamico base al ruolo --}}
                    <div class="label">{{ $isLab ? 'Laboratorio Richiedente' : 'Clinico Prescrittore' }}</div>
                    
                    {{-- Nome del responsabile specifico della sede --}}
                    <div class="value">{{ $isLab ? 'Sig.' : 'Dott.' }} {{ $responsabileName }}</div>
                    
                    <div class="sub-value">
                        <strong>{{ $order->studio->name }}</strong><br>
                        <span style="font-size: 8px; color: #666;">{{ $sedeLabel }}</span><br>
                        {{ $sedeIndirizzo }}<br>
                        {{ $sedeCitta }}<br>
                        
                        <div style="margin-top: 4px; border-top: 1px dashed #ccc; padding-top: 2px;">
                            @if($isLab)
                                ITCA: <strong style="color:#6b21a8">{{ $itcaRef }}</strong>
                            @else
                                Albo: {{ $alboRef }} ({{ strtoupper($provRef) }})
                            @endif
                        </div>
                    </div>
                </div>
            </td>

            <td width="36%" style="padding-left: 5px;">
                <div class="info-box" style="border-left: 3px solid #ff6f3f;">
                    <div class="label">Fabbricante</div>
                    <div class="value">{{ config('lab.manufacturer.name') }}</div>
                    <div class="sub-value">
                        {{ config('lab.manufacturer.address') }}<br>
                        {{ config('lab.manufacturer.zip') }} {{ config('lab.manufacturer.city') }}<br>
                        <strong>{{ config('lab.manufacturer.mdr_registration') }}</strong>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="50%">Riferimento {{ $isLab ? 'Lavoro / Paziente' : 'Paziente' }}</th>
                <th width="50%">Ipotesi Dispositivo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="value" style="font-size: 12px;">{{ strtoupper($order->patient_ref) }}</td>
                <td class="value">{{ str_replace('_', ' ', strtoupper($order->device_type)) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="label">Note Preliminari / Quesito Tecnico</div>
    <div class="notes-section">{{ $order->notes ?: 'Nessuna specifica tecnica aggiuntiva fornita.' }}</div>

    <div style="margin-top: 25px;">
        <div class="label">Audit Asset Digitali & Security Scan (MDR Integrity)</div>
        <div style="margin-top: 10px;">
            @foreach($order->files as $file)
                <div class="security-row">
                    <div class="file-name">{{ $file->original_name }}</div>
                    <table class="tech-data">
                        <tr>
                            <td width="45%">
                                @php 
                                    $status = strtolower(trim($file->scan_status)); 
                                @endphp

                                @if($status === 'clean')
                                    <span class="status-ok">[SHIELD-OK]</span> ClamAV Scan: zINSTREAM PASS
                                @elseif($status === 'infected')
                                    <span class="status-err">[SHIELD-ALARM]</span> THREAT DETECTED / BLOCKED
                                @else
                                    <span style="color: #9ca3af;">[SHIELD-PENDING]</span> Audit in progress
                                @endif
                                <span style="margin-left: 5px; color: #9ca3af;">({{ $file->scanned_at ? $file->scanned_at->format('d/m H:i') : '---' }})</span>
                            </td>
                            <td width="55%" style="text-align: right;">
                                SHA256: {{ $file->hash_sha256 }}
                            </td>
                        </tr>
                    </table>
                </div>
            @endforeach
        </div>
    </div>

    <div class="footer">
        Documento generato dal sistema di gestione MDR. Integrità degli asset verificata tramite firma digitale SHA256.<br>
        <strong>{{ config('lab.manufacturer.name') }} - Conformità Regolamento (UE) 2017/745 (MDR)</strong>
    </div>
</body>
</html>