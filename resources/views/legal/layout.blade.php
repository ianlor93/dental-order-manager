{{--
    Layout condiviso pagine legali pubbliche — design Enamel-DS «Pagine Legali Pubbliche».
    Chrome (header sticky + footer night) da UNA sola fonte; il contenuto 2026.4 dei 3 blade
    resta invariato in @yield('content'). Font + token DS self-hosted via asset('ds/colors_and_type.css')
    (NIENTE Google Fonts). Logo asset('ds/mark.svg').
    Adattamenti al CSS del design per le classi del contenuto: .cite reso inline (le citazioni sono
    span inline), .legal-notice con font diretto (testo senza <p>), rimosso .lg-routebar (aiuto-reviewer).
--}}
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('ds/colors_and_type.css') }}">
    <style>
        html, body { margin: 0; background: var(--canvas); }

        .lg-root { font-family: var(--font-sans); color: var(--ink); background: var(--canvas); }

        /* site chrome */
        .lg-top { position: sticky; top:0; z-index:20; height:64px; display:flex; align-items:center; justify-content:space-between; padding:0 clamp(20px,5vw,48px); border-bottom:1px solid var(--line); background: rgba(244,245,246,0.9); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
        .lg-brand { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .lg-brand img { height:36px; width:36px; display:block; }
        .lg-brand .wm { font-weight:400; letter-spacing:0.08em; font-size:18px; color:var(--ink); line-height:1; }
        .lg-topnav { display:flex; gap:26px; }
        .lg-topnav a { font-size:13.5px; color:var(--mute); text-decoration:none; font-weight:500; transition:color 160ms var(--easing-standard); }
        .lg-topnav a:hover { color:var(--ink); }
        .lg-topnav a.is-current { color:var(--ink); }

        /* document header */
        .lg-head { padding: clamp(40px,6vw,72px) clamp(20px,5vw,48px) clamp(28px,4vw,40px); border-bottom:1px solid var(--line); }
        .lg-head-inner { max-width: 980px; margin:0 auto; }
        .lg-badge { display:inline-flex; align-items:center; gap:7px; font-size:12px; letter-spacing:0.06em; text-transform:uppercase; font-weight:500; color:var(--accent); border:1px solid var(--line); border-radius:4px; padding:5px 11px; background:var(--paper); }
        .lg-head h1 { font-size: clamp(30px,5vw,44px); margin: 18px 0 0; letter-spacing:-0.015em; line-height:1.1; }
        .lg-meta { margin-top:16px; font-size:14px; color:var(--mute); display:flex; flex-wrap:wrap; gap:6px 12px; align-items:center; }
        .lg-meta .dot { color:var(--line); }

        /* body */
        .lg-body { max-width: 980px; margin: 0 auto; padding: clamp(32px,5vw,56px) clamp(20px,5vw,48px) clamp(56px,7vw,88px); }

        /* long-form content (blade .legal-doc) */
        .legal-doc { color:var(--ink); }
        .legal-doc h2 { font-size: clamp(21px,2.6vw,25px); margin: 46px 0 0; line-height:1.25; letter-spacing:-0.005em; scroll-margin-top: 88px; }
        .legal-doc h2:first-child { margin-top:0; }
        .legal-doc h3 { font-size:17px; margin:30px 0 0; line-height:1.35; }
        .legal-doc p { font-size:16px; line-height:1.72; color:var(--ink); margin:14px 0 0; max-width:68ch; }
        .legal-doc ul { margin:14px 0 0; padding-left:22px; }
        .legal-doc li { font-size:16px; line-height:1.72; margin:7px 0; max-width:66ch; }
        .legal-doc a { color:var(--ink); text-decoration:underline; text-decoration-color:var(--accent); text-decoration-thickness:2px; text-underline-offset:3px; }
        .legal-doc a:hover { color:var(--accent); }
        .legal-doc hr { border:none; border-top:1px solid var(--line); margin:40px 0; }

        /* tables */
        .legal-doc table { width:100%; border-collapse:collapse; font-size:14.5px; margin:22px 0 0; border:1px solid var(--line); }
        .legal-doc thead th { text-align:left; font-weight:500; font-size:11.5px; letter-spacing:0.04em; text-transform:uppercase; color:var(--mute); background:var(--canvas); padding:12px 16px; border-bottom:1px solid var(--line); }
        .legal-doc tbody td { padding:14px 16px; border-bottom:1px solid var(--line); color:var(--ink); line-height:1.5; vertical-align:top; }
        .legal-doc tbody tr:last-child td { border-bottom:none; }
        .legal-doc tbody td:first-child { font-weight:500; }

        /* legal notice box (contenuto: <div class="legal-notice">…testo…</div>) */
        .legal-notice { margin:26px 0 0; background:var(--canvas); border:1px solid var(--line); border-left:3px solid var(--accent); border-radius:6px; padding:18px 20px; font-size:15px; line-height:1.6; color:var(--ink); }

        /* citazione normativa — INLINE (il contenuto usa <span class="cite">[…]</span> dentro i <p>) */
        .cite { color:var(--accent); font-weight:600; font-size:0.85em; }

        /* footer (shared, night) */
        .lg-foot { background:var(--night); color:#fff; padding: clamp(40px,5vw,56px) clamp(20px,5vw,48px); }
        .lg-foot-inner { max-width:980px; margin:0 auto; display:flex; flex-direction:column; gap:26px; }
        .lg-foot-links { display:flex; flex-wrap:wrap; gap:10px 30px; }
        .lg-foot-links a { color:#fff; text-decoration:none; font-size:14.5px; font-weight:500; opacity:0.86; transition:opacity 160ms var(--easing-standard), color 160ms var(--easing-standard); }
        .lg-foot-links a:hover { opacity:1; color:var(--accent); }
        .lg-foot-row { display:flex; flex-wrap:wrap; justify-content:space-between; gap:20px 32px; align-items:flex-end; border-top:1px solid var(--steel); padding-top:26px; }
        .lg-foot-brand { display:flex; align-items:center; gap:9px; }
        .lg-foot-brand img { height:30px; width:30px; }
        .lg-foot-brand .wm { font-weight:400; letter-spacing:0.08em; font-size:16px; color:#fff; }
        .lg-foot-id { font-size:13px; color:#A8A6A1; line-height:1.75; max-width:64ch; }
        .lg-foot-mail { font-size:14px; color:#A8A6A1; margin-top:8px; }
        .lg-foot-mail a { color:var(--accent); text-decoration:none; font-weight:500; }
        .lg-foot-mail a:hover { text-decoration:underline; text-decoration-thickness:2px; text-underline-offset:3px; }

        @media (max-width: 860px) {
            .lg-topnav { display:none; }
            .legal-doc table { font-size:13.5px; }
            .legal-doc thead th, .legal-doc tbody td { padding:10px 12px; }
        }

        @media print {
            .lg-top, .lg-foot { display:none !important; }
            .lg-body { max-width:none; padding:0; }
            .lg-head { padding:0 0 16px; border-color:#999; }
            html, body, .lg-root, .lg-head { background:#fff !important; }
            .legal-doc p, .legal-doc li, .legal-doc h2, .legal-doc h3, .lg-head h1, .legal-doc td { color:#000 !important; }
            .lg-badge { color:#000; border-color:#999; }
            .legal-doc a { color:#000 !important; }
            .legal-notice { border-color:#999; background:#fff; }
        }
    </style>
</head>
<body>
<div class="lg-root">

    <header class="lg-top">
        <a class="lg-brand" href="/privacy">
            <img src="{{ asset('ds/mark.svg') }}" alt="{{ config('app.name') }}">
            <span class="wm">{{ config('app.name') }}</span>
        </a>
        <nav class="lg-topnav">
            <a href="/privacy" class="{{ request()->is('privacy') ? 'is-current' : '' }}">Privacy del sito</a>
            <a href="/cookie" class="{{ request()->is('cookie') ? 'is-current' : '' }}">Informativa cookie</a>
            <a href="/note-legali" class="{{ request()->is('note-legali') ? 'is-current' : '' }}">Note legali</a>
        </nav>
    </header>

    <div class="lg-head">
        <div class="lg-head-inner">
            <span class="lg-badge">@yield('badge', 'Versione 2026.4')</span>
            <h1>@yield('title')</h1>
            <div class="lg-meta">@yield('meta')</div>
        </div>
    </div>

    <div class="lg-body">
        <div class="legal-doc">
@yield('content')
        </div>
    </div>

    <footer class="lg-foot">
        <div class="lg-foot-inner">
            <div class="lg-foot-links">
                <a href="/privacy">Privacy del sito</a>
                <a href="/cookie">Informativa cookie</a>
                <a href="/note-legali">Note legali</a>
            </div>
            <div class="lg-foot-row">
                <div class="lg-foot-brand">
                    <img src="{{ asset('ds/mark.svg') }}" alt="{{ config('app.name') }}">
                    <span class="wm">{{ config('app.name') }}</span>
                </div>
                <div>
                    <div class="lg-foot-id">{{ config('lab.manufacturer.name') }} · P.IVA {{ config('lab.manufacturer.vat') }} · {{ config('lab.manufacturer.address') }}, {{ config('lab.manufacturer.zip') }} {{ config('lab.manufacturer.city') }} · Reg. MDR {{ config('lab.manufacturer.mdr_registration') }}</div>
                    <div class="lg-foot-mail">Contatti: <a href="mailto:{{ config('lab.manufacturer.email') }}">{{ config('lab.manufacturer.email') }}</a></div>
                </div>
            </div>
        </div>
    </footer>

</div>
</body>
</html>
