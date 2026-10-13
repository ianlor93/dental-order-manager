{{--
    Pagina READ-ONLY di revisione di un documento legale già presentato nell'onboarding.
    Servita da LegalController::review() — NESSUN side-effect (niente accettazioni, niente
    scrittura di onboarding_step). Apribile in nuova scheda dal wizard; il motore di firma
    (forward-only, append-only) resta intatto. DS self-hosted: /ds/colors_and_type.css.
--}}
@php $version = $version ?? config('legal.version', '2026.4'); @endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisione documento · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="/ds/colors_and_type.css">
    <style>
        html, body { margin: 0; background: var(--canvas); }
        body { font-family: var(--font-sans); color: var(--ink); -webkit-font-smoothing: antialiased; }
        *, *::before, *::after { box-sizing: border-box; }
        .lg-head { display: flex; align-items: center; justify-content: space-between; height: 64px; padding: 0 clamp(20px, 5vw, 48px); border-bottom: 1px solid var(--line); background: var(--canvas); }
        .lg-brand { display: flex; align-items: center; gap: 10px; }
        .lg-brand img { display: block; height: 40px; width: 40px; }
        .lg-brand span { font-weight: 400; letter-spacing: 0.18em; font-size: 18px; color: var(--ink); text-transform: lowercase; }
        .lg-head__ctx { font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 500; color: var(--mute); }
        .lg-main { display: flex; justify-content: center; align-items: flex-start; padding: clamp(24px, 5vw, 64px) clamp(16px, 5vw, 48px); }
        .lg-card { width: min(840px, 100%); background: var(--paper); border: 1px solid var(--line); border-radius: 4px; box-shadow: 0 1px 2px rgb(0 0 0 / 0.05); padding: clamp(24px, 4vw, 44px); display: flex; flex-direction: column; gap: 22px; }
        .lg-overline { font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 500; color: var(--mute); }
        .lg-h2 { font-size: clamp(24px, 4vw, 30px); line-height: 1.15; letter-spacing: -0.01em; font-weight: 700; margin: 0; }
        .lg-ro { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 500; color: var(--accent); }
        .lg-ro svg { flex: none; }
        .lg-scroll { height: min(56vh, 560px); overflow: hidden auto; border: 1px solid var(--line); border-radius: 6px; padding: 22px 26px; background: var(--canvas); font-size: 13.5px; line-height: 1.7; color: var(--ink); white-space: pre-line; text-align: left; }
        .lg-scroll::-webkit-scrollbar { width: 10px; }
        .lg-scroll::-webkit-scrollbar-thumb { background: #d9d5cd; border-radius: 9999px; border: 3px solid var(--canvas); }
        .lg-scroll::-webkit-scrollbar-track { background: transparent; }
        .lg-actions { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; border-top: 1px solid var(--line); padding-top: 20px; }
        .lg-note { font-size: 12px; color: var(--mute); max-width: 42ch; }
        .lg-btn { background: var(--accent); color: var(--ink); border: none; border-radius: 9999px; padding: 0 24px; height: 46px; font-family: inherit; font-weight: 500; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; text-decoration: none; transition: background 160ms; }
        .lg-btn:hover { background: #ed4f23; }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>
</head>
<body>
    <header class="lg-head">
        <span class="lg-brand">
            <img src="/ds/mark.svg" alt="{{ config('app.name') }}">
            <span>{{ config('app.name') }}</span>
        </span>
        <span class="lg-head__ctx">Revisione</span>
    </header>

    <main class="lg-main">
        <div class="lg-card">
            <div style="display:flex;flex-direction:column;gap:8px;">
                <span class="lg-overline">Documento già firmato · Versione {{ $version }}</span>
                <h2 class="lg-h2">{{ $title }}</h2>
                <span class="lg-ro">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    Sola lettura — questa pagina non modifica le firme già registrate.
                </span>
            </div>

            <div class="lg-scroll">{{ $text }}</div>

            <div class="lg-actions">
                <span class="lg-note">Per riprendere da dove eri, chiudi questa scheda oppure usa il pulsante.</span>
                <a class="lg-btn" href="{{ route('legal.onboarding') }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Torna all'onboarding
                </a>
            </div>
        </div>
    </main>
</body>
</html>
