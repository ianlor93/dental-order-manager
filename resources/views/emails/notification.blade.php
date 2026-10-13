<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f3f4f6; color: #374151; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; line-height: 1.5; }
        .wrapper { width: 100%; background-color: #f3f4f6; padding: 40px 0; }
        .container { background-color: #ffffff; max-width: 600px; margin: 0 auto; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb; }
        
        /* Header Arancione (Brand) */
        .header { background-color: #ffffff; padding: 30px; text-align: center; border-bottom: 4px solid #FF6037; }
        .header img { max-width: 200px; height: auto; display: block; margin: 0 auto; }
        .header-text { color: #FF6037; font-size: 24px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-top: 10px; }
        
        /* Corpo */
        .body { padding: 40px 40px 20px 40px; }
        .title { font-size: 22px; font-weight: 700; color: #111827; margin: 0 0 20px 0; }
        .text { font-size: 16px; color: #4b5563; line-height: 1.6; margin-bottom: 30px; white-space: pre-line; }
        
        /* Bottone (Primary Brand Color) */
        .btn-container { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; background-color: #FF6037; color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: bold; font-size: 16px; transition: background-color 0.2s; }
        .btn:hover { background-color: #ED4F23; }
        
        /* Footer */
        .footer { background-color: #f9fafb; padding: 30px; text-align: center; font-size: 13px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        .footer p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                @if(isset($content['logo_path']) && file_exists($content['logo_path']))
                    <img src="{{ $message->embed($content['logo_path']) }}" alt="{{ config('app.name') }}">
                @else
                    <div class="header-text">{{ config('app.name') }}</div>
                @endif
            </div>
            
            <div class="body">
                <div class="title">{{ $content['title'] }}</div>
                
                <div class="text">
                    {!! nl2br(e($content['message'])) !!}
                </div>
                
                @if(isset($content['actionUrl']))
                    <div class="btn-container">
                        <a href="{{ $content['actionUrl'] }}" class="btn">{{ $content['actionText'] ?? 'Accedi al Portale' }}</a>
                    </div>
                @endif
            </div>
            
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('lab.manufacturer.name') }}</p>
                <p>Questa è una notifica automatica, si prega di non rispondere.</p>
            </div>
        </div>
    </div>
</body>
</html>