<!DOCTYPE html>
<html lang="it">
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
        .btn-container { text-align: center; margin: 32px 0; }
        .btn { display: inline-block; background-color: #FF6037; color: #0B0B0C !important; text-decoration: none; padding: 14px 34px; border-radius: 8px; font-weight: 700; font-size: 16px; }
        .muted { font-size: 14px; color: #6b7280; }
        .highlight-box { background-color: #fff7f3; border-left: 4px solid #FF6037; border-radius: 6px; padding: 16px 20px; margin: 20px 0; font-size: 13px; color: #4b5563; word-break: break-all; }
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
                <div class="title">Ciao {{ $lead->nome }}, confermiamo la tua email</div>

                <div class="text">
                    Per completare la registrazione alla piattaforma, conferma il tuo indirizzo email
                    e imposta la password del tuo account con un clic sul pulsante qui sotto.
                </div>

                <div class="btn-container">
                    <a href="{{ $verifyUrl }}" class="btn">Conferma e imposta la password</a>
                </div>

                <div class="text muted">
                    Il link è valido per <strong>48 ore</strong> e può essere usato una sola volta.
                    Dopo aver impostato la password, il tuo account sarà attivato a seguito della
                    verifica da parte del nostro team.
                </div>

                <div class="text muted" style="margin-bottom:8px;">
                    Se il pulsante non funziona, copia e incolla questo indirizzo nel browser:
                </div>
                <div class="highlight-box">{{ $verifyUrl }}</div>

                <div class="text muted">
                    Se non hai richiesto tu la registrazione, ignora pure questa email.
                </div>
            </div>

            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('lab.manufacturer.name') }} — P.IVA {{ config('lab.manufacturer.vat') }}</p>
                <p>Hai ricevuto questa email perché è stata avviata una registrazione su {{ config('app.name') }} con questo indirizzo.</p>
            </div>
        </div>
    </div>
</body>
</html>
