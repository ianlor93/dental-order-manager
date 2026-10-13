{{--
  ============================================================================
  MASTER TEMPLATE EMAIL — Enamel (email-safe, table-based, CSS inline)
  ----------------------------------------------------------------------------
  Cornice unica condivisa da TUTTE le Mailable OM. Re-implementazione email-safe
  del layout Claude Design ("Email unificata Enamel"): NON usa flex/grid/webfont
  obbligatorie/CSS in <head> per il layout — tutto inline + table.

  CONTRATTO DEL CORPO (variabili passate da ogni Mailable via ->with([...])):
    $title          string         — titolo (h1)
    $paragraphs     string[]        — paragrafi corpo (escapati, \n→<br>)  [risolve P2]
    $meta           ?string        — riga meta sotto i paragrafi (grigia, piccola)
    $details        array          — righe etichetta·valore: [['label'=>,'value'=>], ...]
    $infobox        ?array         — box informativo: ['label'=>'Nota','text'=>'...']
    $cta            ?array         — bottone: ['label'=>,'url'=>,'variant'=>'primary'|'secondary']
    $notes          string[]        — note piccole grigie sotto la CTA (es. "link valido 48h")
    $footerVariant  'external'|'internal'  — footer cliente/esterno vs interno
    $footerReason   ?string        — riga "perché ricevi questa email" (specifica per mail)
    $unsubscribeUrl ?string        — slot annulla-iscrizione (solo external; placeholder finché manca l'endpoint)
    $preheader      ?string        — testo preheader nascosto (anteprima inbox)
    $logoPath       ?string        — path PNG per CID embed ($message->embed)
    $logoSrc        ?string        — override src logo (usato dalle ANTEPRIME, niente $message)

  Token brand (da DS): coral #FF6037 · hover #E85530 · ink #0B0B0C · muted #6E6B66
  · bordo #E5E1DA · pannello #F4F5F6 · bianco #FFFFFF. Font 'Ubuntu' + web-safe fallback.
  ============================================================================
--}}
@php
    $title          = $title          ?? '';
    $paragraphs     = $paragraphs      ?? [];
    $meta           = $meta           ?? null;
    $details        = $details        ?? [];
    $infobox        = $infobox        ?? null;
    $cta            = $cta            ?? null;
    $notes          = $notes          ?? [];
    $footerVariant  = $footerVariant  ?? 'external';
    $footerReason   = $footerReason   ?? null;
    $unsubscribeUrl = $unsubscribeUrl ?? null;
    $preheader      = $preheader      ?? null;

    // Logo: nelle Mailable reali si usa il CID embed; nelle anteprime si passa $logoSrc.
    $logoSrc = $logoSrc ?? ((isset($message) && !empty($logoPath)) ? $message->embed($logoPath) : null);

    if ($cta) {
        $cta = array_merge(['variant' => 'primary', 'label' => '', 'url' => '#'], $cta);
    }

    // Font stack web-safe (Ubuntu è progressive-enhancement: caricato dove possibile, altrimenti fallback)
    $font = "'Ubuntu','Segoe UI',Roboto,Helvetica,Arial,sans-serif";
@endphp
<!DOCTYPE html>
<html lang="it" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $title }}</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    @verbatim
    <style>
        body { margin:0 !important; padding:0 !important; width:100% !important; }
        table { border-collapse:collapse; }
        img { border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
        a { text-decoration:none; }
        a.enamel-cta-primary:hover { background:#E85530 !important; }
        a.enamel-cta-secondary:hover { background:#0B0B0C !important; color:#FFFFFF !important; }
        @media only screen and (max-width:620px) {
            .enamel-card   { width:100% !important; }
            .enamel-header { padding:26px 22px !important; }
            .enamel-body   { padding:28px 22px 24px 22px !important; }
            .enamel-footer { padding:22px 22px 24px 22px !important; }
            .enamel-title  { font-size:23px !important; }
            .enamel-logo   { height:140px !important; }
        }
    </style>
    @endverbatim
</head>
<body style="margin:0; padding:0; background:#F4F5F6; -webkit-font-smoothing:antialiased; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">

    {{-- Preheader nascosto (anteprima inbox) --}}
    @if($preheader)
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#F4F5F6; opacity:0;">{{ $preheader }}&#8203;&zwnj;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F4F5F6;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                {{-- ── Card 600px ────────────────────────────────────── --}}
                <table role="presentation" class="enamel-card" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background:#FFFFFF; border:1px solid #E5E1DA;">

                    {{-- Header / logo --}}
                    <tr>
                        <td class="enamel-header" align="center" style="padding:30px 32px; background:#F4F5F6; border-bottom:1px solid #E5E1DA;">
                            @if($logoSrc)
                                <img src="{{ $logoSrc }}" alt="{{ config('app.name') }}" height="185" class="enamel-logo" style="height:185px; width:auto; max-width:100%; display:inline-block; border:0;">
                            @else
                                <span style="font-family:{{ $font }}; font-size:24px; font-weight:700; letter-spacing:.04em; color:#FF6037;">{{ config('app.name') }}</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td class="enamel-body" style="padding:38px 40px 34px 40px; background:#FFFFFF;">

                            {{-- Titolo --}}
                            @if($title !== '')
                            <h1 class="enamel-title" style="margin:0 0 18px 0; font-family:{{ $font }}; font-size:26px; line-height:1.22; letter-spacing:-.01em; font-weight:700; color:#0B0B0C;">{{ $title }}</h1>
                            @endif

                            {{-- Paragrafi corpo (escapati; \n→<br>) --}}
                            @foreach($paragraphs as $p)
                            <p style="margin:0 0 16px 0; font-family:{{ $font }}; font-size:16px; line-height:1.62; color:#0B0B0C;">{!! nl2br(e($p)) !!}</p>
                            @endforeach

                            {{-- Riga meta (grigia, piccola) --}}
                            @if($meta)
                            <p style="margin:4px 0 18px 0; font-family:{{ $font }}; font-size:14px; line-height:1.55; color:#6E6B66;">{!! nl2br(e($meta)) !!}</p>
                            @endif

                            {{-- Blocco dettagli (etichetta · valore) --}}
                            @if(!empty($details))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0 24px 0; border:1px solid #E5E1DA; border-radius:4px;">
                                @foreach($details as $i => $row)
                                <tr>
                                    <td style="padding:12px 16px; {{ $i < count($details) - 1 ? 'border-bottom:1px solid #E5E1DA;' : '' }} font-family:{{ $font }}; font-size:13px; line-height:1.4; color:#6E6B66; vertical-align:top;">{{ $row['label'] ?? '' }}</td>
                                    <td align="right" style="padding:12px 16px; {{ $i < count($details) - 1 ? 'border-bottom:1px solid #E5E1DA;' : '' }} font-family:{{ $font }}; font-size:14px; line-height:1.4; font-weight:500; color:#0B0B0C; text-align:right; vertical-align:top;">{{ $row['value'] ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            {{-- Box informativo --}}
                            @if($infobox)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 26px 0;">
                                <tr><td style="padding:14px 16px; background:#F4F5F6; border:1px solid #E5E1DA; border-radius:4px;">
                                    @if(!empty($infobox['label']))
                                    <div style="font-family:{{ $font }}; font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#FF6037; margin-bottom:5px;">{{ $infobox['label'] }}</div>
                                    @endif
                                    <p style="margin:0; font-family:{{ $font }}; font-size:13px; line-height:1.55; color:#0B0B0C;">{!! nl2br(e($infobox['text'] ?? '')) !!}</p>
                                </td></tr>
                            </table>
                            @endif

                            {{-- CTA bulletproof --}}
                            @if($cta)
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 4px 0;">
                                <tr><td align="center">
                                    @if(($cta['variant'] ?? 'primary') === 'secondary')
                                        {{-- Secondaria: outline ink --}}
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $cta['url'] }}" style="v-text-anchor:middle;mso-fit-shape-to-text:true;height:48px;" arcsize="50%" strokecolor="#0B0B0C" fillcolor="#FFFFFF">
                                        <w:anchorlock/><center style="color:#0B0B0C;font-family:'Segoe UI',Arial,sans-serif;font-size:16px;font-weight:bold;padding:0 30px;">{{ $cta['label'] }}</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-- -->
                                        <a href="{{ $cta['url'] }}" class="enamel-cta-secondary" style="display:inline-block; background:#FFFFFF; color:#0B0B0C; font-family:{{ $font }}; font-weight:700; font-size:16px; text-decoration:none; padding:13px 30px; border:2px solid #0B0B0C; border-radius:9999px;">{{ $cta['label'] }}</a>
                                        <!--<![endif]-->
                                    @else
                                        {{-- Primaria: ink su coral --}}
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $cta['url'] }}" style="v-text-anchor:middle;mso-fit-shape-to-text:true;height:50px;" arcsize="50%" stroke="f" fillcolor="#FF6037">
                                        <w:anchorlock/><center style="color:#0B0B0C;font-family:'Segoe UI',Arial,sans-serif;font-size:16px;font-weight:bold;padding:0 34px;">{{ $cta['label'] }}</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-- -->
                                        <a href="{{ $cta['url'] }}" class="enamel-cta-primary" style="display:inline-block; background:#FF6037; color:#0B0B0C; font-family:{{ $font }}; font-weight:700; font-size:16px; text-decoration:none; padding:15px 34px; border-radius:9999px;">{{ $cta['label'] }}</a>
                                        <!--<![endif]-->
                                    @endif
                                </td></tr>
                            </table>
                            @endif

                            {{-- Note piccole grigie (es. "link valido 48h") --}}
                            @foreach($notes as $n)
                            <p style="margin:18px 0 0 0; font-family:{{ $font }}; font-size:13px; line-height:1.55; color:#6E6B66;">{!! nl2br(e($n)) !!}</p>
                            @endforeach

                        </td>
                    </tr>

                    {{-- Footer unificato (sfondo ink #0B0B0C, testo chiaro) --}}
                    <tr>
                        <td class="enamel-footer" style="padding:26px 32px 30px 32px; background:#0B0B0C;">
                            <p style="margin:0 0 10px 0; font-family:{{ $font }}; font-size:12px; line-height:1.7; color:#FFFFFF;">{{ config('lab.manufacturer.name') }} &middot; P.IVA {{ config('lab.manufacturer.vat') }}<br>{{ config('lab.manufacturer.address') }}, {{ config('lab.manufacturer.zip') }} {{ config('lab.manufacturer.city') }} &middot; {{ config('lab.manufacturer.email') }}</p>
                            @if($footerReason)
                            <p style="margin:0 0 8px 0; padding-top:12px; border-top:1px solid #2A2A2C; font-family:{{ $font }}; font-size:12px; line-height:1.6; color:#C9C7C3;">{{ $footerReason }}</p>
                            @else
                            <div style="padding-top:12px; border-top:1px solid #2A2A2C; font-size:0; line-height:0;">&nbsp;</div>
                            @endif
                            <p style="margin:0; font-family:{{ $font }}; font-size:12px; line-height:1.6; color:#C9C7C3;">Questa è una mail automatica, si prega di non rispondere.</p>
                            @if($footerVariant === 'external')
                            <p style="margin:10px 0 0 0;"><a href="{{ $unsubscribeUrl ?? '#' }}" style="font-family:{{ $font }}; font-size:12px; color:#FFFFFF; text-decoration:underline; text-decoration-color:#FF6037;">Annulla iscrizione</a></p>
                            @endif
                        </td>
                    </tr>

                </table>
                {{-- ── /Card ─────────────────────────────────────────── --}}

            </td>
        </tr>
    </table>

</body>
</html>
