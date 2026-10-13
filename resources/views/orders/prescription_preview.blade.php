<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisione Prescrizione MDR - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <style>
        body { font-family: 'Ubuntu', sans-serif; }
        .enamel-orange { color: #ff6f3f; }
        .bg-enamel-orange { background-color: #ff6f3f; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

    <nav class="bg-white border-b-4 border-[#ff6f3f] p-4 shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="{{ route('dashboard') }}" class="text-xl font-bold uppercase tracking-tight text-gray-800 hover:opacity-80 transition">{{ config('app.name') }}</a>
            <div class="flex items-center gap-4">
                @if(\App\Support\AccessContext::for($user)->isBackOffice())
                    <a href="{{ route('customers.index') }}" class="bg-indigo-50 text-indigo-700 px-4 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition shadow-sm border border-indigo-100">Anagrafica</a>
                    <a href="{{ route('logistics.index') }}" class="bg-gray-800 text-white px-4 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition shadow-sm border border-gray-700 flex items-center gap-2">
                        <svg class="w-3 h-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> Logistica
                    </a>
                    <a href="{{ route('billing.index') }}" class="bg-emerald-100 text-emerald-700 border border-emerald-200 px-4 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-emerald-600 hover:text-white transition shadow-sm flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg> Contabilità
                    </a>
                    <a href="{{ route('amministrazione.index') }}" class="bg-amber-50 text-amber-700 border border-amber-200 px-4 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-amber-600 hover:text-white transition shadow-sm flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg> Amministrazione
                    </a>
                @endif
                <div class="h-8 w-px bg-gray-200 mx-2"></div>
                <a href="{{ route('dashboard.archive') }}" class="bg-gray-100 text-gray-600 px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-gray-800 hover:text-white transition shadow-sm">Archivio</a>
                <a href="{{ route('profile.show') }}" class="text-right flex flex-col items-end group ml-4">
                    <div class="text-xs font-black uppercase text-gray-800">{{ $user->displayname ?? $user->uid }}</div>
                    <div class="mt-1">
                        <span class="{{ $user->isStaff() ? 'bg-gray-800' : ($user->isLaboratorio() ? 'bg-purple-600' : 'bg-enamel-orange') }} text-white text-[8px] font-black px-2 py-0.5 rounded tracking-[0.1em] uppercase">
                            @if($user->isStaff()) Staff @elseif($user->isLaboratorio()) Laboratorio @else Studio @endif
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-6 md:p-10">

        <div class="bg-white p-8 md:p-12 rounded-3xl shadow-2xl border-t-8 border-[#ff6f3f]">
            
            @php
                // --- LOGICA DI RECUPERO DATI (MULTI-SEDE & LAB) ---
                
                // 1. Studio e Lab Check
                $studio = $order->studio; // Assumiamo che esista sempre (relazione obbligatoria)
                $itcaCode = $studio->itca_code ?? null;
                $isLab = !empty($itcaCode);

                // 2. Determinazione Sede (Logica Multi-Sede)
                // Se c'è un address_id, usiamo quello (Sede secondaria), altrimenti usiamo lo Studio (Sede principale)
                $sede = $order->address ?? $studio;

                // 3. Costruzione Dati Ente
                // Nome: Se è una sede secondaria, aggiungiamo la label (es. "Studio Rossi - Sede Milano")
                $nomeStruttura = $studio->name;
                if($order->address) {
                    $nomeStruttura .= ' (' . $order->address->label . ')';
                }

                // Indirizzo Completo della Sede specifica
                $indirizzoStruttura = $sede->address . ' - ' . $sede->zip_code . ' ' . $sede->city . ' (' . ($sede->province ?? '-') . ')';
                $pivaStruttura = $studio->vat_number; // La P.IVA è solitamente unica per lo studio

                // 4. Dati Prescrittore (Override per sede o Default Studio)
                $nomePrescrittore = $order->address->dentist_name ?? ($studio->dentist_name ?? 'N/D');
                $licenzaPrescrittore = $order->address->dentist_license ?? ($studio->dentist_license ?? 'N/D');
                $provinciaAlbo = $order->address->albo_provincia ?? ($studio->albo_provincia ?? '-');
            @endphp

            {{-- Header Documento --}}
            <div class="flex justify-between items-start mb-10 border-b pb-8">
                <div>
                    {{-- TITOLI CORRETTI COME RICHIESTO --}}
                    <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tighter leading-none">
                        Revisione {{ $isLab ? 'Prescrizione Semilavorato' : 'Prescrizione DMsM' }}
                    </h1>
                    <p class="text-[11px] text-[#ff6f3f] font-black uppercase tracking-widest mt-2">Documento ai sensi del Regolamento (UE) 2017/745</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-mono bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full border border-orange-200 uppercase font-bold animate-pulse">
                        In attesa di convalida
                    </span>
                </div>
            </div>

            {{-- Identificazione del Prescrittore e Istituzione --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                
                {{-- BOX PRESCRITTORE --}}
                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100">
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ $isLab ? 'Responsabile Tecnico' : 'Medico Prescrittore' }}</label>
                    <p class="text-lg font-black text-gray-900 uppercase">{{ $isLab ? 'Sig.' : 'Dott.' }} {{ $nomePrescrittore }}</p>
                    <p class="text-xs font-bold text-gray-500 uppercase mt-1">{{ $isLab ? 'Odontotecnico / Resp. Lab' : $order->prescriber_qualification }}</p>
                    <p class="text-[10px] text-gray-400 font-mono mt-2">
                        @if($isLab)
                            ITCA: <span class="text-purple-600 font-bold">{{ $itcaCode }}</span>
                        @else
                            Albo: {{ $licenzaPrescrittore }} ({{ $provinciaAlbo }})
                        @endif
                    </p>
                </div>

                {{-- BOX ENTE / STRUTTURA (AGGIORNATO CON LOGICA MULTI-SEDE) --}}
                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100 flex flex-col justify-center">
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Ente / Struttura di Provenienza</label>
                    
                    {{-- Nome Studio (+ eventuale Sede) --}}
                    <p class="text-sm font-black text-gray-900 uppercase leading-tight mb-1">
                        {{ $nomeStruttura }}
                    </p>
                    
                    {{-- Indirizzo Fisico della Sede --}}
                    <p class="text-xs font-medium text-gray-600 uppercase mb-2">
                        {{ $indirizzoStruttura }}
                    </p>

                    {{-- Dati Fiscali --}}
                    <div class="mt-auto pt-2 border-t border-gray-200">
                         <p class="text-[10px] text-gray-400 font-mono">
                            P.IVA: {{ $pivaStruttura }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Identificazione Paziente --}}
            <div class="mb-10 p-6 bg-enamel-orange/5 border border-enamel-orange/10 rounded-2xl flex items-center justify-between">
                <div>
                    <label class="block text-[9px] font-black enamel-orange uppercase tracking-widest mb-1">Identificativo {{ $isLab ? 'Lavoro' : 'Paziente' }}</label>
                    <p class="text-2xl font-black text-gray-900 uppercase tracking-tight">{{ $order->patient_ref }}</p>
                </div>
                <div class="text-right">
                    <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Tipo Dispositivo</label>
                    <p class="text-xs font-bold text-gray-700 uppercase">{{ str_replace('_', ' ', $order->device_type) }}</p>
                </div>
            </div>

            {{-- Caratteristiche Specifiche di Progettazione --}}
            <div class="mb-10">
                <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3">Caratteristiche Specifiche di Progettazione</label>
                <div class="w-full bg-white border-2 border-gray-100 rounded-2xl p-8 shadow-inner font-mono text-sm leading-relaxed whitespace-pre-wrap text-gray-800 italic ring-1 ring-gray-50">
                    {{ $order->design_features }}
                </div>
                <div class="mt-4 p-4 bg-blue-50 rounded-xl border border-blue-100 flex gap-3">
                    <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[10px] text-blue-700 font-medium leading-tight text-pretty">
                        Ai sensi dell'Art. 2(3) MDR, il dispositivo è fabbricato sulla base di questa prescrizione scritta che indica, sotto la responsabilità del {{ $isLab ? 'richiedente' : 'prescrittore' }}, le caratteristiche specifiche di progettazione.
                    </p>
                </div>
            </div>

            {{-- Azioni di Convalida --}}
            <div class="pt-10 border-t border-gray-100">
                <form action="{{ route('orders.prescription.validate', $order) }}" method="POST">
                    @csrf
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="max-w-sm">
                            <h4 class="text-xs font-black uppercase text-gray-900 mb-1 italic">Dichiarazione di Responsabilità</h4>
                            <p class="text-[10px] text-gray-400 font-bold leading-tight uppercase">
                                La convalida autentica le specifiche tecniche sopra indicate come rispondenti alle esigenze individuali del {{ $isLab ? 'caso' : 'paziente identificato' }}.
                            </p>
                        </div>
                        <button type="submit" class="w-full md:w-auto bg-gray-900 text-white px-12 py-5 rounded-2xl font-black text-[12px] uppercase shadow-2xl hover:bg-green-600 transition-all transform hover:-translate-y-1 tracking-widest">
                            Convalida e Firma {{ $isLab ? 'Prescrizione' : 'Prescrizione' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Footer Normativo --}}
        <p class="mt-8 text-center text-[9px] text-gray-400 font-medium uppercase tracking-[0.1em]">
            Documentazione conforme all'Allegato XIII MDR (UE) 2017/745 • Trattamento dati ai sensi del GDPR (UE) 2016/679
        </p>
    </div>

</body>
</html>