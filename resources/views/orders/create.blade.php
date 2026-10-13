<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuova Richiesta - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/resumable.js/1.1.0/resumable.min.js"></script>
    <style>
        body { font-family: 'Ubuntu', sans-serif; }
        .enamel-orange { color: #ff6f3f; }
        .bg-enamel-orange { background-color: #ff6f3f; }
        .btn-loading { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
        select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1.5em; }
        #upload-progress-container { display: none; }
        .progress-bar-fill { transition: width 0.3s ease; }
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

        <div class="bg-white p-6 md:p-10 rounded-3xl shadow-xl border border-gray-200">
            
            <div class="flex justify-between items-start mb-10 border-b pb-6">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tighter leading-none">Nuova Richiesta</h1>
                    <p class="text-[11px] text-[#ff6f3f] font-black uppercase tracking-widest mt-2">Valutazione Tecnica Preliminare</p>
                </div>
                <div class="text-right">
                    <span class="text-[10px] font-mono bg-gray-100 text-gray-400 px-3 py-1 rounded-full border border-gray-200 uppercase font-bold">
                        Mover Protocol v2.9
                    </span>
                </div>
            </div>

            <form id="orderForm" class="space-y-8" onsubmit="return false;">
                @csrf
                {{-- Campi tecnici nascosti con valori di default --}}
                <input type="hidden" name="expected_files_count" id="expected_files_count" value="0">
                <input type="hidden" name="material" value="N/D">
                <input type="hidden" name="teeth_notation" value="N/D">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    {{-- Selezione Sede e Medico --}}
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2">Sede e Referente *</label>
                        <select name="address_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none transition shadow-sm">
                            
                            @if($user->isStaff())
                                {{-- Visualizzazione per lo Staff: raggruppata per Studio --}}
                                @isset($allAddresses)
                                    @foreach($allAddresses->groupBy(fn($a) => $a->studio->name ?? 'N/D') as $studioName => $addresses)
                                        {{-- 
                                            MODIFICA CHIRURGICA: Determino se lo studio del gruppo è Lab o Medico 
                                            per l'etichetta del gruppo (senza rompere il raggruppamento)
                                        --}}
                                        @php
                                            $firstAddr = $addresses->first();
                                            $studioItca = $firstAddr->studio->itca_code ?? null;
                                            $groupLabel = strtoupper($studioName) . ($studioItca ? ' (LABORATORIO)' : ' (STUDIO MEDICO)');
                                        @endphp

                                        <optgroup label="{{ $groupLabel }}">
                                            @foreach($addresses as $addr)
                                                @php
                                                    // MODIFICA CHIRURGICA: Se è Lab mostra ITCA, se Medico mostra Albo
                                                    $isLab = !empty($addr->studio->itca_code);
                                                    $role = $isLab ? 'Resp.' : 'Dr.';
                                                    $qualifica = $isLab 
                                                        ? 'ITCA: ' . $addr->studio->itca_code 
                                                        : 'Albo: ' . $addr->dentist_license . ' (' . $addr->albo_provincia . ')';
                                                @endphp
                                                <option value="{{ $addr->id }}">
                                                    [{{ $addr->label }}] - {{ $role }} {{ $addr->dentist_name }} - {{ $qualifica }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                @endisset
                            @else
                                {{-- Visualizzazione per il Cliente: solo le sue sedi --}}
                                @if($user->studio && $user->studio->addresses)
                                    @php
                                        // Controllo se l'utente loggato è un laboratorio
                                        $isUserLab = !empty($user->studio->itca_code);
                                    @endphp
                                    @foreach($user->studio->addresses as $address)
                                        @php
                                            $roleUser = $isUserLab ? 'Resp.' : 'Dr.';
                                            $refName = $address->dentist_name ?: $user->studio->dentist_name;
                                            // Costruzione stringa qualifica
                                            $qualificaUser = $isUserLab 
                                                ? 'ITCA: ' . $user->studio->itca_code
                                                : 'Albo: ' . ($address->dentist_license ?: $user->studio->dentist_license) . ' (' . ($address->albo_provincia ?: $user->studio->albo_provincia) . ')';
                                        @endphp
                                        <option value="{{ $address->id }}">
                                            [{{ $address->label }}] - {{ $roleUser }} {{ $refName }} - {{ $qualificaUser }}
                                        </option>
                                    @endforeach
                                @endif
                            @endif
                        </select>
                    </div>

                    {{-- Riferimento Paziente --}}
                    <div>
                        <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2">Riferimento Lavoro *</label>
                        <input type="text" name="patient_ref" id="patient_ref" required placeholder="Es: Rossi_2601" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm font-bold focus:ring-2 focus:ring-[#ff6f3f] outline-none shadow-sm uppercase">
                    </div>

                    {{-- Ipotesi di Trattamento --}}
                    <div>
                        <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2">Ipotesi di Trattamento *</label>
                        <select name="device_type" id="device_type" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm font-bold outline-none shadow-sm">
                            <option value="protesi_fissa">Protesi Fissa</option>
                            <option value="protesi_mobile">Protesi Mobile</option>
                            <option value="protesi_ortodontica">Protesi Ortodontica</option>
                            <option value="chirurgia">Chirurgia</option>
                            <option value="altro">Altro</option>
                        </select>
                    </div>
                </div>

                {{-- DROPZONE CARICAMENTO --}}
                <div id="dropzone" class="p-8 border-2 border-dashed border-gray-300 rounded-3xl bg-gray-50/50 hover:border-[#ff6f3f] transition group text-center cursor-pointer">
                    <button type="button" id="browseButton" class="text-sm font-black text-gray-800 uppercase mb-1 underline">Carica file di lavoro</button>
                    <p class="text-[10px] text-gray-400 mb-4 font-bold uppercase tracking-tighter">STL, PLY, OBJ, DCM, ZIP, RAR, 7Z o IMMAGINI</p>
                    <div id="file-list" class="w-full max-w-lg mx-auto space-y-2 text-left"></div>
                    <div id="empty-state" class="text-[9px] font-bold text-gray-300 uppercase italic">Nessun file selezionato</div>
                </div>

                {{-- Note Cliniche --}}
                <div>
                    <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2">Note / Specifiche Tecniche *</label>
                    <textarea name="notes" id="notes" rows="4" required class="w-full bg-gray-50 border border-gray-200 rounded-xl p-4 text-sm font-medium outline-none focus:ring-2 focus:ring-[#ff6f3f]" placeholder="Descrivi il caso o aggiungi dettagli per lo staff..."></textarea>
                </div>

                {{-- FOOTER AZIONI E PROGRESSO --}}
                <div class="pt-8 border-t flex flex-col gap-6">
                    
                    {{-- Barra di progresso --}}
                    <div id="upload-progress-container" class="p-6 bg-gray-900 rounded-2xl shadow-xl text-white">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em]">Trasferimento Asset...</h3>
                            <span id="upload-percentage" class="text-lg font-black text-[#ff6f3f]">0%</span>
                        </div>
                        <div class="w-full bg-gray-700 h-2 rounded-full overflow-hidden">
                            <div id="upload-progress-bar" class="bg-enamel-orange h-full progress-bar-fill" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <a href="{{ route('dashboard') }}" id="cancelBtn" class="text-gray-400 hover:text-gray-900 text-[10px] font-black uppercase tracking-widest transition">Annulla</a>
                        <button type="button" id="submitBtn" class="bg-[#ff6f3f] text-white px-16 py-5 rounded-2xl font-black text-[12px] uppercase shadow-2xl hover:opacity-90 transition transform hover:-translate-y-1 tracking-widest">
                            Invia Richiesta
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ✨ CONFIGURAZIONE RESUMABLE ESTESA (Aggiunti formati Archivio e Immagine)
        const r = new Resumable({
            target: '', // Impostato dinamicamente
            chunkSize: 2 * 1024 * 1024, 
            simultaneousUploads: 3,
            testChunks: false,
            query: { _token: '{{ csrf_token() }}' },
            fileType: ['stl', 'ply', 'obj', 'dcm', 'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'bmp']
        });

        r.assignBrowse(document.getElementById('browseButton'));
        r.assignDrop(document.getElementById('dropzone'));

        r.on('fileAdded', (file) => { 
            document.getElementById('empty-state').style.display = 'none'; 
            renderFileList(); 
        });

        // ✨ LOGICA DI RENDERING CON TASTO RIMOZIONE
        function renderFileList() {
            const list = document.getElementById('file-list');
            list.innerHTML = '';
            
            if (r.files.length === 0) {
                document.getElementById('empty-state').style.display = 'block';
            }

            r.files.forEach(file => {
                const row = document.createElement('div');
                row.className = "flex justify-between items-center bg-white border border-gray-100 p-3 rounded-xl shadow-sm group/item";
                row.innerHTML = `
                    <span class="text-[10px] font-mono uppercase truncate pr-4 text-gray-600">${file.fileName}</span>
                    <button type="button" onclick="removeFileFromQueue('${file.uniqueIdentifier}')" class="text-red-400 hover:text-red-600 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                `;
                list.appendChild(row);
            });
            
            // Aggiorna il contatore previsto per il backend
            document.getElementById('expected_files_count').value = r.files.length;
        }

        // ✨ FUNZIONE DI RIMOZIONE FILE
        window.removeFileFromQueue = function(uniqueId) {
            const file = r.getFromUniqueIdentifier(uniqueId);
            if (file) {
                r.removeFile(file);
                renderFileList();
            }
        };

        document.getElementById('submitBtn').addEventListener('click', async () => {
            if (r.files.length === 0) return alert("Seleziona almeno un file di lavoro.");
            
            const btn = document.getElementById('submitBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const progressContainer = document.getElementById('upload-progress-container');
            
            btn.innerText = "INIZIALIZZAZIONE...";
            btn.classList.add('btn-loading');

            const formData = new FormData(document.getElementById('orderForm'));
            
            try {
                const res = await fetch("{{ route('orders.store') }}", {
                    method: 'POST',  
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await res.json();
                
                if (data.success) {
                    const orderRef = data.order_code; 
                    
                    r.opts.target = `/orders/${orderRef}/upload-chunk`;
                    r.opts.query.session_id = data.session_id;
                    
                    progressContainer.style.display = 'block';
                    cancelBtn.style.visibility = 'hidden'; 
                    
                    document.getElementById('orderForm').querySelectorAll('input, select, textarea, #dropzone').forEach(el => el.style.pointerEvents = 'none');
                    document.getElementById('orderForm').style.opacity = '0.5';
                    
                    r.upload();
                } else {
                    alert("Errore: " + (data.error || "Controlla i dati."));
                    btn.classList.remove('btn-loading');
                    btn.innerText = "INVIA RICHIESTA";
                }
            } catch (e) { 
                alert("Errore di rete."); 
                btn.classList.remove('btn-loading'); 
                btn.innerText = "INVIA RICHIESTA";
            }
        });

        r.on('progress', () => {
            const progress = Math.floor(r.progress() * 100);
            document.getElementById('upload-progress-bar').style.width = progress + '%';
            document.getElementById('upload-percentage').innerText = progress + '%';
            document.getElementById('submitBtn').innerText = "CARICAMENTO...";
        });

        r.on('complete', () => { 
            window.location.href = "{{ route('dashboard') }}?success=sent"; 
        });

        r.on('error', (message) => {
            alert("Errore durante il trasferimento.");
            location.reload();
        });
    </script>

</body>
</html>