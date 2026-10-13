<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; color: #374151; margin: 0; padding: 0; line-height: 1.5; }
        .wrapper { width: 100%; background-color: #f3f4f6; padding: 40px 0; }
        .container { background-color: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; }
        .header { background-color: #ffffff; padding: 26px; text-align: center; border-bottom: 4px solid #FF6037; }
        .header img { max-width: 160px; height: auto; display: block; margin: 0 auto; }
        .header-text { color: #FF6037; font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .body { padding: 32px 40px; }
        .title { font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 12px 0; }
        .text { font-size: 15px; color: #4b5563; }
        table.data { width: 100%; border-collapse: collapse; margin: 18px 0 4px 0; }
        table.data td { padding: 8px 10px; border-bottom: 1px solid #eef0f2; font-size: 15px; }
        table.data td.k { color: #6b7280; width: 38%; }
        table.data td.v { color: #111827; font-weight: 600; }
        .footer { background-color: #f9fafb; padding: 24px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
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
                <div class="title">Nuovo iscritto verificato</div>
                <div class="text">
                    Un nuovo iscritto ha confermato l'email e impostato la password.
                    È pronto per il vaglio nel pannello Amministrazione.
                </div>

                <table class="data">
                    <tr><td class="k">Ragione sociale</td><td class="v">{{ $lead->studio ?: '—' }}</td></tr>
                    <tr><td class="k">Referente</td><td class="v">{{ $lead->nome ?: '—' }}</td></tr>
                    <tr><td class="k">Email</td><td class="v">{{ $lead->email }}</td></tr>
                    <tr><td class="k">P.IVA</td><td class="v">{{ $lead->piva ?: '—' }}</td></tr>
                    <tr><td class="k">Tipo attività</td><td class="v">{{ $lead->tipo_attivita ?: '—' }}</td></tr>
                    <tr><td class="k">Telefono</td><td class="v">{{ $lead->telefono ?: '—' }}</td></tr>
                    <tr><td class="k">Utente Nextcloud</td><td class="v">{{ $lead->created_user_uid ?: '—' }}</td></tr>
                </table>
            </div>

            <div class="footer">
                <p>Notifica interna automatica — funnel di registrazione {{ config('app.name') }}.</p>
            </div>
        </div>
    </div>
</body>
</html>
