@php
    use Illuminate\Support\Facades\File;

    // Gestione Logo (PNG Base64 per stabilità DomPDF)
    $logoPath = storage_path('app/branding/logo.png');
    $logoBase64 = '';
    if (File::exists($logoPath)) {
        $type = pathinfo($logoPath, PATHINFO_EXTENSION);
        $data = File::get($logoPath);
        $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    // Gestione Intestazione dal TXT
    $headerTxtPath = storage_path('app/branding/header_info.txt');
    $headerLines = [];
    if (File::exists($headerTxtPath)) {
        $headerLines = explode("\n", File::get($headerTxtPath));
    }
    $companyName = array_shift($headerLines); // Prende la prima riga
@endphp

<table style="width: 100%; border: none; border-collapse: collapse; margin-bottom: 20px; border-bottom: 2px solid #ff6f3f; padding-bottom: 10px;">
    <tr>
        <td style="width: 30%; border: none; vertical-align: middle;">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" style="max-height: 60px; max-width: 180px;">
            @else
                <span style="color: #ff6f3f; font-weight: bold; font-size: 22px;">{{ config('app.name') }}</span>
            @endif
        </td>
        <td style="width: 70%; border: none; text-align: right; vertical-align: middle;">
            <div style="font-size: 11pt; font-weight: bold; color: #111827; margin-bottom: 2px;">{{ $companyName }}</div>
            <div style="font-size: 8pt; line-height: 1.2; color: #4b5563;">
                @foreach($headerLines as $line)
                    {{ $line }}<br>
                @endforeach
            </div>
        </td>
    </tr>
</table>