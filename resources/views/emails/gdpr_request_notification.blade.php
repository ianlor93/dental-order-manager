<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; font-size: 14px; }
        h2 { color: #1a1a2e; }
        h3 { color: #555; margin-top: 20px; }
        ul { line-height: 1.8; }
        pre { background: #f5f5f5; padding: 12px; border-radius: 4px; white-space: pre-wrap; word-break: break-word; }
        .badge { display: inline-block; background: #e74c3c; color: white; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .footer { margin-top: 30px; border-top: 1px solid #ddd; padding-top: 12px; color: #888; font-size: 12px; }
    </style>
</head>
<body>

<h2>Nuova richiesta GDPR ricevuta <span class="badge">AZIONE RICHIESTA</span></h2>

<p>È stata registrata una nuova richiesta per il diritto: <strong>{{ $label }}</strong></p>

<ul>
    <li><strong>Riferimento:</strong> #{{ $gdprRequest->id }}</li>
    <li><strong>Interessato:</strong> {{ $interested->displayname }} ({{ $interested->uid }})</li>
    <li><strong>Email:</strong> {{ $interested->email ?? '(non disponibile)' }}</li>
    <li><strong>Categoria dati:</strong> {{ $gdprRequest->object_category ?? '—' }}</li>
    <li><strong>Ricevuta il:</strong> {{ $gdprRequest->received_at->format('d/m/Y H:i') }}</li>
    <li><strong>⚠️ Scadenza risposta Art. 12 GDPR:</strong> <strong>{{ $deadline }}</strong></li>
    <li><strong>IP richiedente:</strong> {{ $gdprRequest->ip_address ?? '—' }}</li>
</ul>

@if($gdprRequest->motivation)
<h3>Motivazione / Dettagli</h3>
<pre>{{ $gdprRequest->motivation }}</pre>
@endif

<p>
    Accedi al backoffice {{ config('app.name') }} per gestire la richiesta, registrare la decisione e inviare risposta all'interessato
    entro la scadenza indicata.
</p>

<div class="footer">
    Questa notifica è generata automaticamente da {{ config('app.name') }}.<br>
    Ref. interno: GdprRequest #{{ $gdprRequest->id }} — {{ $gdprRequest->right_article }}
</div>

</body>
</html>
