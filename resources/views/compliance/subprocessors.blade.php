<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sub-responsabili del Trattamento — {{ config('lab.manufacturer.name') }}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; color: #333; line-height: 1.6; }
        h1 { color: #1a1a2e; border-bottom: 2px solid #1a1a2e; padding-bottom: 10px; }
        h2 { color: #2c3e50; margin-top: 30px; }
        .meta { background: #f8f9fa; border-left: 4px solid #3498db; padding: 12px 16px; margin: 20px 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th { background: #2c3e50; color: white; padding: 10px 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
        tr:nth-child(even) td { background: #f8f9fa; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 12px; font-weight: bold; }
        .badge-it { background: #d5f5e3; color: #1e8449; }
        .badge-eu { background: #d6eaf8; color: #1a5276; }
        .badge-us { background: #fdebd0; color: #a04000; }
        .note { font-size: 12px; color: #666; margin-top: 4px; }
        .footer { margin-top: 40px; border-top: 1px solid #ddd; padding-top: 16px; font-size: 13px; color: #888; }
        a { color: #2980b9; }
    </style>
</head>
<body>

<h1>Sub-responsabili del Trattamento</h1>
<p>
    Allegato A del <strong>Data Processing Agreement (DPA)</strong> — {{ config('lab.manufacturer.name') }}<br>
    Elenco dei soggetti terzi autorizzati al trattamento dei dati personali ai sensi dell'<strong>Art. 28.4 GDPR 2016/679</strong>.
</p>

<div class="meta">
    <strong>Versione Allegato A:</strong> {{ $version }}<br>
    <strong>Ultimo aggiornamento:</strong> {{ $updatedAt }}<br>
    <strong>Preavviso per variazioni:</strong> {{ $noticeDays }} giorni — i clienti ricevono comunicazione via email prima di ogni modifica.
</div>

<h2>Elenco Sub-responsabili Autorizzati</h2>

<table>
    <thead>
        <tr>
            <th>Fornitore</th>
            <th>Ruolo</th>
            <th>Paese</th>
            <th>Base giuridica trasferimento</th>
            <th>DPA</th>
            <th>Categorie dati</th>
        </tr>
    </thead>
    <tbody>
        @foreach($subprocessors as $sp)
        <tr>
            <td><strong>{{ $sp['name'] }}</strong></td>
            <td>
                {{ $sp['role'] }}
                @if(!empty($sp['notes']))
                    <div class="note">{{ $sp['notes'] }}</div>
                @endif
            </td>
            <td>
                @php
                    $countries = explode(' / ', $sp['country']);
                @endphp
                @foreach($countries as $c)
                    @php $c = trim($c); @endphp
                    <span class="badge {{ $c === 'IT' ? 'badge-it' : ($c === 'US' ? 'badge-us' : 'badge-eu') }}">
                        {{ $c }}
                    </span>
                @endforeach
            </td>
            <td>
                @php
                    $basisLabels = [
                        'intra_ue'       => 'Intra-UE — nessun trasferimento extra-SEE',
                        'art45_adequacy' => 'Art. 45 — Paese adeguato',
                        'art45_dpf'      => 'Art. 45 — Data Privacy Framework (decisione di adeguatezza UE 2023/1795)',
                        'art46_sccs'     => 'Art. 46(2)(c) — Clausole Contrattuali Standard (2021/914)',
                    ];
                    $basisPrimary  = $basisLabels[$sp['legal_basis_transfer']] ?? $sp['legal_basis_transfer'];
                    $basisFallback = !empty($sp['legal_basis_transfer_fallback'])
                        ? ($basisLabels[$sp['legal_basis_transfer_fallback']] ?? $sp['legal_basis_transfer_fallback'])
                        : null;
                @endphp
                <strong>Primaria:</strong> {{ $basisPrimary }}
                @if($basisFallback)
                    <div class="note">In subordine: {{ $basisFallback }}</div>
                @endif
            </td>
            <td>
                @if(!empty($sp['dpa_url']))
                    <a href="{{ $sp['dpa_url'] }}" target="_blank" rel="noopener">DPA v{{ $sp['dpa_version'] }}</a>
                @else
                    —
                @endif
            </td>
            <td>
                @foreach($sp['categories'] as $cat)
                    <span style="background:#eee; padding:1px 6px; border-radius:4px; font-size:12px; margin:1px; display:inline-block;">{{ $cat }}</span>
                @endforeach
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    <strong>Titolare del trattamento:</strong> {{ config('lab.manufacturer.name') }} — {{ config('lab.manufacturer.city') }} — P.IVA {{ config('lab.manufacturer.vat') }}<br>
    <strong>DPO:</strong>
    @if(config('lab.dpo.email'))
        {{ config('lab.dpo.name') ?: 'DPO' }} — <a href="mailto:{{ config('lab.dpo.email') }}">{{ config('lab.dpo.email') }}</a>
    @else
        In corso di nomina — contattare <a href="mailto:{{ config('lab.manufacturer.email') }}">{{ config('lab.manufacturer.email') }}</a>
    @endif
    <br><br>
    Versione JSON machine-readable: <a href="{{ route('compliance.subprocessors.json') }}">GET /compliance/subprocessors.json</a>
</div>

</body>
</html>
