<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivio - Registro Storico MDR - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <style>
        body { font-family: 'Ubuntu', sans-serif; }
        .enamel-orange { color: #ff6f3f; }
        .bg-enamel-orange { background-color: #ff6f3f; }
        .border-enamel-orange { border-color: #ff6f3f; }
        .archive-slate { color: #475569; }
        .bg-archive-slate { background-color: #475569; }
        .widget-link:hover { transform: translateY(-3px); }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); } }
        .pulse-pms { animation: pulse-red 2s infinite; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

    <nav class="bg-white border-b-4 border-[#ff6f3f] p-4 shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="text-xl font-bold uppercase tracking-tight text-gray-800 hover:opacity-80 transition">{{ config('app.name') }}</a>
                <span class="bg-slate-100 text-slate-500 text-[9px] font-black px-3 py-1.5 rounded-xl uppercase tracking-widest border border-slate-200">Archivio MDR</span>
            </div>
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
        
        <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div>
                <h1 class="text-4xl font-black uppercase leading-none text-slate-800">Archivio Storico</h1>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Conservazione Legale MDR 2017/745 - Allegato XIII</p>
            </div>
            <div class="bg-white border-2 border-slate-100 px-6 py-4 rounded-[2rem] shadow-sm flex items-center gap-4">
                <div class="text-right">
                    <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest leading-none">Totale Fascicoli</div>
                    <div class="text-2xl font-black text-slate-700 leading-none mt-1">{{ $orders->total() }}</div>
                </div>
                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </div>
            </div>
        </div>

        {{-- FILTRI E RICERCA --}}
        <div class="mb-8 bg-white p-4 rounded-[2rem] shadow-lg border border-gray-100">
            <form action="{{ route('dashboard.archive') }}" method="GET" class="flex flex-col md:flex-row gap-3">
                <div class="flex-1 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cerca Paziente o Codice in Archivio..." class="w-full bg-gray-50 border-none rounded-2xl py-3 pl-12 pr-4 text-xs font-bold focus:ring-2 focus:ring-slate-500 transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <button type="submit" class="bg-slate-800 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all shadow-md">Filtra Archivio</button>
            </form>
        </div>

        <div class="bg-white rounded-3xl shadow-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b bg-gray-50/50">
                            <th class="px-6 py-5">Codice / Archiviazione</th>
                            <th class="px-6 py-5">Rif. Lavoro & Cliente</th>
                            <th class="px-6 py-5 text-center">Dispositivo</th>
                            <th class="px-6 py-5 text-center">Stato Sorveglianza MDR</th>
                            <th class="px-6 py-5 text-center">Fascicolo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($orders as $order)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-mono font-black text-slate-700 uppercase tracking-widest mb-1">{{ $order->code }}</div>
                                <span class="text-[9px] font-black text-slate-400 uppercase">{{ $order->archived_at?->format('d/m/Y H:i') ?? 'N/D' }}</span>
                            </td>
                            <td class="px-6 py-4 leading-tight">
                                <div class="text-sm font-black uppercase text-gray-800 mb-1">{{ $order->patient_ref }}</div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase">{{ $order->studio?->name ?? 'Studio N/D' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-[10px] font-black uppercase text-gray-500 bg-gray-100 px-3 py-1 rounded-lg">
                                    {{ str_replace('_', ' ', $order->device_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                {{-- 🛡️ LOGICA DINAMICA STATO PMS (Art. 83-87 MDR) --}}
                                @php
                                    $statusMap = [
                                        'active' => ['class' => 'text-green-600 bg-green-50 border-green-100', 'label' => 'Sotto Sorveglianza'],
                                        'review_due' => ['class' => 'text-orange-600 bg-orange-50 border-orange-200', 'label' => 'Revisione PSUR'],
                                        'incident_reported' => ['class' => 'text-red-600 bg-red-50 border-red-200 pulse-pms', 'label' => 'Vigilanza Critica'],
                                        'deactivated' => ['class' => 'text-gray-400 bg-gray-50 border-gray-200', 'label' => 'Ciclo Vita Concluso']
                                    ];
                                    $current = $statusMap[$order->pms_status ?? 'active'] ?? $statusMap['active'];
                                @endphp
                                <div class="flex flex-col items-center">
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black border uppercase {{ $current['class'] }}">
                                        {{ $current['label'] }}
                                    </span>
                                    <span class="text-[8px] font-bold text-gray-300 uppercase mt-1.5 tracking-tighter">
                                        Retention: {{ $order->legal_retention_until?->format('Y') ?? ($order->created_at ? $order->created_at->addYears(15)->format('Y') : 'N/D') }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center items-center gap-2">
                                    {{-- Apre il modale usando l'ID locale per il lookup, poi il modale userà il CODE per le rotte --}}
                                    <button onclick="showQuickDetails({{ $order->id }})" class="bg-white border-2 border-slate-200 text-slate-600 hover:bg-slate-800 hover:text-white hover:border-slate-800 px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition flex items-center gap-2 shadow-sm">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Dettagli
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center opacity-20">
                                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                    <span class="text-xs font-black uppercase tracking-[0.3em]">Nessun fascicolo archiviato</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($orders->hasPages())
                <div class="p-4 bg-gray-50 border-t border-gray-100 text-xs">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>

    {{-- MODALE DETTAGLIO (Il file che abbiamo corretto per usare il codice alfanumerico) --}}
    @include('partials.order-detail-modal')

    <script>
        const ordersData = @json($orders->items());
        // currentOrderId viene gestito dal modale
    </script>
</body>
</html>