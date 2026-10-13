@php
    use Carbon\Carbon;
    
    $contracts = [];
    if (isset($studio) && $studio) {
        $contracts = [
            'tos'     => ['label' => 'Termini di Servizio MDR', 'date' => $studio->tos_accepted_at],
            'privacy' => ['label' => 'Informativa Privacy', 'date' => $studio->privacy_accepted_at],
            'dpa'     => ['label' => 'Nomina Responsabile Dati', 'date' => $studio->dpa_accepted_at],
        ];
    }
@endphp

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilo Professionale - {{ config('app.name') }}</title>
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

    <div class="max-w-7xl mx-auto p-6 md:p-10">
        
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-800 text-[10px] font-black uppercase rounded-2xl">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-800 text-[10px] font-black uppercase rounded-2xl">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- SIDEBAR --}}
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-200">
                    <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4">Sorgente Identità</h3>
                    <div class="space-y-4">
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase leading-none mb-1">UID Cloud</label>
                            <span class="text-xs font-mono font-bold text-gray-800">{{ $user->uid }}</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                            <label class="block text-[9px] font-bold text-gray-400 uppercase leading-none mb-1">Ruolo Sistema</label>
                            <span class="text-xs font-bold {{ $user->isStaff() ? 'text-blue-600' : ($user->isLaboratorio() ? 'text-purple-600' : 'text-orange-500') }} uppercase">
                                @if($user->isStaff()) STAFF LABORATORIO @elseif($user->isLaboratorio()) LABORATORIO PARTNER @else MEDICO PRESCRITTORE @endif
                            </span>
                        </div>
                    </div>
                </div>

                @if($studio)
                <div class="bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-gray-800">
                    <div class="p-6 bg-gray-800/50 flex items-center gap-4 border-b border-gray-700">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white shadow-lg shadow-green-900/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-black text-white uppercase tracking-widest">Compliance MDR</h3>
                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-tighter">Protocollo Attivo v.{{ $studio->legal_version }}</p>
                        </div>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="space-y-5">
                            @foreach($contracts as $type => $data)
                            <div class="border-l-2 border-gray-700 pl-4 py-1">
                                <p class="text-[10px] font-black text-gray-300 uppercase leading-none mb-2">{{ $data['label'] }}</p>
                                <p class="text-[9px] text-gray-500 font-mono mb-3 italic">
                                    Firmato: {{ $data['date'] ? Carbon::parse($data['date'])->format('d/m/Y H:i') : 'N/D' }}
                                </p>
                                <a href="{{ route('profile.contract', $type) }}" target="_blank" 
                                   class="inline-block bg-gray-800 hover:bg-[#ff6f3f] text-gray-300 hover:text-white px-4 py-2 rounded-lg text-[9px] font-black uppercase transition-all tracking-widest border border-gray-700 hover:border-[#ff6f3f]">
                                    Visualizza Documento
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- MAIN CONTENT --}}
            <div class="lg:col-span-2 space-y-8">
                
                @if($studio)
                {{-- SEZIONE 1: ANAGRAFICA FISCALE E CLINICA MASTER --}}
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h2 class="text-sm font-black text-gray-800 uppercase tracking-widest">Anagrafica Studio e Sede Legale</h2>
                        <span class="text-[10px] bg-gray-100 px-2 py-1 rounded font-bold text-gray-400 uppercase tracking-tighter italic">Dati Master</span>
                    </div>

                    <form action="{{ route('profile.update') }}" method="POST" class="p-8">
                        @csrf @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Ragione Sociale / Società</label>
                                <input type="text" name="name" value="{{ old('name', $studio->name) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Partita IVA</label>
                                <input type="text" name="vat_number" value="{{ old('vat_number', $studio->vat_number) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Codice SDI</label>
                                <input type="text" name="sdi_code" value="{{ old('sdi_code', $studio->sdi_code) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" required maxlength="7">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">PEC Fatturazione</label>
                                <input type="email" name="pec_email" value="{{ old('pec_email', $studio->pec_email) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" required>
                            </div>

                            <div class="md:col-span-2 pt-4 border-t border-dashed border-gray-200 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-4">
                                    <h4 class="text-[10px] font-black {{ $user->isLaboratorio() ? 'text-purple-600' : 'text-blue-600' }} uppercase mb-2 tracking-widest italic">
                                        {{ $user->isLaboratorio() ? 'Responsabile Tecnico e Qualifiche' : 'Referente Clinico e Contatto Sede Legale' }}
                                    </h4>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">{{ $user->isLaboratorio() ? 'Nome Responsabile' : 'Nome Medico' }}</label>
                                    <input type="text" name="dentist_name" value="{{ old('dentist_name', $studio->dentist_name) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" required>
                                </div>
                                @if($user->isLaboratorio())
                                    <div class="md:col-span-2">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Codice Ministeriale ITCA</label>
                                        <input type="text" name="itca_code" value="{{ old('itca_code', $studio->itca_code) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold outline-none uppercase" required>
                                    </div>
                                    <div class="md:col-span-4">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Telefono / Mobile di Riferimento</label>
                                        <input type="text" name="mobile" value="{{ old('mobile', $studio->mobile) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" placeholder="Es. +39 0771 ..." required>
                                    </div>
                                @else
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">N. Iscrizione Albo</label>
                                        <input type="text" name="dentist_license" value="{{ old('dentist_license', $studio->dentist_license) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" required>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Provincia</label>
                                        <input type="text" name="albo_provincia" value="{{ old('albo_provincia', $studio->albo_provincia) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none uppercase" maxlength="2" required>
                                    </div>
                                    <div class="md:col-span-4">
                                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Telefono / Mobile di Riferimento</label>
                                        <input type="text" name="mobile" value="{{ old('mobile', $studio->mobile) }}" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" placeholder="Es. +39 0771 ..." required>
                                    </div>
                                @endif
                            </div>

                            <div class="md:col-span-2 pt-4 border-t border-dashed border-gray-200">
                                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Indirizzo Sede Legale</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <input type="text" name="address" value="{{ old('address', $studio->address) }}" class="col-span-2 bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" placeholder="Indirizzo" required>
                                    <input type="text" name="zip_code" value="{{ old('zip_code', $studio->zip_code) }}" class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" placeholder="CAP" required>
                                    <input type="text" name="city" value="{{ old('city', $studio->city) }}" class="col-span-3 bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none" placeholder="Città" required>
                                </div>
                            </div>

                            <div class="md:col-span-2 pt-4 flex justify-end">
                                <button type="submit" class="bg-enamel-orange text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase shadow-lg hover:opacity-90 transition tracking-widest">
                                    Aggiorna Dati Master
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                {{-- SEZIONE 2: SEDI OPERATIVE E PRESCRITTORI --}}
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-900 flex justify-between items-center">
                        <h2 class="text-sm font-black text-white uppercase tracking-widest">Sedi Operative e Prescrittori</h2>
                        <span class="text-[9px] text-orange-400 font-bold uppercase tracking-widest">MDR Clinical Locations</span>
                    </div>

                    <div class="p-8">
                        <div class="space-y-4 mb-10">
                            @foreach($studio->addresses as $addr)
                            <div class="p-5 bg-gray-50 rounded-2xl border border-gray-200 flex justify-between items-center shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-black text-gray-800 uppercase tracking-tight">{{ $addr->label }}</span>
                                            @if($addr->is_main)
                                                <span class="text-[8px] bg-blue-600 text-white px-2 py-0.5 rounded font-black uppercase tracking-widest leading-none">Sede Principale</span>
                                            @endif
                                        </div>
                                        <p class="text-[10px] text-gray-500 font-medium leading-none mb-3">
                                            {{ $addr->address }} — {{ $addr->zip_code }} {{ $addr->city }}
                                        </p>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                                            <span class="text-[9px] bg-gray-200 text-gray-800 px-2 py-0.5 rounded font-black uppercase tracking-tighter">
                                                {{ $user->isLaboratorio() ? 'Resp. Tecnico' : 'Medico' }}: {{ $addr->dentist_name ?: ($studio->dentist_name) }}
                                            </span>
                                            @if(!$user->isLaboratorio())
                                            <span class="text-[9px] text-gray-500 font-mono">
                                                Albo: {{ $addr->dentist_license ?: ($studio->dentist_license) }} ({{ $addr->albo_provincia ?: ($studio->albo_provincia) }})
                                            </span>
                                            @endif
                                            <span class="text-[9px] enamel-orange font-black uppercase tracking-tighter">
                                                Tel: {{ $addr->phone ?? ($studio->mobile) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                @if(!$addr->is_main)
                                <form action="{{ route('addresses.destroy', $addr->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-300 hover:text-red-500 transition p-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                            @endforeach
                        </div>

                        <form action="{{ route('addresses.store') }}" method="POST" class="bg-gray-50/50 p-6 rounded-2xl border-2 border-dashed border-gray-200">
                            @csrf
                            <h4 class="text-[11px] font-black enamel-orange uppercase mb-6 text-center tracking-widest">Registra Nuova Sede e Referente</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="text-[9px] font-bold text-gray-400 uppercase ml-2">Etichetta Sede</label>
                                    <input type="text" name="label" class="w-full bg-white border border-gray-200 rounded-xl p-3 text-xs font-bold outline-none focus:ring-2 focus:ring-[#ff6f3f] shadow-sm" placeholder="es. Filiale Milano" required>
                                </div>
                                
                                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-2">
                                    <div class="md:col-span-2 text-[9px] font-black text-blue-600 uppercase mb-1 italic">
                                        {{ $user->isLaboratorio() ? 'Dati Responsabile Tecnico Sede' : 'Dati Professionali Medico Referente Sede' }}
                                    </div>
                                    <div class="md:col-span-2">
                                        <input type="text" name="dentist_name" placeholder="Nome e Cognome" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs font-bold outline-none focus:ring-2 focus:ring-[#ff6f3f]" required>
                                    </div>
                                    @if($user->isLaboratorio())
                                        {{-- Per i laboratori, nascondiamo i campi medici nell'aggiunta sedi secondarie (useranno i placeholder automatici del controller) --}}
                                        <input type="hidden" name="dentist_license" value="LAB-PARTNER">
                                        <input type="hidden" name="albo_provincia" value="NA">
                                        <div class="md:col-span-2">
                                            <input type="text" name="phone" placeholder="Tel." class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs font-bold outline-none focus:ring-2 focus:ring-[#ff6f3f]" required>
                                        </div>
                                    @else
                                        <input type="text" name="dentist_license" placeholder="N. Albo" class="bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs font-bold outline-none focus:ring-2 focus:ring-[#ff6f3f]" required>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" name="albo_provincia" maxlength="2" placeholder="Prov." class="bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs font-bold outline-none focus:ring-2 focus:ring-[#ff6f3f] uppercase" required>
                                            <input type="text" name="phone" placeholder="Tel." class="bg-gray-50 border border-gray-200 rounded-lg p-2 text-xs font-bold outline-none focus:ring-2 focus:ring-[#ff6f3f]" required>
                                        </div>
                                    @endif
                                </div>

                                <input type="text" name="address" placeholder="Indirizzo Sede" class="bg-white border border-gray-200 rounded-xl p-3 text-xs font-bold outline-none focus:ring-2 focus:ring-[#ff6f3f] shadow-sm" required>
                                <div class="grid grid-cols-3 gap-2">
                                    <input type="text" name="city" placeholder="Città" class="col-span-2 bg-white border border-gray-200 rounded-xl p-3 text-xs font-bold outline-none focus:ring-2 focus:ring-[#ff6f3f] shadow-sm" required>
                                    <input type="text" name="zip_code" placeholder="CAP" class="bg-white border border-gray-200 rounded-xl p-3 text-xs font-bold outline-none focus:ring-2 focus:ring-[#ff6f3f] shadow-sm" required>
                                </div>
                                
                                <button type="submit" class="md:col-span-2 bg-gray-900 text-white p-4 rounded-xl font-black text-[10px] uppercase hover:bg-enamel-orange transition tracking-widest shadow-md">
                                    Registra Sede
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @else
                {{-- VISTA ACCOUNT STAFF (Se studio è nullo) --}}
                <div class="bg-white p-12 rounded-2xl shadow-xl border border-gray-200 text-center">
                    <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <h2 class="text-xl font-black text-gray-800 uppercase tracking-tight">Accesso Staff</h2>
                    <p class="text-sm text-gray-400 mt-4 max-w-sm mx-auto leading-relaxed">
                        Questo account ha privilegi di amministrazione e monitoraggio della produzione. 
                        Non richiede la configurazione di uno studio clinico o l'accettazione dei contratti MDR.
                    </p>
                    <div class="mt-8 pt-8 border-t border-gray-100 grid grid-cols-2 gap-4">
                        <div class="text-left p-4 bg-gray-50 rounded-xl">
                            <span class="block text-[10px] font-black text-gray-400 uppercase mb-1">Livello Permessi</span>
                            <span class="text-xs font-bold text-blue-600 uppercase">Master Admin</span>
                        </div>
                        <div class="text-left p-4 bg-gray-50 rounded-xl">
                            <span class="block text-[10px] font-black text-gray-400 uppercase mb-1">Workflow attivo</span>
                            <span class="text-xs font-bold text-gray-800 uppercase">MDR Data Bridge 2.6</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- GDPR: PORTABILITÀ DATI (ART. 20) --}}
                @if(!$isStaff)
                <div class="bg-blue-50 rounded-2xl border border-blue-100 p-8 flex flex-col md:flex-row justify-between items-center gap-6 shadow-inner">
                    <div class="max-w-md text-center md:text-left">
                        <h3 class="text-xs font-black text-blue-800 uppercase tracking-widest mb-2 italic">Portabilità dei Dati (UE 2016/679 Art. 20)</h3>
                        <p class="text-[10px] text-blue-700 leading-tight">Scarica una copia di tutti i tuoi dati personali in formato strutturato: profilo, consensi, ordini e relativi metadati.</p>
                    </div>
                    <a href="{{ route('gdpr.export') }}"
                       class="border-2 border-blue-200 text-blue-600 px-6 py-3 rounded-xl text-[10px] font-black uppercase hover:bg-blue-600 hover:text-white transition tracking-widest">
                        Scarica i Miei Dati
                    </a>
                </div>
                @endif

                {{-- GDPR: OPPOSIZIONE (ART. 21) --}}
                @if(!$isStaff)
                <div x-data="{ open: false }" class="bg-amber-50 rounded-2xl border border-amber-100 p-8 shadow-inner">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="max-w-md text-center md:text-left">
                            <h3 class="text-xs font-black text-amber-800 uppercase tracking-widest mb-2 italic">Opposizione al Trattamento (UE 2016/679 Art. 21)</h3>
                            <p class="text-[10px] text-amber-700 leading-tight">Hai il diritto di opporti al trattamento dei tuoi dati basato su legittimo interesse. Nota: i dati clinici degli ordini sono soggetti a conservazione obbligatoria MDR e non possono essere oggetto di opposizione.</p>
                        </div>
                        <button @click="open = !open"
                                class="border-2 border-amber-200 text-amber-700 px-6 py-3 rounded-xl text-[10px] font-black uppercase hover:bg-amber-600 hover:text-white transition tracking-widest shrink-0">
                            Invia Opposizione
                        </button>
                    </div>

                    {{-- Form opposizione (collassabile) --}}
                    <div x-show="open" x-transition class="mt-6 border-t border-amber-200 pt-6">
                        @if(session('success'))
                            <div class="bg-green-100 text-green-800 rounded-xl p-4 text-xs font-semibold mb-4">
                                {{ session('success') }}
                            </div>
                        @endif
                        <form action="{{ route('gdpr.opposition') }}" method="POST">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-[10px] font-black text-amber-800 uppercase tracking-widest mb-1">Categoria Dati *</label>
                                    <select name="object_category" required
                                            class="w-full border border-amber-200 rounded-xl px-4 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-amber-400">
                                        <option value="">— Seleziona —</option>
                                        <option value="studio">Dati studio / fatturazione</option>
                                        <option value="ordini">Dati ordini (solo metadati non MDR)</option>
                                        <option value="tutto">Tutti i dati non MDR</option>
                                        <option value="altro">Altro (specificare nella motivazione)</option>
                                    </select>
                                    @error('object_category')
                                        <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-[10px] font-black text-amber-800 uppercase tracking-widest mb-1">Motivazione * <span class="text-[9px] font-normal normal-case">(min. 20 caratteri)</span></label>
                                <textarea name="motivation" rows="4" required minlength="20" maxlength="2000"
                                          placeholder="Descrivi il motivo dell'opposizione e i dati specifici oggetto della richiesta..."
                                          class="w-full border border-amber-200 rounded-xl px-4 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none">{{ old('motivation') }}</textarea>
                                @error('motivation')
                                    <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <p class="text-[9px] text-amber-600 mb-4">Riceverai risposta entro 30 giorni all'indirizzo email del tuo account (Art. 12 GDPR). Rif. DPO: {{ config('lab.dpo.email', config('lab.manufacturer.email')) }}</p>
                            <button type="submit"
                                    class="bg-amber-600 text-white px-6 py-3 rounded-xl text-[10px] font-black uppercase hover:bg-amber-700 transition tracking-widest">
                                Invia Richiesta di Opposizione
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                {{-- GDPR: RESTRIZIONE (ART. 18) --}}
                @if(!$isStaff)
                <div x-data="{ open: false }" class="bg-blue-50 rounded-2xl border border-blue-100 p-8 shadow-inner">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                        <div class="max-w-md text-center md:text-left">
                            <h3 class="text-xs font-black text-blue-800 uppercase tracking-widest mb-2 italic">Restrizione del Trattamento (UE 2016/679 Art. 18)</h3>
                            <p class="text-[10px] text-blue-700 leading-tight">Puoi richiedere la restrizione del trattamento dei tuoi dati in caso di contestazione dell'esattezza, trattamento illecito, necessità per difesa giudiziaria o opposizione pendente. I dati restano conservati ma non vengono ulteriormente trattati.</p>
                        </div>
                        <button @click="open = !open"
                                class="border-2 border-blue-200 text-blue-700 px-6 py-3 rounded-xl text-[10px] font-black uppercase hover:bg-blue-600 hover:text-white transition tracking-widest shrink-0">
                            Richiedi Restrizione
                        </button>
                    </div>

                    <div x-show="open" x-transition class="mt-6 border-t border-blue-200 pt-6">
                        @if(session('success') && str_contains(session('success'), 'restrizione'))
                            <div class="bg-green-100 text-green-800 rounded-xl p-4 text-xs font-semibold mb-4">
                                {{ session('success') }}
                            </div>
                        @endif
                        <form action="{{ route('gdpr.restriction') }}" method="POST">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-[10px] font-black text-blue-800 uppercase tracking-widest mb-1">Motivo della Restrizione *</label>
                                    <select name="restriction_reason" required
                                            class="w-full border border-blue-200 rounded-xl px-4 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                                        <option value="">— Seleziona —</option>
                                        <option value="accuracy_dispute">Contesto l'esattezza dei dati (Art. 18(1)(a))</option>
                                        <option value="unlawful_processing">Trattamento illecito, preferisco la restrizione alla cancellazione (Art. 18(1)(b))</option>
                                        <option value="legal_claim">Necessità per accertamento/difesa di un diritto in sede giudiziaria (Art. 18(1)(c))</option>
                                        <option value="opposition_pending">Ho presentato opposizione Art. 21, in attesa di verifica (Art. 18(1)(d))</option>
                                    </select>
                                    @error('restriction_reason')
                                        <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-blue-800 uppercase tracking-widest mb-1">Categoria Dati *</label>
                                    <select name="object_category" required
                                            class="w-full border border-blue-200 rounded-xl px-4 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                                        <option value="">— Seleziona —</option>
                                        <option value="studio">Dati studio / fatturazione</option>
                                        <option value="ordini">Dati ordini (solo metadati non MDR)</option>
                                        <option value="tutto">Tutti i dati non MDR</option>
                                        <option value="altro">Altro (specificare nella motivazione)</option>
                                    </select>
                                    @error('object_category')
                                        <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-[10px] font-black text-blue-800 uppercase tracking-widest mb-1">Motivazione * <span class="text-[9px] font-normal normal-case">(min. 20 caratteri)</span></label>
                                <textarea name="motivation" rows="4" required minlength="20" maxlength="2000"
                                          placeholder="Descrivi le circostanze che giustificano la richiesta di restrizione..."
                                          class="w-full border border-blue-200 rounded-xl px-4 py-2 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none">{{ old('motivation') }}</textarea>
                                @error('motivation')
                                    <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <p class="text-[9px] text-blue-600 mb-4">Riceverai risposta entro 30 giorni (Art. 12 GDPR). La restrizione viene applicata manualmente dallo staff dopo valutazione. Rif.: {{ config('lab.dpo.email', config('lab.manufacturer.email')) }}</p>
                            <button type="submit"
                                    class="bg-blue-600 text-white px-6 py-3 rounded-xl text-[10px] font-black uppercase hover:bg-blue-700 transition tracking-widest">
                                Invia Richiesta di Restrizione
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                {{-- GDPR FOOTER --}}
                <div class="bg-red-50 rounded-2xl border border-red-100 p-8 flex flex-col md:flex-row justify-between items-center gap-6 shadow-inner">
                    <div class="max-w-md text-center md:text-left">
                        <h3 class="text-xs font-black text-red-800 uppercase tracking-widest mb-2 italic">Diritto all'Oblio (UE 2016/679)</h3>
                        <p class="text-[10px] text-red-700 leading-tight">La cancellazione dei dati è soggetta alla conservazione decennale obbligatoria MDR (UE 2017/745) per i dati clinici e tecnici dei dispositivi fabbricati.</p>
                    </div>
                    <form action="{{ route('profile.deletion-request') }}" method="POST" onsubmit="return confirm('Confermi la richiesta di cancellazione? Tutti i tuoi dati verranno marcati per la rimozione automatica dopo i termini di legge.');">
                        @csrf
                        <button type="submit" class="border-2 border-red-200 text-red-600 px-6 py-3 rounded-xl text-[10px] font-black uppercase hover:bg-red-600 hover:text-white transition tracking-widest">
                            Richiedi Rimozione
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>