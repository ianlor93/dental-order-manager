<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Distinta Riepilogativa - {{ $studio->name }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #1a1a1a; line-height: 1.4; }
        
        .section-title { font-weight: bold; text-transform: uppercase; font-size: 8.5pt; color: #ff6f3f; border-bottom: 1px solid #eee; margin-top: 15px; margin-bottom: 6px; }
        .data-box { background: #fdfdfd; padding: 15px; border: 1px solid #f0f0f0; border-radius: 12px; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8pt; color: #9ca3af; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        
        /* Tabella con Raggruppamento */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .items-table th { text-align: left; font-size: 7.5pt; text-transform: uppercase; color: #ff6f3f; border-bottom: 1px solid #ff6f3f; padding: 8px 4px; }
        .items-table td { padding: 10px 4px; border-bottom: 1px solid #f0f0f0; vertical-align: top; font-size: 8.5pt; }
        
        /* Riga Intestazione DDT (Raggruppamento) */
        .ddt-group-header { background-color: #fafafa; font-weight: bold; font-size: 8pt; color: #555; }
        .ddt-group-header td { border-bottom: 1px solid #eee; padding: 6px 4px; color: #ff6f3f; text-transform: uppercase; font-size: 7.5pt; }

        .code-cell { font-family: 'Courier', monospace; font-weight: bold; }
        .patient-cell { font-weight: bold; text-transform: uppercase; }
        .doctor-sub { font-size: 7.5pt; color: #777; margin-top: 2px; }

        ul.mdr-list { margin: 0; padding-left: 12px; list-style-type: disc; color: #333; }
        ul.mdr-list li { margin-bottom: 1px; font-size: 8pt; }

        /* Box Riepilogo Finale Soft UI */
        .summary-stats-box { 
            margin-top: 30px; 
            background: #fffaf8; 
            border: 2px solid #ff6f3f; 
            border-radius: 15px; 
            padding: 20px; 
        }
        .stat-item { font-size: 9pt; margin-bottom: 4px; }
        .stat-label { text-transform: uppercase; font-weight: bold; color: #666; font-size: 7.5pt; }
        .total-highlight { font-size: 14pt; font-weight: 900; color: #1a1a1a; text-transform: uppercase; }
    </style>
</head>
<body>
    @include('pdfs.partials.branding_header')

    <div style="text-align: right; margin-bottom: 20px;">
        <h1 style="margin:0; font-size: 18pt; text-transform: uppercase;">Distinta riepilogativa mensile</h1>
        <div style="font-size: 9pt; color: #ff6f3f; font-weight: bold;">Elenco Consegne</div>
    </div>

    <div class="section-title">Destinatario</div>
    <div class="data-box">
        <div style="font-size: 11pt; font-weight: bold; text-transform: uppercase; margin-bottom: 4px;">{{ $studio->name }}</div>
        <div style="color: #444;">
            {{ $studio->address }}<br>
            {{ $studio->zip_code }} {{ $studio->city }}<br>
            <strong>P.IVA:</strong> {{ $studio->vat_number }}
        </div>
    </div>

    <div class="section-title">Elenco Analitico Spedizioni</div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="15%">Invio</th>
                <th width="15%">Codice</th>
                <th width="35%">Paziente / Clinico</th>
                <th width="35%">Dispositivo</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $mano = 0; 
                $corriere = 0; 
            @endphp
            @foreach($notes as $note)
                @php 
                    $isMano = str_contains(strtolower($note->courier), 'diretta') || str_contains(strtolower($note->courier), 'mano');
                    if($isMano) { $mano++; } else { $corriere++; }
                @endphp
                
                {{-- Riga di testata gruppo DDT --}}
                <tr class="ddt-group-header">
                    <td colspan="4">
                        <strong>DDT #{{ $note->id }}</strong> — Spedito via {{ $note->courier }} il {{ $note->delivery_date->format('d/m/Y') }}
                    </td>
                </tr>

                @foreach($note->orders as $order)
                <tr>
                    <td style="font-size: 8pt; color: #777;">{{ $order->created_at->format('d/m/Y') }}</td>
                    <td class="code-cell">{{ $order->code }}</td>
                    <td>
                        <div class="patient-cell">{{ $order->patient_ref }}</div>
                        <div class="doctor-sub">Dott. {{ $order->address?->dentist_name ?? $order->studio?->dentist_name }}</div>
                    </td>
                    <td>
                        @if(!empty($order->manufactured_devices) && is_array($order->manufactured_devices))
                            <ul class="mdr-list">
                                @foreach($order->manufactured_devices as $dev)
                                    <li>{{ is_array($dev) ? ($dev['name'] ?? 'N/D') : $dev }}</li>
                                @endforeach
                            </ul>
                        @else
                            {{ str_replace('_', ' ', $order->device_type) }}
                        @endif
                    </td>
                </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="summary-stats-box">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td width="60%" valign="top">
                    <div class="stat-item"><span class="stat-label">Spedizioni con corriere:</span> <strong>{{ $corriere }}</strong></div>
                    <div class="stat-item"><span class="stat-label">Consegne a mano:</span> <strong>{{ $mano }}</strong></div>
                    <div style="margin-top: 10px; color: #777; font-size: 8pt; font-style: italic;">
                        Report generato il {{ $generatedAt->format('d/m/Y H:i') }}
                    </div>
                </td>
                <td width="40%" style="text-align: right; vertical-align: bottom;">
                    <div class="stat-label">Totale Consegne Effettuate</div>
                    <div class="total-highlight">{{ $notes->count() }} Consegne</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ config('app.name') }} - Sistema Integrato MDR & Logistica
    </div>
</body>
</html>