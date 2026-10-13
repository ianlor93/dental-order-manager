<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amministrazione — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <style>
        body { font-family: 'Ubuntu', sans-serif; }
        .enamel-orange { color: #ff6f3f; }
        .bg-enamel-orange { background-color: #ff6f3f; }
        .border-enamel-orange { border-color: #ff6f3f; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    {{-- NAV --}}
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
                    <a href="{{ route('amministrazione.index') }}" class="bg-amber-500 text-white border border-amber-600 px-4 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-sm flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg> Amministrazione
                    </a>
                @endif
                <div class="h-8 w-px bg-gray-200 mx-2"></div>
                <a href="{{ route('dashboard.archive') }}" class="bg-gray-100 text-gray-600 px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-gray-800 hover:text-white transition shadow-sm">Archivio</a>
                <a href="{{ route('orders.create') }}" class="bg-enamel-orange text-white px-6 py-2.5 rounded-xl shadow-lg font-black text-xs uppercase tracking-widest hover:scale-105 transition">+ Nuova Richiesta</a>
                <a href="{{ route('profile.show') }}" class="text-right flex flex-col items-end group ml-4">
                    <div class="text-xs font-black uppercase text-gray-800">{{ $user->displayname ?? $user->uid }}</div>
                    <div class="mt-1">
                        <span class="bg-gray-800 text-white text-[8px] font-black px-2 py-0.5 rounded tracking-[0.1em] uppercase">Staff</span>
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">

        {{-- HEADER --}}
        <div class="mb-8">
            <h1 class="text-2xl font-black uppercase tracking-tight text-gray-900">Amministrazione</h1>
            <p class="text-sm text-gray-500 mt-1">Lead, pre-registrazioni e richieste di assistenza</p>
        </div>

        {{-- FLASH MESSAGE --}}
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium px-4 py-3 rounded-xl">
                {{ session('success') }}
            </div>
        @endif

        {{-- STATS --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="text-3xl font-black text-gray-900">{{ $stats['leads_totali'] }}</div>
                <div class="text-xs font-bold uppercase tracking-widest text-gray-400 mt-1">Lead totali</div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="text-3xl font-black text-indigo-600">{{ $stats['lead_convertiti'] }}</div>
                <div class="text-xs font-bold uppercase tracking-widest text-gray-400 mt-1">Lead convertiti</div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="text-3xl font-black {{ $stats['supporto_aperte'] > 0 ? 'text-red-500' : 'text-gray-300' }}">{{ $stats['supporto_aperte'] }}</div>
                <div class="text-xs font-bold uppercase tracking-widest text-gray-400 mt-1">Ticket aperti</div>
            </div>
        </div>

        {{-- LEAD --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-8">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest text-gray-800">Lead — Pre-registrazioni</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Registrazioni dalla landing page {{ config('lab.landing_url') }}</p>
                </div>
                <span class="text-xs font-bold text-gray-400">{{ $leads->total() }} totali</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Nome</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Email</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Ragione Sociale</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">P.IVA</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Tipo</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Telefono</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Stato</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Registrato</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($leads as $lead)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $lead->nome }}</td>
                                <td class="px-6 py-4 text-gray-600">
                                    <a href="mailto:{{ $lead->email }}" class="hover:text-[#ff6f3f] transition">{{ $lead->email }}</a>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ $lead->studio }}</td>
                                <td class="px-6 py-4 text-gray-500 font-mono text-xs">{{ $lead->piva ?? '—' }}</td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    @switch($lead->tipo_attivita)
                                        @case('studio_odontoiatrico') Studio Odontoiatrico @break
                                        @case('laboratorio_odontotecnico') Laboratorio @break
                                        @case('fornitore_servizi') Agente / Rappresentante @break
                                        @default —
                                    @endswitch
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    <a href="tel:{{ $lead->telefono }}" class="hover:text-[#ff6f3f] transition">{{ $lead->telefono }}</a>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $badge = [
                                            'nuovo'       => ['Nuovo', 'bg-gray-100 text-gray-600'],
                                            'da_vagliare' => ['Da vagliare', 'bg-amber-100 text-amber-700'],
                                            'approvato'   => ['Approvato', 'bg-emerald-100 text-emerald-700'],
                                            'rifiutato'   => ['Rifiutato', 'bg-red-100 text-red-700'],
                                        ][$lead->status] ?? [$lead->status, 'bg-gray-100 text-gray-600'];
                                    @endphp
                                    <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest {{ $badge[1] }} px-2.5 py-1 rounded-full">{{ $badge[0] }}</span>
                                    @if($lead->converted_at)
                                        <span class="block mt-1 text-[10px] font-bold text-indigo-600">Convertito</span>
                                    @endif
                                    @if(in_array($lead->status, ['approvato', 'rifiutato']) && $lead->vetted_at)
                                        <span class="block mt-1 text-[10px] text-gray-400">{{ $lead->vetted_by_uid }} · {{ $lead->vetted_at->format('d/m/Y') }}</span>
                                        @if($lead->status === 'approvato' && $lead->assigned_group)
                                            <span class="block text-[10px] font-bold text-emerald-600">→ {{ $lead->assigned_group }}</span>
                                        @endif
                                        @if($lead->status === 'rifiutato' && $lead->rejection_reason)
                                            <span class="block text-[10px] italic text-gray-400" title="{{ $lead->rejection_reason }}">{{ \Illuminate\Support\Str::limit($lead->rejection_reason, 32) }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-400">{{ $lead->created_at->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if($lead->status === 'da_vagliare')
                                        <div class="flex items-center justify-end gap-2">
                                            <form action="{{ route('amministrazione.lead.approve', $lead) }}" method="POST" class="inline-flex items-center gap-1">
                                                @csrf
                                                <select name="assigned_group" required class="border border-gray-200 rounded-lg px-2 py-1 text-[11px] font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                                                    @foreach(config('funnel.assignable_groups', []) as $gid => $label)
                                                        <option value="{{ $gid }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-emerald-700 hover:text-emerald-900 transition px-2.5 py-1.5 rounded-lg hover:bg-emerald-50">Approva</button>
                                            </form>
                                            <form action="{{ route('amministrazione.lead.reject', $lead) }}" method="POST" class="inline"
                                                  onsubmit="var r=prompt('Motivo del rifiuto per {{ addslashes($lead->email) }}:'); if(r===null||r.trim()===''){return false;} this.rejection_reason.value=r;">
                                                @csrf
                                                <input type="hidden" name="rejection_reason">
                                                <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-red-600 transition px-2.5 py-1.5 rounded-lg hover:bg-red-50">Rifiuta</button>
                                            </form>
                                        </div>
                                    @elseif($lead->status === 'nuovo')
                                        <form action="{{ route('amministrazione.lead.destroy', $lead) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Scartare il lead {{ addslashes($lead->email) }}? Verrà segnato come rifiutato (non eliminato).')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-red-600 transition px-3 py-1.5 rounded-lg hover:bg-red-50">Scarta</button>
                                        </form>
                                    @else
                                        <span class="text-[10px] text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-400">
                                    Nessun lead ancora. I dati appariranno qui non appena qualcuno compila il form su {{ config('lab.landing_url') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if($leads->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $leads->links() }}
                </div>
            @endif
        </div>

        {{-- RICHIESTE DI ASSISTENZA --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-8">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-widest text-gray-800">Richieste di Assistenza</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Ticket tracciati dallo staff</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold text-gray-400">{{ $supportRequests->total() }} totali</span>
                    <details class="relative">
                        <summary class="cursor-pointer list-none bg-[#ff6f3f] text-white text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-xl hover:bg-orange-600 transition select-none">
                            + Nuovo Ticket
                        </summary>
                        <div class="absolute right-0 top-full mt-2 w-96 bg-white rounded-2xl border border-gray-200 shadow-xl z-10 p-5">
                            <h3 class="text-xs font-black uppercase tracking-widest text-gray-700 mb-4">Nuovo Ticket</h3>
                            <form action="{{ route('amministrazione.supporto.store') }}" method="POST" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Utente (uid)</label>
                                    <input type="text" name="user_uid" placeholder="uid nextcloud" required maxlength="100"
                                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#ff6f3f]">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Oggetto</label>
                                    <input type="text" name="subject" placeholder="Oggetto del ticket" required maxlength="200"
                                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#ff6f3f]">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Descrizione</label>
                                    <textarea name="body" rows="4" required maxlength="3000" placeholder="Descrizione del problema..."
                                              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-xs text-gray-900 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#ff6f3f] resize-none"></textarea>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1">Priorità</label>
                                    <select name="priority" class="border border-gray-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#ff6f3f]">
                                        <option value="normale">Normale</option>
                                        <option value="urgente">Urgente</option>
                                    </select>
                                </div>
                                <button type="submit" class="w-full bg-[#ff6f3f] text-white text-[10px] font-black uppercase tracking-widest py-2.5 rounded-xl hover:bg-orange-600 transition mt-1">
                                    Crea Ticket
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Studio</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Utente</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Oggetto</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Priorità</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Stato</th>
                            <th class="text-left px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Data</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($supportRequests as $sr)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-gray-700 font-medium">{{ $sr->studio?->name ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500 text-xs">{{ $sr->user_uid }}</td>
                                <td class="px-6 py-4 text-gray-900 font-medium max-w-xs">
                                    <details class="group">
                                        <summary class="cursor-pointer list-none flex items-start gap-2">
                                            <svg class="w-3.5 h-3.5 mt-0.5 text-gray-300 group-open:text-[#ff6f3f] flex-shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            <span>{{ $sr->subject }}</span>
                                        </summary>
                                        <div class="mt-3 pl-5 space-y-4">
                                            <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-600 whitespace-pre-wrap border border-gray-100">{{ $sr->body }}</div>

                                            <form action="{{ route('amministrazione.supporto.status', $sr) }}" method="POST" class="space-y-3">
                                                @csrf
                                                @method('PATCH')
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Stato</label>
                                                    <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-[#ff6f3f]">
                                                        <option value="aperta" {{ $sr->status === 'aperta' ? 'selected' : '' }}>Aperta</option>
                                                        <option value="in_lavorazione" {{ $sr->status === 'in_lavorazione' ? 'selected' : '' }}>In lavorazione</option>
                                                        <option value="chiusa" {{ $sr->status === 'chiusa' ? 'selected' : '' }}>Chiusa</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Note interne</label>
                                                    <textarea name="staff_notes" rows="3" placeholder="Note visibili solo allo staff..."
                                                              class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#ff6f3f] resize-none">{{ $sr->staff_notes }}</textarea>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <button type="submit" class="text-[10px] font-black uppercase tracking-widest bg-[#ff6f3f] text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition">
                                                        Salva
                                                    </button>
                                                    <form action="{{ route('amministrazione.supporto.destroy', $sr) }}" method="POST" class="inline"
                                                          onsubmit="return confirm('Eliminare la richiesta #{{ $sr->id }}?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-red-600 transition px-3 py-2 rounded-lg hover:bg-red-50">
                                                            Elimina
                                                        </button>
                                                    </form>
                                                </div>
                                            </form>
                                        </div>
                                    </details>
                                </td>
                                <td class="px-6 py-4">
                                    @if($sr->priority === 'urgente')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest bg-red-100 text-red-700 px-2.5 py-1 rounded-full">Urgente</span>
                                    @else
                                        <span class="text-xs text-gray-400">Normale</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($sr->status === 'aperta')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest bg-red-100 text-red-700 px-2.5 py-1 rounded-full">Aperta</span>
                                    @elseif($sr->status === 'in_lavorazione')
                                        <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full">In lavorazione</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-full">Chiusa</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-400">{{ $sr->created_at->format('d/m/Y') }}</td>
                                <td class="px-6 py-4"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">
                                    Nessuna richiesta di assistenza ancora.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($supportRequests->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $supportRequests->appends(request()->except('sr_page'))->links() }}
                </div>
            @endif
        </div>

    </main>

</body>
</html>
