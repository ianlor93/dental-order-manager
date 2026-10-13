<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $docTitle }}</title>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <style>
        @page { margin: 0; }
        body { 
            font-family: 'Ubuntu', 'Helvetica', sans-serif; 
            color: #1a1a1a; 
            line-height: 1.6; 
            margin: 0; 
            padding: 0; 
        }
        .page { padding: 45px; }
        
        /* Header: Enamel (Fisso) vs Medico (Dinamico) */
        .header-table { 
            width: 100%; 
            border-bottom: 4px solid #ff6f3f; 
            margin-bottom: 35px; 
            padding-bottom: 20px; 
            border-collapse: collapse; 
        }
        .col-label { font-size: 9px; font-weight: bold; color: #ff6f3f; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 1px; }
        .name { font-size: 14px; font-weight: bold; margin-bottom: 4px; display: block; color: #000; }
        .details { font-size: 10.5px; color: #444; line-height: 1.4; }
        
        /* Titoli */
        h1 { font-size: 20px; text-transform: uppercase; text-align: center; margin: 30px 0 5px 0; color: #000; letter-spacing: -0.5px; }
        .mdr-tag { font-size: 10px; text-align: center; color: #ff6f3f; font-weight: bold; margin-bottom: 40px; text-transform: uppercase; letter-spacing: 2px; }
        
        /* Contenuto iniettato dai tuoi .txt */
        .legal-content { 
            font-size: 11px; 
            text-align: justify; 
            color: #333; 
            white-space: pre-wrap; /* Mantiene gli a capo dei tuoi file .txt */
        }
        
        /* Box di Validazione */
        .signature-box { 
            margin-top: 50px; 
            border: 2px solid #1a1a1a; 
            padding: 25px; 
            background-color: #fafafa; 
        }
        .ts-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #ff6f3f; margin-bottom: 12px; display: block; }
        .ts-data { font-family: monospace; font-size: 13px; font-weight: bold; color: #000; }
        
        .watermark { 
            position: fixed; top: 40%; left: 10%; transform: rotate(-45deg); 
            font-size: 80px; color: rgba(255, 111, 63, 0.07); font-weight: bold; 
            z-index: -1000; text-transform: uppercase; 
        }
    </style>
</head>
<body>
    @if(isset($isCopy) && $isCopy)
        <div class="watermark">Copia Firmata</div>
    @endif

    <div class="page">
        <table class="header-table">
            <tr>
                <td width="55%" style="vertical-align: top;">
                    <div class="col-label">Fabbricante Dispositivi Medici (Reg. UE 2017/745)</div>
                    <span class="name">{{ config('lab.manufacturer.name') }}</span>
                    <div class="details">
                        {{ config('lab.manufacturer.address') }}<br>
                        {{ config('lab.manufacturer.zip') }} {{ config('lab.manufacturer.city') }}<br>
                        <strong>P.IVA: {{ config('lab.manufacturer.vat') }}</strong><br>
                        Ministero della Salute: {{ config('lab.manufacturer.mdr_registration') }}
                    </div>
                </td>

                <td width="45%" style="vertical-align: top; padding-left: 30px; border-left: 1px solid #eee;">
                    <div class="col-label">Committente</div>
                    <span class="name">{{ $studio->name }}</span>
                    <div class="details">
                        Sede Legale: {{ $studio->address }}<br>
                        {{ $studio->zip_code }} {{ $studio->city }} ({{ $studio->albo_provincia }})<br>
                        P.IVA/CF: {{ $studio->vat_number }}<br>
                        Iscrizione Albo: n. {{ $studio->dentist_license }}
                    </div>
                </td>
            </tr>
        </table>

        <h1>{{ $docTitle }}</h1>
        <div class="mdr-tag">Protocollo Data Bridge - Validazione Professionale</div>

        <div class="legal-content">
            {!! nl2br(e($legalContent)) !!}
        </div>

        <div class="signature-box">
            <table width="100%" style="border-collapse: collapse;">
                <tr>
                    <td width="60%" style="vertical-align: top;">
                        <span class="ts-title">Certificazione Digitale</span>
                        <div style="font-size: 10px; line-height: 1.5; color: #333;">
                            Documento accettato elettronicamente tramite {{ config('app.name') }}.<br>
                            Sottoscritto da: <strong>{{ $user->displayname }}</strong><br>
                            Per conto di: <strong>{{ $studio->name }}</strong><br>
                            Foro Competente: <strong>{{ config('lab.manufacturer.court') }}</strong>
                        </div>
                    </td>
                    <td width="40%" style="text-align: right; vertical-align: top;">
                        <span class="ts-title">Marca Temporale di Sistema</span>
                        <div class="ts-data">{{ $timestamp ? $timestamp->format('d/m/Y - H:i:s') : 'N/D' }}</div>
                        <div style="font-size: 8px; color: #888; margin-top: 15px; font-family: monospace; text-transform: uppercase;">
                            Fingerprint: {{ hash('sha256', $studio->id . $timestamp . $docTitle) }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>