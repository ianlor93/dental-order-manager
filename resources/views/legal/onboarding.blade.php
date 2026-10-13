{{--
    Wizard onboarding legale (4 step) — restyle dal design Claude «Legal Gate».
    SOLO presentazione: motore server-driven INVARIATO.
      - $step (1-4), $text (loadText reale = testo su cui si calcola il content_hash), $studio, $user
      - POST a route('legal.store') + @csrf; ogni "Avanti" registra l'accettazione lato server
      - nomi campo INVARIATI (contratto col controller): tos_accepted / vessatorie_accepted /
        privacy_accepted / name / vat_number / sdi_code / pec_email / dentist_name /
        itca_code | dentist_license + albo_provincia / mobile / address / city / zip_code / dpa_accepted
      - fork reale = $user->isLaboratorio() (Lab) vs else (Studio). Il ramo "Fornitore" del
        design (REA/tipoServizio) NON è cablato nel controller → non reso (vedi report).
      - forward-only: il controller non ha handler "Indietro" → nessun bottone Indietro.
    DS self-hosted: /ds/colors_and_type.css (token + Ubuntu), niente Google Fonts.
--}}
@php
    $version  = config('legal.version', '2026.3');
    $progress = [1 => 'Termini', 2 => 'Clausole', 3 => 'Privacy', 4 => 'Contratto'];
    $titles   = [
        1 => 'Termini e condizioni di fornitura',
        2 => 'Approvazione clausole (Art. 1341 c.c.)',
        3 => 'Informativa privacy (Artt. 13-14 GDPR)',
        4 => 'Contratto e dati professionali',
    ];
    $legal = [
        1 => [
            'overline' => 'Passo 1 di 4 · Termini',
            'desc'     => "Leggi integralmente i Termini e Condizioni di Fornitura (versione {$version}) e conferma la lettura.",
            'field'    => 'tos_accepted',
            'accept'   => "Dichiaro di aver letto e di accettare integralmente i Termini e Condizioni di Fornitura MDR (versione {$version}).",
            'next'     => 'Avanti — Clausole',
        ],
        2 => [
            'overline' => 'Passo 2 di 4 · Clausole vessatorie',
            'desc'     => "Ai sensi dell'Art. 1341, comma 2, c.c., le clausole elencate qui sotto richiedono la tua approvazione specifica e separata.",
            'field'    => 'vessatorie_accepted',
            'accept'   => "Ai sensi e per gli effetti dell'Art. 1341, comma 2, c.c., approvo specificamente le clausole vessatorie elencate nel testo qui sopra, di cui ho preso integrale visione.",
            'next'     => 'Avanti — Privacy',
        ],
        3 => [
            'overline' => 'Passo 3 di 4 · Privacy',
            'desc'     => "Prendi visione dell'Informativa Privacy (versione {$version}). Non è richiesto un consenso, ma una presa visione obbligatoria.",
            'field'    => 'privacy_accepted',
            'accept'   => "Dichiaro di aver preso visione dell'Informativa Privacy (versione {$version}), relativa ai dati del Committente (Titolare " . config('lab.manufacturer.name') . ") e ai dati del Paziente (Responsabile " . config('lab.manufacturer.name') . " ex Art. 28 GDPR).",
            'next'     => 'Avanti — Contratto',
        ],
    ];
    $isLab = $user->isLaboratorio();
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attivazione profilo · {{ config('app.name') }}</title>
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
        .lg-card { width: min(840px, 100%); background: var(--paper); border: 1px solid var(--line); border-radius: 4px; box-shadow: 0 1px 2px rgb(0 0 0 / 0.05); padding: clamp(24px, 4vw, 44px); display: flex; flex-direction: column; gap: 28px; }

        /* progress */
        .lg-prog { display: flex; flex-direction: column; gap: 14px; }
        .lg-prog__top { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; }
        .lg-prog__num { font-size: 40px; font-weight: 700; line-height: 1; color: var(--ink); font-variant-numeric: tabular-nums; }
        .lg-prog__num small { font-size: 18px; font-weight: 500; color: var(--mute); }
        .lg-prog__title { font-size: 15px; font-weight: 500; color: var(--ink); text-align: right; }
        .lg-prog__bars { display: flex; gap: 6px; }
        .lg-prog__bars i { flex: 1; height: 4px; border-radius: 9999px; background: var(--line); }
        .lg-prog__bars i.done, .lg-prog__bars i.active { background: var(--accent); }
        .lg-prog__labels { display: flex; gap: 6px; }
        .lg-prog__labels div { flex: 1; display: flex; align-items: center; gap: 5px; font-size: 11.5px; color: var(--mute); min-width: 0; }
        .lg-prog__labels div.done { color: var(--ink); }
        .lg-prog__labels div.active { color: var(--ink); font-weight: 700; }
        .lg-prog__labels span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .lg-step { display: flex; flex-direction: column; gap: 20px; }
        .lg-overline { font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 500; color: var(--mute); }
        .lg-h2 { font-size: clamp(26px, 4vw, 32px); line-height: 1.15; letter-spacing: -0.01em; font-weight: 700; margin: 0; }
        .lg-desc { font-size: 15px; line-height: 1.55; color: var(--mute); max-width: 64ch; margin: 0; }

        /* rivedi documenti firmati */
        .lg-review { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 14px; padding: 12px 16px; border: 1px dashed var(--line); border-radius: 6px; background: var(--canvas); }
        .lg-review__lbl { font-size: 12.5px; color: var(--mute); }
        .lg-review__link { font-size: 13px; font-weight: 500; color: var(--accent); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .lg-review__link:hover { text-decoration: underline; }

        /* clausole vessatorie list (step 2) */

        /* scroll box */
        .lg-scroll { position: relative; height: min(44vh, 420px); overflow: hidden auto; border: 1px solid var(--line); border-radius: 6px; padding: 22px 26px; background: var(--canvas); font-size: 13.5px; line-height: 1.7; color: var(--ink); white-space: pre-line; text-align: left; }
        .lg-scroll::-webkit-scrollbar { width: 10px; }
        .lg-scroll::-webkit-scrollbar-thumb { background: #d9d5cd; border-radius: 9999px; border: 3px solid var(--canvas); }
        .lg-scroll::-webkit-scrollbar-track { background: transparent; }

        .lg-hint { display: flex; align-items: center; gap: 8px; font-size: 13px; }
        .lg-hint.scroll { color: var(--mute); }
        .lg-hint.done { color: var(--ink); font-weight: 500; }
        .lg-hint svg { flex: none; }

        /* accept */
        .lg-accept { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; border: 1px solid var(--line); border-radius: 6px; background: var(--canvas); cursor: pointer; transition: border-color 160ms, background 160ms; }
        .lg-accept:has(input:checked) { border-color: var(--accent); background: #fff7f3; }
        .lg-accept input { margin-top: 1px; width: 18px; height: 18px; accent-color: var(--accent); flex: none; cursor: pointer; }
        .lg-accept span { font-size: 14px; line-height: 1.5; color: var(--ink); }

        .lg-err { font-size: 13px; color: var(--error); margin: 2px 0 0; }
        .lg-alert { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; background: #f8e1df; border: 1px solid #ecc3c0; border-radius: 6px; font-size: 14px; color: var(--ink); }
        .lg-alert svg { flex: none; color: var(--error); margin-top: 1px; }

        /* footer actions */
        .lg-actions { display: flex; align-items: center; justify-content: flex-end; gap: 14px; flex-wrap: wrap; border-top: 1px solid var(--line); padding-top: 20px; }
        .lg-note { font-size: 12px; color: var(--mute); max-width: 32ch; text-align: right; }
        .lg-btn { background: var(--accent); color: var(--ink); border: none; border-radius: 9999px; padding: 0 24px; height: 46px; font-family: inherit; font-weight: 500; font-size: 15px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; transition: background 160ms, opacity 160ms; }
        .lg-btn:hover:not(:disabled) { background: #ed4f23; }
        .lg-btn:active:not(:disabled) { transform: scale(0.99); }
        .lg-btn:disabled { opacity: 0.45; cursor: not-allowed; }

        /* form (step 4) */
        .lg-section { display: flex; flex-direction: column; gap: 14px; }
        .lg-section h3 { font-size: 12px; letter-spacing: 0.08em; text-transform: uppercase; font-weight: 500; color: var(--accent); margin: 0; padding-left: 12px; border-left: 3px solid var(--accent); }
        .lg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .lg-field { display: flex; flex-direction: column; gap: 6px; }
        .lg-field.full { grid-column: span 2; }
        .lg-field label { font-size: 13px; font-weight: 500; color: var(--ink); }
        .lg-input { height: 44px; padding: 0 14px; border-radius: 6px; border: 1px solid var(--line); background: var(--paper); font-family: inherit; font-size: 15px; color: var(--ink); outline: none; width: 100%; transition: border-color 160ms, box-shadow 160ms; }
        .lg-input:focus { border-color: var(--accent); box-shadow: 0 0 0 2px rgb(255 96 55 / 0.18); }

        @media (max-width: 560px) { .lg-grid { grid-template-columns: 1fr; } .lg-field.full { grid-column: span 1; } .lg-prog__title { display: none; } }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>
</head>
<body>
    <header class="lg-head">
        <span class="lg-brand">
            <img src="/ds/mark.svg" alt="{{ config('app.name') }}">
            <span>{{ config('app.name') }}</span>
        </span>
        <span class="lg-head__ctx">{{ \Illuminate\Support\Str::upper($progress[$step] ?? '') }}</span>
    </header>

    <main class="lg-main">
        <div class="lg-card">

            {{-- PROGRESS --}}
            <div class="lg-prog">
                <div class="lg-prog__top">
                    <div class="lg-prog__num">{{ str_pad((string) $step, 2, '0', STR_PAD_LEFT) }} <small>/ 04</small></div>
                    <div class="lg-prog__title">{{ $titles[$step] ?? '' }}</div>
                </div>
                <div class="lg-prog__bars">
                    @foreach ($progress as $n => $lbl)
                        <i class="{{ $n < $step ? 'done' : ($n === $step ? 'active' : '') }}"></i>
                    @endforeach
                </div>
                <div class="lg-prog__labels">
                    @foreach ($progress as $n => $lbl)
                        <div class="{{ $n < $step ? 'done' : ($n === $step ? 'active' : '') }}">
                            @if ($n < $step)
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            @endif
                            <span>{{ $lbl }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($step > 1)
                <div class="lg-review">
                    <span class="lg-review__lbl">Rivedi i documenti già firmati:</span>
                    @foreach (array_slice($progress, 0, $step - 1, true) as $n => $lbl)
                        <a class="lg-review__link" href="{{ route('legal.review', ['step' => $n]) }}" target="_blank" rel="noopener">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            {{ $lbl }}
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($errors->any())
                <div class="lg-alert" role="alert">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m10.3 3.2-8 13.8A2 2 0 0 0 4 20h16a2 2 0 0 0 1.7-3L13.7 3.2a2 2 0 0 0-3.4 0Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>Verifica i campi evidenziati per proseguire.</span>
                </div>
            @endif

            <form action="{{ route('legal.store') }}" method="POST">
                @csrf

                {{-- ============ STEP LEGALI (1-3) ============ --}}
                @if ($step <= 3)
                    @php $m = $legal[$step]; @endphp
                    <div class="lg-step">
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <span class="lg-overline">{{ $m['overline'] }}</span>
                            <h2 class="lg-h2">{{ $titles[$step] }}</h2>
                            <p class="lg-desc">{{ $m['desc'] }}</p>
                        </div>

                        <div class="lg-scroll" id="lg-scroll">{{ $text }}</div>

                        <div class="lg-hint scroll" id="lg-hint-scroll">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                            Scorri il testo fino in fondo per abilitare l'accettazione.
                        </div>
                        <div class="lg-hint done" id="lg-hint-done" style="display:none;">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Hai letto l'intero testo.
                        </div>

                        <label class="lg-accept">
                            <input type="checkbox" class="js-gate" name="{{ $m['field'] }}" value="1" required disabled @checked(old($m['field']))>
                            <span>{{ $m['accept'] }}</span>
                        </label>
                        @error($m['field']) <div class="lg-err">{{ $message }}</div> @enderror

                        <div class="lg-actions">
                            <button type="submit" class="lg-btn" id="lg-submit" disabled>
                                {{ $m['next'] }}
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- ============ STEP 4 — Contratto + dati + DPA ============ --}}
                @if ($step === 4)
                    <div class="lg-step">
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            <span class="lg-overline">Passo 4 di 4 · Contratto</span>
                            <h2 class="lg-h2">{{ $titles[4] }}</h2>
                            <p class="lg-desc">Completa i dati della tua struttura e sottoscrivi l'accordo sul trattamento dei dati (DPA, Art. 28 GDPR) per attivare l'account.</p>
                        </div>

                        <div class="lg-section">
                            <h3>Dati fiscali e fatturazione</h3>
                            <div class="lg-grid">
                                <div class="lg-field full">
                                    <label for="f-name">Ragione sociale studio / società</label>
                                    <input class="lg-input" id="f-name" type="text" name="name" value="{{ old('name', $studio->name ?? $user->displayname) }}" required>
                                    @error('name') <div class="lg-err">{{ $message }}</div> @enderror
                                </div>
                                <div class="lg-field">
                                    <label for="f-vat">Partita IVA</label>
                                    <input class="lg-input" id="f-vat" type="text" name="vat_number" value="{{ old('vat_number', $studio->vat_number ?? '') }}" placeholder="IT00000000000" required>
                                    @error('vat_number') <div class="lg-err">{{ $message }}</div> @enderror
                                </div>
                                <div class="lg-field">
                                    <label for="f-sdi">Codice SDI</label>
                                    <input class="lg-input" id="f-sdi" type="text" name="sdi_code" value="{{ old('sdi_code', $studio->sdi_code ?? '') }}" placeholder="7 caratteri" maxlength="7" style="text-transform:uppercase;" required>
                                    @error('sdi_code') <div class="lg-err">{{ $message }}</div> @enderror
                                </div>
                                <div class="lg-field full">
                                    <label for="f-pec">Email PEC (fatturazione elettronica)</label>
                                    <input class="lg-input" id="f-pec" type="email" name="pec_email" value="{{ old('pec_email', $studio->pec_email ?? '') }}" placeholder="indirizzo@pec.it" required>
                                    @error('pec_email') <div class="lg-err">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="lg-section">
                            @if ($isLab)
                                <h3>Responsabile tecnico e qualifica</h3>
                                <div class="lg-grid">
                                    <div class="lg-field full">
                                        <label for="f-dn">Nome e cognome responsabile tecnico</label>
                                        <input class="lg-input" id="f-dn" type="text" name="dentist_name" value="{{ old('dentist_name', $studio->dentist_name ?? $user->displayname) }}" required>
                                        @error('dentist_name') <div class="lg-err">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="lg-field full">
                                        <label for="f-itca">Codice ministeriale ITCA (iscrizione fabbricanti)</label>
                                        <input class="lg-input" id="f-itca" type="text" name="itca_code" value="{{ old('itca_code', $studio->itca_code ?? '') }}" placeholder="ITCA-XXXXX" style="text-transform:uppercase;" required>
                                        @error('itca_code') <div class="lg-err">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="lg-field full">
                                        <label for="f-mob">Telefono / mobile di riferimento</label>
                                        <input class="lg-input" id="f-mob" type="text" name="mobile" value="{{ old('mobile', $studio->mobile ?? '') }}" required>
                                        @error('mobile') <div class="lg-err">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            @else
                                <h3>Referente clinico — sede legale</h3>
                                <div class="lg-grid">
                                    <div class="lg-field full">
                                        <label for="f-dn">Nome e cognome medico</label>
                                        <input class="lg-input" id="f-dn" type="text" name="dentist_name" value="{{ old('dentist_name', $studio->dentist_name ?? $user->displayname) }}" required>
                                        @error('dentist_name') <div class="lg-err">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="lg-field">
                                        <label for="f-lic">N. iscrizione albo</label>
                                        <input class="lg-input" id="f-lic" type="text" name="dentist_license" value="{{ old('dentist_license', $studio->dentist_license ?? '') }}" required>
                                        @error('dentist_license') <div class="lg-err">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="lg-field">
                                        <label for="f-albop">Provincia albo</label>
                                        <input class="lg-input" id="f-albop" type="text" name="albo_provincia" value="{{ old('albo_provincia', $studio->albo_provincia ?? '') }}" maxlength="2" style="text-transform:uppercase;" required>
                                        @error('albo_provincia') <div class="lg-err">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="lg-field full">
                                        <label for="f-mob">Telefono / mobile di riferimento</label>
                                        <input class="lg-input" id="f-mob" type="text" name="mobile" value="{{ old('mobile', $studio->mobile ?? '') }}" required>
                                        @error('mobile') <div class="lg-err">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="lg-section">
                            <h3>Ubicazione sede legale</h3>
                            <div class="lg-grid">
                                <div class="lg-field full">
                                    <label for="f-addr">Indirizzo completo</label>
                                    <input class="lg-input" id="f-addr" type="text" name="address" value="{{ old('address', $studio->address ?? '') }}" required>
                                    @error('address') <div class="lg-err">{{ $message }}</div> @enderror
                                </div>
                                <div class="lg-field">
                                    <label for="f-city">Città</label>
                                    <input class="lg-input" id="f-city" type="text" name="city" value="{{ old('city', $studio->city ?? '') }}" required>
                                    @error('city') <div class="lg-err">{{ $message }}</div> @enderror
                                </div>
                                <div class="lg-field">
                                    <label for="f-zip">CAP</label>
                                    <input class="lg-input" id="f-zip" type="text" name="zip_code" value="{{ old('zip_code', $studio->zip_code ?? '') }}" maxlength="5" required>
                                    @error('zip_code') <div class="lg-err">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="lg-section">
                            <h3>Data Processing Agreement — Art. 28 GDPR</h3>
                            <div class="lg-scroll" id="lg-scroll">{{ $text }}</div>

                            <div class="lg-hint scroll" id="lg-hint-scroll">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                                Scorri il DPA fino in fondo per abilitare la sottoscrizione.
                            </div>
                            <div class="lg-hint done" id="lg-hint-done" style="display:none;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                Hai letto l'intero DPA.
                            </div>

                            <label class="lg-accept">
                                <input type="checkbox" class="js-gate" name="dpa_accepted" value="1" required disabled @checked(old('dpa_accepted')) data-preaccepted="{{ old('dpa_accepted') ? '1' : '0' }}">
                                <span>In qualità di <strong>Titolare del Trattamento</strong> dei dati dei pazienti, sottoscrivo il <strong>Data Processing Agreement</strong> con {{ config('lab.manufacturer.name') }} ex Art. 28 GDPR, con autorizzazione generale ai sub-responsabili dell'Allegato A.</span>
                            </label>
                            @error('dpa_accepted') <div class="lg-err">{{ $message }}</div> @enderror
                        </div>

                        <div class="lg-actions">
                            <button type="submit" class="lg-btn" id="lg-submit" disabled>Sottoscrivi e attiva profilo</button>
                        </div>
                    </div>
                @endif

            </form>
        </div>
    </main>

    <script>
    (function () {
        var area   = document.getElementById('lg-scroll');
        var gate   = document.querySelector('.js-gate');
        var submit = document.getElementById('lg-submit');
        var hintS  = document.getElementById('lg-hint-scroll');
        var hintD  = document.getElementById('lg-hint-done');
        if (!gate || !submit) { return; }

        function markRead() {
            gate.disabled = false;
            if (hintS) { hintS.style.display = 'none'; }
            if (hintD) { hintD.style.display = 'flex'; }
        }
        function sync() { submit.disabled = !gate.checked; }

        // Re-render dopo errore di validazione (step 4): se il DPA era già accettato,
        // non costringere a ri-scorrere/ri-accettare.
        if (gate.getAttribute('data-preaccepted') === '1') { markRead(); gate.checked = true; }

        if (area) {
            area.addEventListener('scroll', function () {
                if (area.scrollTop >= area.scrollHeight - area.clientHeight - 24) { markRead(); }
            });
            // testo più corto del box: nessuno scroll necessario
            if (area.scrollHeight <= area.clientHeight + 8) { markRead(); }
        }

        gate.addEventListener('change', sync);
        sync();
    })();
    </script>
</body>
</html>
