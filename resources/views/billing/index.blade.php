<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Contabile - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <style>
        body { font-family: 'Ubuntu', sans-serif; }
        .enamel-orange { color: #ff6f3f; }
        .bg-enamel-orange { background-color: #ff6f3f; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 pb-20">

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

    <div class="max-w-6xl mx-auto p-6 md:p-10">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-8">
            <div>
                <h1 class="text-4xl font-black uppercase leading-none text-gray-800">
                    {{ isset($isArchive) && $isArchive ? 'Archivio Fatturati' : 'Da Fatturare' }}
                </h1>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mt-2">
                    {{ isset($isArchive) && $isArchive ? 'Storico globale DDT processati' : 'Riepilogo globale DDT pendenti per Studio' }}
                </p>
                
                {{-- Toggle Navigazione --}}
                <div class="flex gap-4 mt-6">
                    <a href="{{ route('billing.index') }}" class="text-[10px] font-black uppercase tracking-widest {{ (!isset($isArchive) || !$isArchive) ? 'text-emerald-600 border-b-2 border-emerald-600' : 'text-gray-400 hover:text-gray-600' }} pb-1">Pendenze</a>
                    <a href="{{ route('billing.archive') }}" class="text-[10px] font-black uppercase tracking-widest {{ (isset($isArchive) && $isArchive) ? 'text-emerald-600 border-b-2 border-emerald-600' : 'text-gray-400 hover:text-gray-600' }} pb-1">Storico Archivio</a>
                </div>
            </div>
            
            <div class="flex gap-4">
                <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-gray-100 text-center">
                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest">DDT Visualizzati</div>
                    <div class="text-2xl font-black text-emerald-600">{{ $totalShipments }}</div>
                </div>
                <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-gray-100 text-center">
                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Totale Lavori</div>
                    <div class="text-2xl font-black text-gray-800">{{ $totalOrders }}</div>
                </div>
            </div>
        </div>

        {{-- BARRA FILTRI --}}
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-8 flex flex-col md:flex-row items-center gap-4 justify-between">
            <form action="{{ isset($isArchive) && $isArchive ? route('billing.archive') : route('billing.index') }}" method="GET" class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
                
                {{-- FILTRO CLIENTE --}}
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-xl border border-gray-200 w-full md:w-auto">
                    <span class="text-[10px] font-bold uppercase text-gray-400">Cliente</span>
                    <select name="studio_id" class="bg-transparent text-sm font-bold text-gray-800 outline-none cursor-pointer w-full md:min-w-[200px]">
                        <option value="">Tutti i Clienti</option>
                        @foreach($allStudios as $s)
                            <option value="{{ $s->id }}" {{ (isset($currentStudio) && $currentStudio == $s->id) ? 'selected' : '' }}>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- FILTRO PERIODO --}}
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-xl border border-gray-200 w-full md:w-auto">
                    <span class="text-[10px] font-bold uppercase text-gray-400">Periodo</span>
                    
                    <select name="month" class="bg-transparent text-sm font-bold text-gray-800 outline-none cursor-pointer">
                        <option value="">Tutti i mesi</option>
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}" {{ (isset($currentMonth) && $currentMonth == $m) ? 'selected' : '' }}>
                                {{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>

                    <select name="year" class="bg-transparent text-sm font-bold text-gray-800 outline-none cursor-pointer border-l border-gray-200 pl-2">
                        <option value="">Tutti</option>
                        @foreach(range(now()->year, 2024) as $y)
                            <option value="{{ $y }}" {{ (isset($currentYear) && $currentYear == $y) ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-600 transition w-full md:w-auto">Filtra</button>
                @if(request()->has('month') || request()->has('year') || request()->has('studio_id'))
                    <a href="{{ isset($isArchive) && $isArchive ? route('billing.archive') : route('billing.index') }}" class="text-gray-400 hover:text-red-500 text-xs font-bold px-2 whitespace-nowrap">× Reset</a>
                @endif
            </form>
            
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide hidden md:block">
                @if(isset($isArchive) && $isArchive)
                    Filtro su Data Fatturazione
                @else
                    Filtro su Data DDT
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-2xl mb-8 font-bold">
                ✅ {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6">
            @forelse($studios as $studio)
                <div class="bg-white rounded-[2.5rem] shadow-xl border border-gray-100 overflow-hidden transition-all hover:border-emerald-200">
                    <div class="p-8 flex flex-col md:flex-row justify-between items-center gap-6 bg-gray-50/30">
                        
                        <div class="flex-1">
                            <div class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Cliente</div>
                            <h2 class="text-2xl font-black uppercase text-gray-800 leading-tight">{{ $studio->name }}</h2>
                            <div class="flex gap-4 mt-2">
                                <span class="text-[10px] font-bold text-gray-400 uppercase">P.IVA: {{ $studio->vat_number ?? 'N/D' }}</span>
                            </div>
                        </div>

                        <div class="text-center min-w-[160px]">
                            <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest">DDT {{ (isset($isArchive) && $isArchive) ? 'Archiviati' : 'in lista' }}</div>
                            <div class="text-3xl font-black text-gray-800">{{ $studio->deliveryNotes->count() }}</div>
                        </div>

                        <div class="flex flex-col gap-2 w-full md:w-auto">
                            @if(!isset($isArchive) || !$isArchive)
                                <button type="button" onclick="openSelectModal({{ $studio->id }})" class="bg-white border-2 border-gray-200 text-gray-700 px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-gray-800 hover:text-white hover:border-gray-800 transition text-center flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    Genera Riepilogo (PDF)
                                </button>
                                
                                <button onclick="confirmBilling({{ $studio->id }}, '{{ addslashes($studio->name) }}')" class="bg-emerald-600 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-black transition shadow-md">
                                    Segna come Fatturato
                                </button>
                            @else
                                <span class="text-[10px] font-black text-gray-400 uppercase text-center border border-gray-100 rounded-xl py-2 px-4 bg-gray-50/50">Sola Lettura Archivio</span>
                            @endif
                        </div>
                    </div>

                    <div class="px-8 py-6">
                        <div class="flex flex-col gap-2">
                            @foreach($studio->deliveryNotes as $note)
                                <div class="flex justify-between items-center bg-white px-4 py-3 rounded-xl border border-gray-100 hover:border-emerald-100 transition shadow-sm group">
                                    <div class="flex items-center gap-4">
                                        <div class="flex flex-col items-center justify-center w-12 h-12 bg-gray-50 rounded-lg border border-gray-100">
                                            <span class="text-[8px] font-black text-gray-400 uppercase">DDT</span>
                                            <span class="text-xs font-bold text-gray-800">{{ $note->id }}</span>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-gray-800">{{ $note->delivery_date->format('d/m/Y') }}</div>
                                            <div class="text-[10px] text-gray-400 uppercase tracking-wide">
                                                {{ $note->billed_at ? 'Fatturato il ' . $note->billed_at->format('d/m/Y') : 'Via ' . $note->courier }}
                                                @if($note->monthly_summary_ref) | Rif: {{ $note->monthly_summary_ref }} @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-4">
                                        <span class="text-[10px] font-bold text-gray-500 bg-gray-50 px-3 py-1 rounded-full">{{ $note->orders_count }} Lavori</span>
                                        <a href="{{ route('logistics.download', $note->id) }}" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-emerald-500 hover:text-white transition" title="Scarica DDT">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-[3rem] p-20 text-center border-4 border-dashed border-gray-100">
                    <div class="opacity-20 flex flex-col items-center">
                        <svg class="w-20 h-20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <h3 class="text-xl font-black uppercase tracking-widest">Nessun dato</h3>
                        <p class="text-xs font-bold mt-2">Nessun DDT trovato con i filtri correnti.</p>
                        @if(request()->has('month') || request()->has('year') || request()->has('studio_id'))
                            <a href="{{ url()->current() }}" class="mt-4 text-emerald-600 font-bold underline">Rimuovi Filtri</a>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    @if(!isset($isArchive) || !$isArchive)

        {{-- MODALI SELEZIONE DDT (uno per studio) --}}
        @foreach($studios as $studio)
            <div id="selectModal-{{ $studio->id }}" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
                <div class="bg-white rounded-[2.5rem] max-w-lg w-full p-8 shadow-2xl max-h-[90vh] flex flex-col">
                    <div class="mb-5 flex-shrink-0">
                        <h3 class="text-xl font-black uppercase text-gray-800 leading-tight">Seleziona DDT</h3>
                        <p class="text-sm text-gray-500 mt-1">Scegli i DDT da includere nel PDF — <span class="font-bold text-gray-700">{{ $studio->name }}</span></p>
                    </div>

                    <form action="{{ route('billing.summary') }}" method="POST" class="flex flex-col flex-1 min-h-0">
                        @csrf
                        <input type="hidden" name="studio_id" value="{{ $studio->id }}">

                        <div class="mb-3 flex-shrink-0">
                            <button type="button" onclick="toggleAll({{ $studio->id }})"
                                    id="toggleAllBtn-{{ $studio->id }}"
                                    class="text-[10px] font-black uppercase tracking-widest text-emerald-600 hover:text-emerald-800 transition">
                                Deseleziona tutti
                            </button>
                        </div>

                        <div class="overflow-y-auto flex-1 space-y-2 pr-1 mb-6">
                            @foreach($studio->deliveryNotes as $note)
                                <label class="flex items-center gap-3 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100 hover:border-emerald-200 cursor-pointer transition group">
                                    <input type="checkbox" name="note_ids[]" value="{{ $note->id }}" checked
                                           class="w-4 h-4 accent-emerald-600 flex-shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[9px] font-black text-gray-400 uppercase">DDT</span>
                                            <span class="text-xs font-bold text-gray-800">#{{ $note->id }}</span>
                                            <span class="text-[10px] text-gray-500">{{ $note->delivery_date->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 uppercase tracking-wide mt-0.5">
                                            {{ $note->courier }} — {{ $note->orders_count }} lavori
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex gap-3 flex-shrink-0">
                            <button type="button" onclick="closeSelectModal({{ $studio->id }})"
                                    class="flex-1 bg-gray-100 text-gray-500 py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-gray-200 transition">
                                Annulla
                            </button>
                            <button type="submit"
                                    class="flex-1 bg-gray-800 text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-black transition shadow-lg flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                Genera PDF Riepilogo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach

        <div id="billingModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
            <div class="bg-white rounded-[2.5rem] max-w-md w-full p-8 shadow-2xl">
                <h3 class="text-2xl font-black uppercase text-gray-800 mb-2">Conferma Fatturazione</h3>
                <p class="text-sm text-gray-500 mb-6">Stai per archiviare <strong>TUTTI i DDT pendenti</strong> per lo studio <span id="modalStudioName" class="text-emerald-600 font-bold"></span>.</p>
                <form action="{{ route('billing.markBilled') }}" method="POST">
                    @csrf
                    <input type="hidden" name="studio_id" id="modalStudioId">
                    <div class="mb-6">
                        <label class="text-[10px] font-black text-gray-400 uppercase block mb-2">Rif. Fattura Esterna (Opzionale)</label>
                        <input type="text" name="invoice_number" placeholder="Es. FT/2026/001" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 text-gray-500 py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-gray-200 transition">Annulla</button>
                        <button type="submit" class="flex-1 bg-emerald-600 text-white py-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-black transition shadow-lg">Conferma Chiusura</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        function confirmBilling(id, name) {
            document.getElementById('modalStudioId').value = id;
            document.getElementById('modalStudioName').innerText = name;
            document.getElementById('billingModal').classList.remove('hidden');
        }
        function closeModal() {
            document.getElementById('billingModal').classList.add('hidden');
        }

        function openSelectModal(studioId) {
            document.getElementById('selectModal-' + studioId).classList.remove('hidden');
        }
        function closeSelectModal(studioId) {
            document.getElementById('selectModal-' + studioId).classList.add('hidden');
        }
        function toggleAll(studioId) {
            const modal = document.getElementById('selectModal-' + studioId);
            const boxes = modal.querySelectorAll('input[type="checkbox"]');
            const btn = document.getElementById('toggleAllBtn-' + studioId);
            const allChecked = Array.from(boxes).every(b => b.checked);
            boxes.forEach(b => b.checked = !allChecked);
            btn.textContent = allChecked ? 'Seleziona tutti' : 'Deseleziona tutti';
        }
    </script>
</body>
</html>