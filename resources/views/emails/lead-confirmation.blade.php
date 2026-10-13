<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; color: #374151; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; line-height: 1.5; }
        .wrapper { width: 100%; background-color: #f3f4f6; padding: 40px 0; }
        .container { background-color: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; }
        .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 4px solid #FF6037; }
        .header img { max-width: 180px; height: auto; display: block; margin: 0 auto; }
        .header-text { color: #FF6037; font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .body { padding: 40px; }
        .title { font-size: 22px; font-weight: 700; color: #111827; margin: 0 0 16px 0; }
        .text { font-size: 16px; color: #4b5563; line-height: 1.65; margin-bottom: 20px; }
        .highlight-box { background-color: #fff7f3; border-left: 4px solid #FF6037; border-radius: 6px; padding: 18px 22px; margin: 24px 0; font-size: 15px; color: #4b5563; }
        .footer { background-color: #f9fafb; padding: 28px; text-align: center; font-size: 13px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        .footer p { margin: 4px 0; }
        a { color: #FF6037; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                @if($logoPath)
                    <img src="{{ $message->embed($logoPath) }}" alt="{{ config('app.name') }}">
                @else
                    <div class="header-text">{{ config('app.name') }}</div>
                @endif
            </div>

            <div class="body">
                <div class="title">Ciao {{ $lead->nome }}, grazie!</div>

                <div class="text">
                    Abbiamo ricevuto la tua pre-registrazione e ci fa davvero piacere
                    l'interesse che hai mostrato verso {{ config('lab.manufacturer.name') }}.
                </div>

                <div class="text">
                    Stiamo lavorando a qualcosa di nuovo per odontotecnici, studi
                    odontoiatrici e fornitori di servizi per l'odontoiatria. Sarai
                    tra i primi a saperlo non appena tutto sarà pronto.
                </div>

                <div class="highlight-box">
                    Ti ricontatteremo entro <strong>24 ore</strong> all'indirizzo
                    <strong>{{ $lead->email }}</strong> per condividere con te le
                    prime novità in anteprima.
                </div>

                <div class="text" style="font-size:14px; color:#6b7280;">
                    Se hai domande nel frattempo, scrivici pure a
                    <a href="mailto:{{ config('lab.manufacturer.email') }}">{{ config('lab.manufacturer.email') }}</a>:
                    saremo felici di risponderti.
                </div>

                <div class="text" style="font-size:14px; color:#6b7280; margin-top:24px;">
                    A presto,<br>
                    <strong>Il team di {{ config('lab.manufacturer.name') }}</strong>
                </div>
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('lab.manufacturer.name') }} — P.IVA {{ config('lab.manufacturer.vat') }}</p>
                <p>Questa email è stata inviata perché hai compilato il form di pre-registrazione su {{ config('lab.landing_url') }}.</p>
            </div>
        </div>
    </div>
</body>
</html>
