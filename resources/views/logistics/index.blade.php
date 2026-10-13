<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logistica e Spedizioni - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <style>body { font-family: 'Ubuntu', sans-serif; }</style>
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
                <a href="{{ route('orders.create') }}" class="bg-[#ff6f3f] text-white px-6 py-2.5 rounded-xl shadow-lg font-black text-xs uppercase tracking-widest hover:scale-105 transition">+ Nuova Richiesta</a>
                <a href="{{ route('profile.show') }}" class="text-right flex flex-col items-end group ml-4">
                    <div class="text-xs font-black uppercase text-gray-800">{{ $user->displayname ?? $user->uid }}</div>
                    <div class="mt-1">
                        <span class="{{ $user->isStaff() ? 'bg-gray-800' : ($user->isLaboratorio() ? 'bg-purple-600' : 'bg-[#ff6f3f]') }} text-white text-[8px] font-black px-2 py-0.5 rounded tracking-[0.1em] uppercase">
                            @if($user->isStaff()) Staff @elseif($user->isLaboratorio()) Laboratorio @else Studio @endif
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto p-6">
        
        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-2xl mb-8 font-bold">
                ✅ {{ session('success') }}
            </div>
        @endif

        <div class="mb-10">
            <h1 class="text-3xl font-black uppercase text-gray-800">Magazzino Uscite</h1>
            <p class="text-sm text-gray-500 font-medium mt-1">Lavori in stato "Pronto Consegna" in attesa di spedizione.</p>
        </div>

        @if($studios->isEmpty())
            <div class="bg-white rounded-3xl p-16 text-center shadow-sm border border-gray-200">
                <div class="text-4xl mb-4">📦</div>
                <h3 class="text-lg font-black uppercase text-gray-400">Nessun lavoro in attesa</h3>
                <p class="text-xs font-bold text-gray-400 mt-2">Tutti i lavori completati sono stati spediti o sono in lavorazione.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-8">
                @foreach($studios as $studio)
                    <form action="{{ route('logistics.store') }}" method="POST" class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100 group hover:border-orange-200 transition-all">
                        @csrf
                        <input type="hidden" name="studio_id" value="{{ $studio->id }}">

                        <div class="bg-gray-50 p-6 flex justify-between items-center border-b border-gray-100">
                            <div>
                                <h2 class="text-xl font-black uppercase text-gray-800">{{ $studio->name }}</h2>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ $studio->city }}</p>
                            </div>
                            <span class="text-xs font-bold bg-orange-100 text-orange-600 px-3 py-1 rounded-full">{{ $studio->orders->count() }} pronti</span>
                        </div>

                        <div class="p-6">
                            <div class="space-y-3">
                                @foreach($studio->orders as $order)
                                    <label class="flex items-center gap-4 p-4 border border-gray-100 rounded-2xl hover:bg-orange-50 cursor-pointer transition-colors">
                                        <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="w-5 h-5 text-orange-600 rounded focus:ring-orange-500 border-gray-300" checked>
                                        
                                        <div class="flex-1">
                                            <div class="flex justify-between">
                                                <span class="text-sm font-black uppercase text-gray-800">#{{ $order->code }}</span>
                                                {{-- FIX 500: Usiamo updated_at perché archived_at è nullo --}}
                                                <span class="text-[10px] font-mono text-gray-400">Pronto dal: {{ $order->updated_at->format('d/m/Y H:i') }}</span>
                                            </div>
                                            <div class="text-xs font-bold text-gray-600 mt-0.5">{{ $order->patient_ref }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-gray-900 p-6 flex flex-col md:flex-row gap-4 items-end justify-between">
                            <div class="flex-1 w-full md:w-auto grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[9px] font-black text-gray-500 uppercase block mb-1">Corriere</label>
                                    <select name="courier" class="w-full bg-gray-800 text-white text-xs font-bold rounded-lg border-none p-2.5">
                                        <option value="Consegna Diretta">Consegna Diretta (A mano)</option>
                                        <option value="GLS">GLS</option>
                                        <option value="DHL">DHL</option>
                                        <option value="TNT">TNT</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-[9px] font-black text-gray-500 uppercase block mb-1">Tracking</label>
                                    <input type="text" name="tracking_code" placeholder="Opzionale" class="w-full bg-gray-800 text-white text-xs font-bold rounded-lg border-none p-2.5">
                                </div>
                            </div>
                            <button type="submit" class="w-full md:w-auto bg-orange-500 hover:bg-orange-600 text-white font-black uppercase text-xs tracking-widest px-8 py-4 rounded-xl shadow-lg transition-transform active:scale-95">
                                Genera DDT
                            </button>
                        </div>
                    </form>
                @endforeach
            </div>
        @endif
    </div>

    {{-- AUTO DOWNLOAD SCRIPT --}}
    @if(session('download_note_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    window.open("{{ route('logistics.download', session('download_note_id')) }}", '_blank');
                }, 500);
            });
        </script>
    @endif
</body>
</html>