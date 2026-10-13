<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Informativa Paziente - {{ $order->code }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #333; line-height: 1.5; margin: 0; padding: 0; }
        .title { font-size: 16pt; font-weight: bold; color: #ff6f3f; text-transform: uppercase; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .section-title { font-weight: bold; text-transform: uppercase; font-size: 9pt; color: #ff6f3f; margin-top: 20px; margin-bottom: 5px; }
        .highlight-box { background: #fffaf8; padding: 15px; border-radius: 12px; border: 1px solid #ff6f3f22; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8pt; color: #9ca3af; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
        .device-list { margin: 0; padding-left: 15px; list-style-type: square; }
        .device-item { font-weight: bold; text-transform: uppercase; font-size: 10.5pt; }
        .device-meta { font-size: 8pt; color: #6b7280; font-weight: normal; }
    </style>
</head>
<body>
    @include('pdfs.partials.branding_header')

    <div class="title">Nota Informativa per il Paziente</div>

    <p>Gentile Paziente, il dispositivo odontoiatrico identificato con codice <strong style="color:#ff6f3f">{{ $order->code }}</strong> è stato realizzato su misura esclusivamente per Lei, integrando tecnologie digitali e materiali certificati.</p>

    <div class="highlight-box">
        <div class="section-title" style="margin-top:0">Dati del Fabbricante</div>
        <p style="margin:0; font-size: 10.5pt;"><strong>Il dispositivo è stato prodotto dal Laboratorio indicato nell'intestazione.</strong><br>
        Identificato ai fini MDR come Dispositivo Medico su Misura.</p>
    </div>

    <div class="section-title">Descrizione e Destinazione</div>
    <div style="margin-top:0">
        @php $devices = $order->manufactured_devices ?? []; @endphp

        @if(empty($devices))
            <p>Tipologia: <strong class="device-item">{{ str_replace('_', ' ', strtoupper($order->final_device_name ?? $order->device_type)) }}</strong></p>
        @else
            <p>Componenti del Suo dispositivo su misura:</p>
            <ul class="device-list">
                @foreach($devices as $dev)
                    <li class="device-item">
                        {{ $dev['name'] }}
                        <span class="device-meta"> — Uso previsto: {{ $dev['duration_use'] ?? 'Lungo Termine' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <p style="margin-top: 10px;">Questo manufatto è destinato alla riabilitazione funzionale ed estetica secondo le specifiche e la pianificazione clinica del Suo odontoiatra.</p>
    </div>

    <div class="section-title">Istruzioni e Manutenzione</div>
    <p style="margin-top: 0;">Mantenere una rigorosa igiene orale. Pulire il dispositivo quotidianamente con spazzolini a setole morbide e detergenti neutri non abrasivi. In caso di rotture, fastidi improvvisi o alterazioni morfologiche, consulti immediatamente il Suo medico curante.</p>

    <div class="section-title">Tracciabilità e Garanzia</div>
    @php
        $retentionYears = config('legal.retention.implant', 15);
        if (!empty($order->final_device_slug)) {
            $catalogEntry = \App\Models\MdrCatalog::where('slug', $order->final_device_slug)->first();
            if ($catalogEntry) {
                $retentionYears = $catalogEntry->is_implantable
                    ? config('legal.retention.implant', 15)
                    : config('legal.retention.standard', 10);
            }
        }
    @endphp
    <p style="margin-top: 0;">I dati tecnici del Suo lavoro sono archiviati nei nostri sistemi protetti per un periodo di <strong style="color:#ff6f3f">{{ $retentionYears }} anni</strong> (MDR 2017/745, Art. 10 par. 8). Questa rintracciabilità totale garantisce che ogni materiale utilizzato sia biocompatibile e certificato, e permette, in caso di necessità clinica, una riproduzione fedele basata sui file digitali originali conservati in archivio.</p>

    <div class="section-title">Informativa Privacy — Art. 13 GDPR 2016/679</div>
    <p style="margin-top: 0; font-size: 9pt;">
        I Suoi dati personali (identificati tramite codice pseudonimo assegnato dal Suo odontoiatra) sono trattati per la
        fabbricazione del presente dispositivo medico su misura. Base giuridica: Art. 9(2)(h) + Art. 9(2)(i) GDPR +
        Art. 2-septies Dlgs 196/2003. Non è richiesto il Suo consenso.<br><br>
        In qualità di interessato, Lei ha diritto di accesso, rettifica, cancellazione (nei limiti degli obblighi MDR),
        portabilità, restrizione e opposizione al trattamento dei propri dati (Artt. 15-21 GDPR).
        Per esercitare tali diritti rivolgersi in prima istanza al Suo odontoiatra (Titolare del Trattamento per i Suoi dati).
    </p>
    <table style="width:100%; font-size: 9pt; border-collapse: collapse; margin-top: 5px;">
        <tr>
            <td style="width:33%; padding-right: 10px; vertical-align: top;">
                <strong style="color:#ff6f3f; font-size: 8pt; text-transform: uppercase;">Titolare del Trattamento</strong><br>
                <span style="font-size: 8.5pt;">
                    {{ $clinician }}<br>
                    {{ $studio_name }}<br>
                    {{ $studio_address }}
                </span>
            </td>
            <td style="width:34%; padding-right: 10px; vertical-align: top;">
                <strong style="color:#ff6f3f; font-size: 8pt; text-transform: uppercase;">Responsabile del Trattamento ex Art. 28 GDPR</strong><br>
                <span style="font-size: 8.5pt;">
                    {{ config('lab.manufacturer.name') }}<br>
                    @if(config('lab.manufacturer.address')){{ config('lab.manufacturer.address') }}, {{ config('lab.manufacturer.zip') }} {{ config('lab.manufacturer.city') }}<br>@endif
                    {{ config('lab.manufacturer.email') }}
                </span>
            </td>
            <td style="width:33%; vertical-align: top;">
                <strong style="color:#ff6f3f; font-size: 8pt; text-transform: uppercase;">
                    @if(config('lab.dpo.name'))Resp. Protezione Dati (DPO)@else Contatto Privacy @endif
                </strong><br>
                <span style="font-size: 8.5pt;">
                    @if(config('lab.dpo.name'))
                        {{ config('lab.dpo.name') }}<br>
                        {{ config('lab.dpo.email') }}
                        @if(config('lab.dpo.phone'))<br>{{ config('lab.dpo.phone') }}@endif
                    @else
                        {{ config('lab.manufacturer.email') }}<br>
                        <span style="font-size: 8pt; color: #9ca3af;">DPO non nominato — alla scala attuale dell'attività di {{ config('lab.manufacturer.name') }} non ricorrono i presupposti di designazione obbligatoria ex Art. 37(1) GDPR; tale valutazione è riesaminata con cadenza trimestrale, in attuazione del principio di responsabilizzazione (Art. 5(2) GDPR).</span>
                    @endif
                </span>
            </td>
        </tr>
    </table>

    <div class="footer">Documento conforme al Regolamento (UE) 2017/745 (MDR) e GDPR 2016/679</div>
</body>
</html>
