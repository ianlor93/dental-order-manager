<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Registro Analisi MDR - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/resumable.js/1.1.0/resumable.min.js"></script>
    <style>
        body { font-family: 'Ubuntu', sans-serif; }
        .enamel-orange { color: #ff6f3f; }
        .bg-enamel-orange { background-color: #ff6f3f; }
        .border-enamel-orange { border-color: #ff6f3f; }
        .progress-bar-fill { transition: width 0.3s ease; }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        .upload-toast { animation: slideUp 0.4s ease-out; }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); } 70% { box-shadow: 0 0 0 8px rgba(220, 38, 38, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); } }
        .pulse-urgent { animation: pulse-red 2s infinite; }
        .widget-link:hover { transform: translateY(-3px); }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

    <nav class="bg-white border-b-4 border-[#ff6f3f] p-4 shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="{{ route('dashboard') }}" class="text-xl font-bold uppercase tracking-tight text-gray-800 hover:opacity-80 transition">{{ config('app.name') }}</a>
            <div class="flex items-center gap-4">
                
                @if(\App\Support\AccessContext::for($user)->isBackOffice())
                    <a href="{{ route('customers.index') }}" class="bg-indigo-50 text-indigo-700 px-4 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-indigo-600 hover:text-white transition shadow-sm border border-indigo-100" title="Gestione Clienti">Anagrafica</a>
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
        @if(session('success')) <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-800 text-[10px] font-black uppercase rounded-2xl">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-800 text-[10px] font-black uppercase rounded-2xl">{{ session('error') }}</div> @endif

        @if($user->isStaff())
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                @foreach(['overdue'=>'In Ritardo','due_today'=>'Oggi','due_tomorrow'=>'Domani','in_trial'=>'In Prova','urgent'=>'Urgenti'] as $key => $label)
                    @php 
                        $filterValue = str_replace(['due_', 'in_'], '', $key);
                        $colors = ['overdue'=>'red-600', 'due_today'=>'red-500', 'due_tomorrow'=>'orange-600', 'in_trial'=>'blue-600', 'urgent'=>'gray-900'];
                    @endphp
                    <a href="{{ route('dashboard', ['filter' => $filterValue]) }}" class="bg-white p-6 rounded-[2rem] shadow-xl border-b-4 border-{{ $colors[$key] }} flex flex-col justify-between transition-all widget-link {{ request('filter') == $filterValue ? 'ring-4 ring-'.$colors[$key].'/20 -translate-y-1' : '' }}">
                        <span class="text-[9px] font-black uppercase text-gray-400 tracking-widest leading-tight">{{ $label }}</span>
                        <div class="mt-2"><span class="text-3xl font-black text-{{ $colors[$key] }} leading-none">{{ $stats[$key] ?? 0 }}</span></div>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mb-8 bg-white p-4 rounded-[2rem] shadow-lg border border-gray-100">
            <form action="{{ route('dashboard') }}" method="GET" class="flex flex-col md:flex-row gap-3">
                <div class="flex-1 relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cerca Lavoro o ID..." class="w-full bg-gray-50 border-none rounded-2xl py-3 pl-12 pr-4 text-xs font-bold focus:ring-2 focus:ring-[#ff6f3f] transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <select name="status" class="bg-gray-50 border-none rounded-2xl px-4 py-3 text-[10px] font-black uppercase tracking-widest focus:ring-2 focus:ring-[#ff6f3f] cursor-pointer">
                    <option value="">Tutti gli Stati</option>
                    @foreach(['draft', 'scanning', 'processing', 'to_be_validated', 'validated', 'in_progettazione', 'in_produzione', 'finalizzazione', 'controllo_qualita', 'pronto_consegna'] as $st)
                        <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $st)) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-gray-900 text-white px-8 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-black transition-all">Filtra</button>
            </form>
        </div>

        <div class="bg-white rounded-3xl shadow-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left" id="ordersTable">
                    <thead>
                        <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b bg-gray-50/50">
                            <th class="px-6 py-5">ID / Data</th>
                            <th class="px-6 py-5">Rif. Lavoro & Cliente / Sede</th>
                            <th class="px-6 py-5 text-center">Tipo</th>
                            <th class="px-6 py-5 text-center">Consegna</th>
                            <th class="px-6 py-5">Stato</th>
                            <th class="px-6 py-5 text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-xs">
                                <div class="flex items-center gap-2 mb-1">
                                    <button onclick="showQuickDetails({{ $order->id }})" class="font-mono font-black text-[#ff6f3f] hover:underline uppercase tracking-widest block">{{ $order->code }}</button>
                                    @if($order->is_trial) <span class="bg-blue-100 text-blue-600 text-[7px] font-black px-1.5 py-0.5 rounded border border-blue-200 uppercase">PROVA</span> @endif
                                    @if($order->priority === 'high') <span class="bg-red-600 text-white text-[7px] font-black px-1.5 py-0.5 rounded uppercase pulse-urgent">URGENTE</span> @endif
                                </div>
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="px-6 py-4 leading-tight">
                                <div class="text-sm font-black uppercase text-gray-800 mb-1">{{ $order->patient_ref }}</div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase mb-1">{{ $order->studio?->name ?? 'Studio N/D' }} — <span class="text-gray-900">{{ $order->address?->label ?? 'Sede N/D' }}</span></div>
                                
                                {{-- 🛡️ DATI MULTI-PRESCRITTORE --}}
                                @php
                                    $isLabOrder = !empty($order->studio->itca_code);
                                    $prescriberName = $order->address?->dentist_name ?? $order->studio?->dentist_name ?? 'N/D';
                                    $licenseInfo = $order->address?->dentist_license ?? $order->studio?->dentist_license ?? '-';
                                    $provInfo = $order->address?->albo_provincia ?? $order->studio?->albo_provincia ?? '-';
                                    $itcaInfo = $order->studio->itca_code;
                                @endphp

                                <div class="flex items-center gap-2 text-[9px] font-black uppercase {{ $isLabOrder ? 'text-purple-600' : 'text-blue-600' }}">
                                    <span>{{ $isLabOrder ? 'Resp.' : 'Dr.' }} {{ $prescriberName }}</span>
                                    <span class="text-gray-300">|</span>
                                    <span class="text-gray-500 font-medium">
                                        {{ $isLabOrder ? 'ITCA: '.$itcaInfo : 'Albo: '.$licenseInfo.' ('.$provInfo.')' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-[10px] font-black uppercase text-gray-600">{{ str_replace('_', ' ', $order->device_type) }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($order->delivery_date)
                                    @php
                                        $today_dt = \Carbon\Carbon::now()->startOfDay();
                                        $delivery = \Carbon\Carbon::parse($order->delivery_date)->startOfDay();
                                        $diffDays = $today_dt->diffInDays($delivery, false);
                                    @endphp
                                    <div class="flex flex-col items-center gap-1">
                                        @if($order->status === 'completed' || $order->status === 'pronto_consegna')
                                            <div class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-green-600 text-white shadow-md">{{ $delivery->format('d/m/Y') }}</div>
                                        @elseif($diffDays < 0)
                                            <div class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-red-600 text-white shadow-md">SCADUTO</div>
                                        @elseif($diffDays == 0)
                                            <div class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-red-5 text-red-600 animate-pulse shadow-md">OGGI</div>
                                        @elseif($diffDays == 1)
                                            <div class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-orange-600 text-white shadow-sm">DOMANI</div>
                                        @else
                                            <div class="px-3 py-1 rounded-full text-[9px] font-black uppercase bg-green-500 text-white shadow-sm">{{ $delivery->format('d/m/Y') }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-300 text-[8px] font-black uppercase italic tracking-tighter">Da pianificare</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $st = [
                                        'draft'=>'bg-gray-200 text-gray-600', 'scanning'=>'bg-yellow-100 text-yellow-700', 'processing'=>'bg-blue-100 text-blue-700',
                                        'to_be_validated' => 'bg-orange-100 text-orange-700 animate-pulse', 'validated' => 'bg-green-100 text-green-700',
                                        'in_progettazione' => 'bg-purple-100 text-purple-700 font-bold border border-purple-200', 'in_produzione' => 'bg-blue-600 text-white font-bold',
                                        'finalizzazione' => 'bg-orange-600 text-white font-black', 
                                        'controllo_qualita' => 'bg-indigo-700 text-white font-black shadow-lg animate-pulse', 
                                        'pronto_consegna' => 'bg-green-600 text-white font-black shadow-sm'
                                    ];
                                @endphp
                                <span class="px-3 py-1.5 rounded text-[8px] font-black uppercase tracking-widest {{ $st[$order->status] ?? 'bg-gray-100' }}">{{ str_replace('_', ' ', $order->status) }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center items-center gap-2">
                                    @if($user->isStaff())
                                        @if($order->status == 'processing') 
                                            <a href="{{ route('orders.prescription.create', $order) }}" class="bg-gray-900 text-white px-3 py-1.5 rounded-lg text-[8px] font-black uppercase tracking-widest transition">Prescrizione</a>
                                        @elseif($order->status == 'to_be_validated')
                                            <form action="{{ route('orders.prescription.cancel', $order) }}" method="POST" class="inline" onsubmit="return confirm('Sei sicuro di voler annullare la prescrizione inviata al dottore?')">
                                                @csrf <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-[8px] font-black uppercase shadow transition">Annulla Prescrizione</button>
                                            </form>
                                        @elseif($order->status == 'validated') 
                                            <form action="{{ route('orders.production.start', $order) }}" method="POST" class="inline">@csrf <button type="submit" class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-[8px] font-black uppercase">Avvia Lavoro</button></form>
                                        @elseif(in_array($order->status, ['in_progettazione', 'in_produzione', 'finalizzazione', 'controllo_qualita'])) 
                                            <a href="{{ route('orders.production.create', $order) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-[8px] font-black uppercase transition shadow-md">Gestisci Stepper</a>
                                        @elseif($order->status == 'pronto_consegna')
                                            @php $hasMdrDocs = $order->hasMdrDocuments(); @endphp
                                            @if(!$order->production || !$order->production->isReleased() || !$hasMdrDocs)
                                                <form action="{{ route('orders.mdr.generate', $order) }}" method="POST" class="inline">
                                                    @csrf <button type="submit" title="{{ $hasMdrDocs ? 'Rigenera i PDF MDR su FIN_L' : 'Genera PDF MDR su FIN_L (obbligatori prima dell\'archiviazione)' }}" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg text-[8px] font-black uppercase shadow hover:bg-indigo-700 transition">{{ $hasMdrDocs ? 'Rigenera Doc' : 'Genera Doc' }}</button>
                                                </form>
                                            @endif
                                            @if($order->production && $order->production->isReleased() && $hasMdrDocs)
                                                @if(is_null($order->delivery_note_id))
                                                    @if(\App\Support\AccessContext::for($user)->isBackOffice())
                                                        <a href="{{ route('logistics.index') }}" class="bg-orange-500 text-white px-3 py-1.5 rounded-lg text-[8px] font-black uppercase shadow hover:bg-orange-600 transition flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                                                            Vai a Logistica
                                                        </a>
                                                    @else
                                                        <span class="bg-gray-100 text-gray-400 px-3 py-1.5 rounded-lg text-[8px] font-black uppercase border border-gray-200 cursor-not-allowed">In Attesa Spedizione</span>
                                                    @endif
                                                @else
                                                    <form action="{{ route('orders.mdr.archive', $order) }}" method="POST" class="inline" onsubmit="return confirm('Il lavoro è stato spedito. Confermi l\'archiviazione definitiva?')">
                                                        @csrf <button type="submit" title="Sposta in ARCH_L" class="bg-black text-white px-3 py-1.5 rounded-lg text-[8px] font-black uppercase shadow hover:scale-105 transition animate-pulse">Archivia</button>
                                                    </form>
                                                @endif
                                            @endif
                                        @endif
                                    @endif

                                    @if(!$user->isStaff() && $order->status == 'to_be_validated')
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('orders.prescription.preview', $order) }}" class="bg-enamel-orange text-white px-4 py-1.5 rounded-lg text-[8px] font-black uppercase tracking-widest shadow-lg transition transform hover:scale-105">Revisiona e Convalida</a>
                                        </div>
                                    @endif

                                    @php $allowedForUpload = ['processing', 'to_be_validated', 'validated', 'in_progettazione', 'in_produzione', 'finalizzazione', 'controllo_qualita', 'pronto_consegna']; @endphp
                                    @if(in_array($order->status, $allowedForUpload))
                                        <button onclick="triggerSupplementalUpload('{{ $order->code }}')" title="Allega Asset" class="text-[#ff6f3f] border border-[#ff6f3f] hover:bg-[#ff6f3f] hover:text-white transition p-1.5 rounded-lg">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                        </button>
                                    @endif

                                    <button onclick="showQuickDetails({{ $order->id }})" class="text-gray-300 hover:text-[#ff6f3f] transition p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 bg-gray-50 border-t border-gray-100 text-xs">{{ $orders->links() }}</div>
        </div>
    </div>

    @include('partials.order-detail-modal')

    <div id="suppUploadOverlay" class="fixed bottom-6 right-6 z-[100] hidden">
        <div class="bg-gray-900 text-white p-6 rounded-3xl shadow-2xl border-b-4 border-[#ff6f3f] min-w-[300px] upload-toast">
            <div class="flex justify-between items-center mb-4"><div class="flex items-center gap-3"><div class="animate-spin rounded-full h-4 w-4 border-2 border-[#ff6f3f] border-t-transparent"></div><h3 class="text-[10px] font-black uppercase">Integrazione...</h3></div><span id="suppPercentage" class="text-sm font-black text-[#ff6f3f]">0%</span></div>
            <div class="w-full bg-gray-800 h-1.5 rounded-full overflow-hidden"><div id="suppProgressBar" class="bg-[#ff6f3f] h-full progress-bar-fill" style="width: 0%"></div></div>
        </div>
    </div>

    <input type="file" id="globalSuppInput" class="hidden">
    <script>
        const ordersData = @json($orders->items());
        let currentOrderRef = null; 

        const suppResumable = new Resumable({ target: '', chunkSize: 2 * 1024 * 1024, query: { _token: '{{ csrf_token() }}' }, testChunks: false });
        suppResumable.assignBrowse(document.getElementById('globalSuppInput'));
        
        function triggerSupplementalUpload(code) { currentOrderRef = code; document.getElementById('globalSuppInput').click(); }
        
        suppResumable.on('fileAdded', (f) => { 
            document.getElementById('suppUploadOverlay').classList.remove('hidden'); 
            suppResumable.opts.target = `/orders/${currentOrderRef}/upload-supplemental`; 
            suppResumable.upload(); 
        });
        
        suppResumable.on('progress', () => { const p = Math.floor(suppResumable.progress() * 100); document.getElementById('suppProgressBar').style.width = p + '%'; document.getElementById('suppPercentage').innerText = p + '%'; });
        suppResumable.on('complete', () => { location.reload(); });
    </script>
</body>
</html>