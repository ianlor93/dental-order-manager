<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redazione Prescrizione MDR - {{ config('app.name') }}</title>
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
                // Logica di distinzione Laboratorio / Clinico
                $studio = $order->studio ?? null;
                $isLab = !empty($studio->itca_code ?? null);
            @endphp

            {{-- Header Documento --}}
            <div class="flex justify-between items-start mb-10 border-b pb-8">
                <div>
                    {{-- TITOLO DINAMICO RICHIESTO --}}
                    <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tighter leading-none">
                        Redazione {{ $isLab ? 'Prescrizione Semilavorato' : 'Prescrizione DMsM' }}
                    </h1>
                    <p class="text-[11px] text-[#ff6f3f] font-black uppercase tracking-widest mt-2">Protocollo MDR 2017/745 - Allegato XIII</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-mono bg-gray-100 text-gray-400 px-3 py-1 rounded-full border border-gray-200 uppercase font-bold">
                        Drafting Mode v1.1
                    </span>
                </div>
            </div>

            {{-- Inizio Form: spostato qui per includere la modifica dell'ipotesi --}}
            <form action="{{ route('orders.prescription.store', $order) }}" method="POST" class="space-y-8">
                @csrf

                {{-- Riepilogo Clinico / Tecnico --}}
                <div class="mb-8 p-6 bg-orange-50/50 border border-orange-100 rounded-2xl">
                    <h3 class="text-[10px] font-black text-orange-400 uppercase tracking-widest mb-3">
                        {{ $isLab ? 'Responsabile Richiedente' : 'Clinico Prescrittore di Riferimento' }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Nome Completo</p>
                            <p class="text-sm font-bold text-gray-800 uppercase">
                                {{ $isLab ? 'Sig.' : 'Dott.' }} 
                                {{ $order->address->dentist_name ?? ($studio->dentist_name ?? 'N/D') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Identificativo {{ $isLab ? 'ITCA' : 'Albo' }}</p>
                            @if($isLab)
                                <p class="text-sm font-bold text-purple-700 font-mono">{{ $studio->itca_code ?? 'N/D' }}</p>
                            @else
                                <p class="text-sm font-bold text-gray-800">
                                    {{ $order->address->dentist_license ?? ($studio->dentist_license ?? 'N/D') }} 
                                    ({{ $order->address->albo_provincia ?? ($studio->albo_provincia ?? '-') }})
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Riepilogo Pratica e Selezione Ipotesi --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-10 p-6 bg-gray-50 rounded-2xl border border-gray-100">
                    <div>
                        <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Codice</label>
                        <p class="text-sm font-bold text-gray-900 uppercase tracking-tighter">{{ $order->code }}</p>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Rif. {{ $isLab ? 'Lavoro' : 'Paziente' }}</label>
                        <p class="text-sm font-bold text-gray-900 uppercase tracking-tighter">{{ $order->patient_ref }}</p>
                    </div>
                    
                    {{-- CAMPO IPOTESI TRATTAMENTO MODIFICABILE --}}
                    <div class="col-span-1">
                        <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Ipotesi Trattamento *</label>
                        <select name="device_type" required class="w-full bg-white border border-gray-200 rounded-lg px-2 py-1 text-[11px] font-bold text-gray-900 focus:ring-2 focus:ring-[#ff6f3f] outline-none shadow-sm transition">
                            <option value="protesi_fissa" {{ $order->device_type == 'protesi_fissa' ? 'selected' : '' }}>Protesi Fissa</option>
                            <option value="protesi_mobile" {{ $order->device_type == 'protesi_mobile' ? 'selected' : '' }}>Protesi Mobile</option>
                            <option value="protesi_ortodontica" {{ $order->device_type == 'protesi_ortodontica' ? 'selected' : '' }}>Protesi Ortodontica</option>
                            <option value="chirurgia" {{ $order->device_type == 'chirurgia' ? 'selected' : '' }}>Chirurgia</option>
                            <option value="altro" {{ $order->device_type == 'altro' ? 'selected' : '' }}>Altro</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Data Richiesta</label>
                        <p class="text-sm font-bold text-gray-900 uppercase tracking-tighter">{{ $order->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>

                {{-- Campi Professionali --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2">Qualifica Professionale {{ $isLab ? 'Responsabile' : 'Prescrittore' }} *</label>
                        <input type="text" name="prescriber_qualification" required 
                               value="{{ $order->prescriber_qualification ?? ($isLab ? 'Odontotecnico' : 'Odontoiatra') }}" 
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none transition shadow-sm">
                        <p class="text-[9px] text-gray-400 mt-2 italic">Esempio: Odontoiatra, Medico Chirurgo, Odontotecnico</p>
                    </div>

                    <div>
                        <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2">Ente / Struttura</label>
                        <input type="text" name="sanitary_institution" 
                               value="{{ $order->sanitary_institution ?? ($studio->name ?? '') }}" 
                               placeholder="Nome della struttura" 
                               class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none transition shadow-sm">
                        <p class="text-[9px] text-gray-400 mt-2 italic">Obbligatorio se si opera in una struttura</p>
                    </div>
                </div>

                {{-- Caratteristiche Specifiche di Progettazione --}}
                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2">Caratteristiche Specifiche di Progettazione *</label>
                    <textarea name="design_features" rows="6" required 
                              placeholder="Descrivere dimensioni, spessori, misure e caratteristiche funzionali necessarie..."
                              class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm font-medium leading-relaxed focus:ring-2 focus:ring-[#ff6f3f] outline-none transition shadow-sm">{{ $order->design_features }}</textarea>
                    <div class="mt-3 p-4 bg-orange-50 rounded-xl border border-orange-100">
                        <p class="text-[10px] text-orange-700 font-medium leading-tight">
                            <strong>Nota Normativa:</strong> Indicare le caratteristiche necessarie sotto la responsabilità del {{ $isLab ? 'richiedente' : 'prescrittore' }} per rispondere alle condizioni individuali del {{ $isLab ? 'caso' : 'paziente' }}.
                        </p>
                    </div>
                </div>

                {{-- Azioni di Invio --}}
                <div class="flex justify-between items-center pt-10 border-t border-gray-100">
                    <div class="max-w-xs text-[10px] text-gray-400 font-bold leading-tight uppercase">
                        Stato Attuale: <span class="text-orange-500 tracking-tighter font-black">Redazione Bozza Legale</span><br>
                        Tracciabilità: {{ $order->patient_ref }}
                    </div>
                    <button type="submit" class="bg-[#ff6f3f] text-white px-12 py-5 rounded-2xl font-black text-[12px] uppercase shadow-2xl hover:opacity-90 transition transform hover:-translate-y-1 tracking-widest">
                        Invia al {{ $isLab ? 'Responsabile' : 'Clinico' }} per Revisione
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>