<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <style>
        /* Forza il formato 10x15 ed elimina ogni margine di pagina */
        @page { size: 10cm 15cm; margin: 0; }
        
        body { 
            margin: 0; 
            padding: 0; 
            width: 10cm; 
            height: 15cm; 
            font-family: 'Helvetica', sans-serif; 
            background-color: #ffffff;
            overflow: hidden; /* Impedisce tassativamente la creazione di nuove pagine */
        }

        /* Il Box: usiamo misure in PX leggermente inferiori al limite per sicurezza totale */
        .main-container {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 330px; /* Circa 8.8cm */
            height: 520px; /* Circa 13.8cm */
            border: 2px solid #ff6f3f;
            padding: 15px;
            box-sizing: border-box;
        }

        /* Header */
        .header { border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 15px; }
        .brand { font-size: 10pt; font-weight: bold; }
        
        .badge { 
            background: #ff6f3f; 
            color: white; 
            text-align: center; 
            padding: 4px; 
            font-weight: bold; 
            font-size: 9pt; 
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        /* Testi */
        .label-sm { font-size: 7pt; color: #ff6f3f; font-weight: bold; text-transform: uppercase; margin-top: 8px; }
        .value { font-size: 11pt; font-weight: bold; margin-bottom: 8px; display: block; }
        .value-lg { font-size: 18pt; font-weight: bold; margin-bottom: 8px; display: block; }

        .device-item { font-size: 8.5pt; line-height: 1.2; margin-bottom: 2px; }
        
        /* Footer ancorato al fondo del container */
        .footer { 
            position: absolute; 
            bottom: 15px; 
            left: 15px; 
            right: 15px; 
        }

        .legal { font-size: 7pt; font-style: italic; line-height: 1.1; margin-bottom: 8px; border-top: 1px solid #eee; padding-top: 5px; }

        .symbol-md { 
            float: right; 
            border: 2px solid #000; 
            padding: 2px 5px; 
            font-weight: bold; 
            font-size: 12pt; 
        }

        .hash-code { font-size: 5pt; color: #aaa; font-family: monospace; margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <span class="brand">{{ config('lab.manufacturer.name') }}</span><br>
            <span style="font-size: 7pt;">Conformità MDR UE 2017/745</span>
        </div>

        <div class="badge">Dispositivo Medico su Misura</div>

        <div class="label-sm">ID PRATICA</div>
        <div class="value-lg">{{ $order->code }}</div>

        <div class="label-sm">PAZIENTE</div>
        <div class="value">{{ strtoupper($order->patient_ref) }}</div>

        <div class="label-sm">DISPOSITIVI</div>
        <div style="height: 140px; overflow: hidden;">
            @php $devices = $order->manufactured_devices ?? []; @endphp
            @if(empty($devices))
                <div class="device-item">• {{ strtoupper($order->final_device_name ?? $order->device_type) }}</div>
            @else
                @foreach($devices as $dev)
                    <div class="device-item">• {{ strtoupper($dev['name']) }}</div>
                @endforeach
            @endif
        </div>

        <div class="footer">
            <div class="legal">
                Destinato esclusivamente all'utilizzatore indicato in prescrizione. 
                Classe: {{ $order->risk_class ?? 'IIa' }}.
            </div>
            
            <div class="hash-code">AUTH: {{ $order->production_hash ?? 'N/D' }}</div>

            <div style="float: left; font-size: 8pt;">
                Data emissione:<br><strong>{{ date('d/m/Y') }}</strong>
            </div>

            <div class="symbol-md">MD</div>
        </div>
    </div>
</body>
</html>