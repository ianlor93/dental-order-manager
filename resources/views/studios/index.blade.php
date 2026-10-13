<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anagrafica Clienti - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <style>
        body { font-family: 'Ubuntu', sans-serif; }
        .enamel-orange { color: #ff6f3f; }
        .bg-enamel-orange { background-color: #ff6f3f; }
        .border-enamel-orange { border-color: #ff6f3f; }
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
                <a href="{{ route('orders.create') }}" class="bg-enamel-orange text-white px-6 py-2.5 rounded-xl shadow-lg font-black text-xs uppercase tracking-widest hover:scale-105 transition">+ Nuova Richiesta</a>
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

    <div class="max-w-7xl mx-auto p-6 md:p-12">
        
        {{-- Header Professionale --}}
        <div class="flex justify-between items-end mb-10">
            <div>
                <h1 class="text-3xl font-black uppercase tracking-tighter leading-none">Anagrafica <span class="enamel-orange">Clienti</span></h1>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2">Database Fiscale e Clinico - Accesso Amministrativo Riservato</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('dashboard') }}" class="text-[10px] font-black uppercase bg-white px-6 py-3 rounded-2xl shadow-sm border hover:bg-gray-50 transition tracking-widest">Torna al Registro</a>
            </div>
        </div>

        {{-- Widget Riassuntivi --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-[2rem] shadow-xl border-b-4 border-indigo-500">
                <span class="text-[9px] font-black uppercase text-gray-400 tracking-widest">Totale Studi</span>
                <div class="text-3xl font-black text-indigo-600 mt-1">{{ $studios->total() }}</div>
            </div>
            <div class="bg-white p-6 rounded-[2rem] shadow-xl border-b-4 border-green-500">
                <span class="text-[9px] font-black uppercase text-gray-400 tracking-widest">Validati MDR</span>
                <div class="text-3xl font-black text-green-600 mt-1">{{ $studios->where('mdr_accepted_at', '!=', null)->count() }}</div>
            </div>
            <div class="bg-white p-6 rounded-[2rem] shadow-xl border-b-4 border-orange-500">
                <span class="text-[9px] font-black uppercase text-gray-400 tracking-widest">In Onboarding</span>
                <div class="text-3xl font-black text-orange-600 mt-1">{{ $studios->where('mdr_accepted_at', '==', null)->count() }}</div>
            </div>
        </div>

        {{-- Main Table Card --}}
        <div class="bg-white rounded-[2.5rem] shadow-2xl border border-gray-100 overflow-hidden">
            
            {{-- Search Bar --}}
            <div class="p-8 border-b bg-gray-50/50">
                <form action="{{ route('customers.index') }}" method="GET" class="flex gap-4">
                    <div class="flex-1 relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cerca per Nome Studio, Partita IVA o Referente..." class="w-full bg-white border border-gray-200 rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] shadow-sm outline-none transition-all">
                        <svg class="w-5 h-5 text-gray-300 absolute right-6 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <button type="submit" class="bg-gray-900 text-white px-10 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition shadow-lg">Filtra Database</button>
                </form>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] border-b">
                        <tr>
                            <th class="px-10 py-6">Denominazione / Sede Legale</th>
                            <th class="px-10 py-6">Dati Fiscali & SDI</th>
                            <th class="px-10 py-6">Referente / Responsabile</th>
                            <th class="px-10 py-6 text-center">Protocolli</th>
                            <th class="px-10 py-6 text-center">Abbonamento Tier</th>
                            <th class="px-10 py-6">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($studios as $studio)
                        @php
                            $isLab = !empty($studio->itca_code);
                        @endphp
                        <tr class="hover:bg-orange-50/30 transition">
                            <td class="px-10 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $isLab ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' }}">
                                        @if($isLab)
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        @else
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-sm font-black uppercase text-gray-800">{{ $studio->name }}</div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-tight">{{ $studio->city }} ({{ $studio->zip_code }})</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-10 py-6">
                                <div class="text-[11px] font-mono font-bold text-gray-700">P.IVA: {{ $studio->vat_number }}</div>
                                <div class="text-[9px] text-gray-500 font-black mt-1 uppercase tracking-tighter">Codice SDI: <span class="text-indigo-600">{{ $studio->sdi_code ?? 'N/D' }}</span></div>
                            </td>
                            <td class="px-10 py-6">
                                <div class="text-[11px] font-black uppercase text-gray-800">
                                    {{ $isLab ? 'Resp. ' : 'Dott. ' }}{{ $studio->dentist_name }}
                                </div>
                                <div class="text-[9px] text-gray-400 font-bold uppercase">
                                    @if($isLab)
                                        ITCA: <span class="text-purple-600 font-mono">{{ $studio->itca_code }}</span>
                                    @else
                                        Licenza: {{ $studio->dentist_license }} ({{ $studio->albo_provincia }})
                                    @endif
                                </div>
                            </td>
                            <td class="px-10 py-6 text-center">
                                @if($studio->mdr_accepted_at)
                                    <div class="inline-flex flex-col items-center">
                                        <span class="bg-green-100 text-green-700 text-[8px] font-black px-3 py-1.5 rounded-lg border border-green-200 uppercase tracking-widest">Protocollo Attivo</span>
                                        <span class="text-[7px] text-gray-400 mt-1 font-bold">{{ \Carbon\Carbon::parse($studio->mdr_accepted_at)->format('d/m/Y') }}</span>
                                    </div>
                                @else
                                    <span class="bg-orange-100 text-orange-700 text-[8px] font-black px-3 py-1.5 rounded-lg border border-orange-200 uppercase tracking-widest">In Attesa</span>
                                @endif
                            </td>
                            
                            {{-- ✨ NUOVA COLONNA: GESTIONE TIER RAPIDA --}}
                            <td class="px-10 py-6 text-center">
                                <form action="{{ route('studios.updateBilling', $studio->id) }}" method="POST">
                                    @csrf
                                    <select name="tier_level" onchange="this.form.submit()" class="text-[10px] font-black uppercase border border-gray-200 rounded-xl px-2 py-1 bg-white focus:ring-2 focus:ring-[#ff6f3f] outline-none">
                                        @foreach(['L0', 'L1', 'L2', 'L3', 'L4'] as $t)
                                            <option value="{{ $t }}" {{ $studio->tier_level == $t ? 'selected' : '' }}>{{ $t }}</option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="subscription_plan" value="{{ $studio->subscription_plan ?? 'monthly' }}">
                                </form>
                            </td>

                            <td class="px-10 py-6">
                                <button onclick='openStudioModal(@json($studio))' class="bg-gray-900 text-white text-[9px] font-black uppercase px-4 py-2 rounded-xl hover:bg-orange-600 transition shadow-sm">
                                    Dettagli
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-10 py-20 text-center">
                                <div class="text-gray-300 font-black uppercase tracking-widest">Nessuno studio trovato</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="p-8 bg-gray-50 border-t">
                {{ $studios->links() }}
            </div>
        </div>
        
        <p class="text-center text-[9px] text-gray-400 font-bold uppercase tracking-[0.3em] mt-10">{{ config('app.name') }} • Security Audit Compliant</p>
    </div>

    {{-- ✨ MODALE ANAGRAFICA COMPLETA --}}
    <div id="studioModal" class="hidden fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-10 border-b flex justify-between items-center bg-gray-50">
                <div>
                    <h2 id="modalTitle" class="text-2xl font-black uppercase text-gray-900 leading-none"></h2>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2">Scheda Integrata Fiscale, Clinica e Billing</p>
                </div>
                <button onclick="closeStudioModal()" class="w-12 h-12 rounded-full bg-white border shadow-sm flex items-center justify-center hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-10 overflow-y-auto flex-1 grid grid-cols-1 md:grid-cols-2 gap-10">
                <div>
                    <h3 class="text-xs font-black uppercase text-orange-500 tracking-widest mb-6">Dati Fiscali & Sede</h3>
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <span class="block text-[8px] font-black text-gray-400 uppercase mb-1">P.IVA / Codice Fiscale</span>
                            <span id="modalVat" class="text-sm font-bold text-gray-800"></span>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <span class="block text-[8px] font-black text-gray-400 uppercase mb-1">Codice SDI</span>
                            <span id="modalSdi" class="text-sm font-bold text-indigo-600"></span>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <span class="block text-[8px] font-black text-gray-400 uppercase mb-1">PEC Email</span>
                            <span id="modalPec" class="text-sm font-bold text-gray-800"></span>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <span class="block text-[8px] font-black text-gray-400 uppercase mb-1">Sede Operativa</span>
                            <span id="modalAddress" class="text-sm font-bold text-gray-800 italic"></span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-black uppercase text-orange-500 tracking-widest mb-6">Dati Clinici & Conformità</h3>
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                            <span class="block text-[8px] font-black text-gray-400 uppercase mb-1">Referente / Iscrizione Albo</span>
                            <span id="modalDentist" class="text-sm font-bold text-gray-800"></span>
                        </div>
                        <div id="modalItcaContainer" class="bg-purple-50 p-4 rounded-2xl border border-purple-100 hidden">
                            <span class="block text-[8px] font-black text-purple-400 uppercase mb-1">Codice ITCA (Fabbricante)</span>
                            <span id="modalItca" class="text-sm font-bold text-purple-700 font-mono"></span>
                        </div>
                        
                        <div class="mt-6 border-t pt-6">
                            <span class="block text-[9px] font-black text-gray-400 uppercase mb-4">Registro Firme Digitali</span>
                            <div class="space-y-3" id="modalSignatures">
                                </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-10 bg-gray-50 border-t flex gap-4">
                <button onclick="closeStudioModal()" class="flex-1 bg-white border border-gray-200 text-gray-400 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-gray-100 transition">Chiudi Scheda</button>
                <button class="flex-1 bg-gray-900 text-white py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-orange-600 transition shadow-lg shadow-orange-100">Genera Report Audit</button>
            </div>
        </div>
    </div>

    <script>
        function openStudioModal(studio) {
            document.getElementById('modalTitle').innerText = studio.name;
            document.getElementById('modalVat').innerText = studio.vat_number + (studio.fiscal_code ? ' / ' + studio.fiscal_code : '');
            document.getElementById('modalSdi').innerText = studio.sdi_code || 'NON CONFIGURATO';
            document.getElementById('modalPec').innerText = studio.pec_email || 'N/D';
            document.getElementById('modalAddress').innerText = studio.address + ', ' + studio.zip_code + ' ' + studio.city + ' (' + (studio.albo_provincia || '') + ')';
            document.getElementById('modalDentist').innerText = studio.dentist_name + (studio.dentist_license ? ' (Lic. ' + studio.dentist_license + ')' : '');

            // Gestione ITCA
            const itcaContainer = document.getElementById('modalItcaContainer');
            if(studio.itca_code) {
                itcaContainer.classList.remove('hidden');
                document.getElementById('modalItca').innerText = studio.itca_code;
            } else {
                itcaContainer.classList.add('hidden');
            }

            // Gestione Firme
            const signatures = [
                { label: 'Privacy Policy', date: studio.privacy_accepted_at },
                { label: 'Termini di Servizio', date: studio.tos_accepted_at },
                { label: 'Nomina DPA', date: studio.dpa_accepted_at },
                { label: 'Protocollo MDR 2017/745', date: studio.mdr_accepted_at }
            ];

            let sigHtml = '';
            signatures.forEach(sig => {
                const dateText = sig.date ? new Date(sig.date).toLocaleDateString('it-IT') : 'NON FIRMATO';
                const color = sig.date ? 'text-green-600' : 'text-red-500';
                const icon = sig.date ? '✓' : '✗';
                sigHtml += `
                    <div class="flex justify-between items-center text-[10px] font-bold uppercase">
                        <span class="text-gray-500">${sig.label}</span>
                        <span class="${color}">${icon} ${dateText}</span>
                    </div>
                `;
            });
            document.getElementById('modalSignatures').innerHTML = sigHtml;

            document.getElementById('studioModal').classList.remove('hidden');
        }

        function closeStudioModal() {
            document.getElementById('studioModal').classList.add('hidden');
        }
    </script>
</body>
</html>