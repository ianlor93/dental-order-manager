<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benvenuto - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <style>
        body { font-family: 'Ubuntu', sans-serif; }
        .enamel-orange { color: #ff6f3f; }
        .bg-enamel-orange { background-color: #ff6f3f; }
        .news-scroll::-webkit-scrollbar { width: 4px; }
        .news-scroll::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 10px; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen flex flex-col">

    {{-- LOGICA PROFILO UTENTE --}}
    @php
        $profileType = 'Profilo Clinico';
        if($user->uid === 'admin') { 
            $profileType = 'Profilo Amministratore'; 
        } elseif($user->isStaff()) { 
            $profileType = 'Profilo Operativo'; 
        } elseif($user->isLaboratorio()) {
            $profileType = 'Profilo Tecnico';
        }
    @endphp

    <div class="flex-grow flex flex-col md:flex-row overflow-hidden">
        
        {{-- LATO SINISTRO: OPERATIVO --}}
        <div class="flex-1 p-8 md:p-16 flex flex-col justify-center bg-white">
            <div class="max-w-xl mx-auto w-full">
                
                {{-- HEADER --}}
                <div class="mb-12">
                    <span class="text-xs font-black uppercase tracking-[0.3em] enamel-orange">{{ config('app.name') }}</span>
                    <h1 class="text-5xl font-black uppercase leading-none mt-2 tracking-tighter">
                        Buongiorno,<br>
                        <span class="text-gray-400">{{ $user->display_name }}</span>
                    </h1>
                    <div class="mt-3 flex items-center gap-2">
                        <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                        <span class="text-xs font-bold uppercase tracking-widest text-gray-500">
                            Sei collegato con {{ $profileType }}
                        </span>
                    </div>
                </div>


                {{-- AREA AZIONI --}}
                <div class="space-y-6">
                    
                    {{-- 1. CTA PRINCIPALE --}}
                    <a href="{{ route('dashboard') }}" class="group bg-gray-900 text-white p-8 rounded-[2rem] flex items-center justify-between transition-all hover:bg-enamel-orange hover:scale-[1.01] shadow-xl">
                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-widest opacity-50">Accesso Autenticato</span>
                            <span class="text-3xl font-black uppercase tracking-tight">Entra in Laboratorio</span>
                        </div>
                        <span class="text-3xl transition-transform group-hover:translate-x-2">→</span>
                    </a>

                    {{-- SEPARATORE --}}
                    <div class="flex items-center gap-4 py-2">
                        <div class="h-px bg-gray-100 flex-1"></div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-gray-300">Assistenza Rapida</span>
                        <div class="h-px bg-gray-100 flex-1"></div>
                    </div>

                    {{-- 2. TASTI SUPPORTO --}}
                    <div class="grid grid-cols-2 gap-4">
                        
                        {{-- WhatsApp --}}
                        <a href="https://wa.me/393452371753" target="_blank" class="bg-white border-2 border-gray-100 p-4 rounded-2xl hover:border-[#25D366] hover:text-[#25D366] transition-all flex items-center justify-center gap-3 group h-20">
                            <div class="text-left">
                                <span class="block text-[8px] font-black uppercase tracking-widest opacity-50">Supporto</span>
                                <span class="text-sm font-bold leading-none">WhatsApp</span>
                            </div>
                        </a>

                        {{-- AnyDesk --}}
                        <a href="https://anydesk.com/it/downloads" target="_blank" class="bg-white border-2 border-gray-100 p-4 rounded-2xl hover:border-red-500 hover:text-red-600 transition-all flex items-center justify-center gap-3 group h-20">
                            <div class="text-left">
                                <span class="block text-[8px] font-black uppercase tracking-widest opacity-50">Remoto</span>
                                <span class="text-sm font-bold leading-none">AnyDesk</span>
                            </div>
                        </a>

                    </div>
                </div>
            </div>
        </div>

        {{-- LATO DESTRO: LOBBY NEWS --}}
        <div class="w-full md:w-[450px] bg-gray-900 text-gray-100 flex flex-col border-l border-gray-800">
            <div class="p-8 border-b border-gray-800 flex justify-between items-end">
                <div>
                    <h2 class="text-xl font-black uppercase tracking-tighter text-white">Changelog</h2>
                    <p class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">Aggiornamenti di sistema</p>
                </div>
                <span class="text-[10px] font-mono text-gray-600">ToS; DPA; Privacy v_{{ config('legal.version') }}</span>
            </div>

            <div class="flex-grow overflow-y-auto p-8 news-scroll bg-gradient-to-b from-gray-900 to-gray-800">
                @forelse($news as $item)
                    @php
                        $borderColor = 'border-gray-600';
                        $titleColor = 'text-white';
                        if($item['type'] == 'new')   { $borderColor = 'border-[#ff6f3f]'; $titleColor = 'text-[#ff6f3f]'; }
                        if($item['type'] == 'alert') { $borderColor = 'border-red-500'; $titleColor = 'text-red-400'; }
                    @endphp

                    <div class="mb-10 border-l-2 {{ $borderColor }} pl-6 relative">
                        <div class="absolute -left-[5px] top-0 w-2 h-2 rounded-full bg-gray-900 border border-gray-600"></div>
                        <span class="block text-[10px] font-mono text-gray-500 mb-2 tracking-tighter">{{ $item['date'] }}</span>
                        <h3 class="text-sm font-black uppercase tracking-tight {{ $titleColor }} leading-tight mb-2">
                            {{ $item['title'] }}
                        </h3>
                        <p class="text-xs text-gray-400 leading-relaxed font-medium italic">
                            {!! nl2br(e($item['body'])) !!}
                        </p>
                    </div>
                @empty
                    <div class="text-center py-20 opacity-20">
                        <p class="text-xs font-black uppercase tracking-widest italic">Nessuna comunicazione</p>
                    </div>
                @endforelse
            </div>

            <div class="p-6 bg-gray-950/50 text-center border-t border-gray-800">
                <p class="text-[9px] font-bold text-gray-600 uppercase tracking-widest">© 2026 {{ config('lab.manufacturer.name') }} — MDR Order Manager</p>
            </div>
        </div>

    </div>

</body>
</html>
