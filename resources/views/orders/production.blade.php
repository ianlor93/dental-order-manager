<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workflow Produzione - {{ $order->code }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('fonts/ubuntu.css') }}">
    <style>
        body { font-family: 'Ubuntu', sans-serif; background-color: #f9fafb; color: #1f2937; }
        .enamel-orange { color: #ff6f3f; }
        .bg-enamel-orange { background-color: #ff6f3f; }
        .step-content-locked { opacity: 0.3; pointer-events: none; filter: grayscale(1); transition: all 0.3s; }
        .badge-done { background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 8px; font-weight: 900; text-transform: uppercase; }
        input, textarea, select { border: 1px solid #e5e7eb !important; border-radius: 12px !important; padding: 12px !important; width: 100%; font-size: 13px; font-weight: 600; outline: none !important; }
        input:focus, select:focus { border-color: #ff6f3f !important; ring: 2px #ff6f3f22; }
        .search-highlight { background-color: #fff7ed; border-bottom: 2px solid #ff6f3f !important; }
        .btn-add { background: #1f2937; color: white; padding: 6px 12px; border-radius: 8px; font-size: 10px; font-weight: 900; text-transform: uppercase; cursor: pointer; }
        
        /* ✨ Feedback visuale per il caricamento */
        .btn-loading { opacity: 0.5; cursor: not-allowed; position: relative; }
    </style>
</head>
<body class="pb-20">

    @php
        $s = $order->status;
        $lock1 = ($s !== 'in_progettazione' && $s !== 'new');
        $lock2 = ($s !== 'in_produzione');
        $lock3 = ($s !== 'finalizzazione');
        $lock4 = ($s !== 'controllo_qualita');
        
        $teethLib = is_array($production->cad_teeth_library) ? $production->cad_teeth_library : json_decode($production->cad_teeth_library, true) ?? [];
        $camData = is_array($production->cam_data) ? $production->cam_data : json_decode($production->cam_data, true) ?? [];
        $labMaterials = is_array($production->lab_materials) ? $production->lab_materials : json_decode($production->lab_materials, true) ?? [];
        $currentDevices = $order->manufactured_devices ?? [];
    @endphp

    <nav class="bg-white border-b-4 border-[#ff6f3f] p-4 shadow-md sticky top-0 z-50 mb-10">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-black transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <span class="text-xl font-bold uppercase">STEPPER <span class="enamel-orange">MDR</span></span>
            </div>
            <div class="text-right">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Lavoro: {{ $order->code }}</div>
                <div class="text-lg font-black enamel-orange uppercase leading-none">{{ $order->device_type }}</div>
            </div>
        </div>
    </nav>

    <div class="max-w-3xl mx-auto px-4">
        @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-2xl mb-6 font-bold">
                ⚠️ ERRORE: {{ session('error') }}
            </div>
        @endif

        <form id="productionForm" action="{{ route('orders.production.store', $order) }}" method="POST">
            @csrf
            <input type="hidden" name="action" id="actionInput">

            {{-- 1. CAD --}}
            <section id="sect_cad" class="bg-white p-8 rounded-[2rem] border border-gray-100 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-black uppercase enamel-orange flex items-center gap-3"><span class="bg-enamel-orange text-white w-7 h-7 rounded-full flex items-center justify-center text-xs">1</span> CAD (Progetto)</h2>
                    <button type="button" onclick="unlockSection('sect_cad')" class="text-gray-400 hover:text-orange-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                </div>
                <div class="step-body {{ $lock1 && !empty($production->cad_operator) ? 'step-content-locked' : '' }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2"><input type="text" name="cad_operator" required placeholder="Operatore CAD" value="{{ $production->cad_operator }}"></div>
                        <input type="text" name="cad_teeth_library[ant]" placeholder="Libreria Denti Anteriore" value="{{ $teethLib['ant'] ?? '' }}">
                        <input type="text" name="cad_teeth_library[post]" placeholder="Libreria Denti Posteriore" value="{{ $teethLib['post'] ?? '' }}">
                        <input type="text" name="cad_implant_library" placeholder="Libreria Impianti" value="{{ $production->cad_implant_library }}">
                        <textarea name="cad_notes" rows="2" placeholder="Note di progettazione..." class="md:col-span-2">{{ $production->cad_notes }}</textarea>
                    </div>
                    <button type="button" onclick="submitStep('step_cad', 'sect_cad')" class="mt-8 bg-enamel-orange text-white px-8 py-3 rounded-xl font-black uppercase text-xs tracking-widest shadow-lg save-btn {{ ($s !== 'in_progettazione' && $s !== 'new') ? 'hidden' : '' }}">Salva Fase CAD</button>
                </div>
            </section>

            {{-- 2. CAM --}}
            <section id="sect_cam" class="bg-white p-8 rounded-[2rem] border border-gray-100 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-black uppercase enamel-orange flex items-center gap-3"><span class="bg-enamel-orange text-white w-7 h-7 rounded-full flex items-center justify-center text-xs">2</span> CAM (Produzione)</h2>
                    <button type="button" onclick="unlockSection('sect_cam')" class="text-gray-400 hover:text-orange-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                </div>
                <div class="step-body {{ $lock2 && !empty($camData['lots']) ? 'step-content-locked' : '' }}">
                    <input type="text" name="cam_data[operator]" required placeholder="Operatore CAM" value="{{ $camData['operator'] ?? '' }}" class="mb-4">
                    <div id="cam_rows" class="space-y-3">
                        @foreach(($camData['lots'] ?? [['material_name' => '', 'material_id' => '', 'machine' => '']]) as $idx => $lot)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 p-4 bg-gray-50 rounded-2xl border relative">
                            <input type="text" name="cam_data[lots][{{$idx}}][material_name]" placeholder="Materiale" value="{{ $lot['material_name'] }}" required>
                            <input type="text" name="cam_data[lots][{{$idx}}][material_id]" placeholder="Lotto" value="{{ $lot['material_id'] }}" required>
                            <input type="text" name="cam_data[lots][{{$idx}}][machine]" placeholder="Macchina" value="{{ $lot['machine'] }}" required>
                            @if($idx > 0) <button type="button" onclick="this.parentElement.remove()" class="absolute -right-2 -top-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]">✕</button> @endif
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex justify-between items-center">
                        <button type="button" onclick="addRow('cam_rows', 'cam_data')" class="btn-add">+ Aggiungi Lotto</button>
                        <button type="button" onclick="submitStep('step_cam', 'sect_cam')" class="bg-enamel-orange text-white px-8 py-3 rounded-xl font-black uppercase text-xs tracking-widest shadow-lg save-btn {{ $s !== 'in_produzione' ? 'hidden' : '' }}">Salva Fase CAM</button>
                    </div>
                </div>
            </section>

            {{-- 3. LAB & MDR DEVICES --}}
            <section id="sect_lab" class="bg-white p-8 rounded-[2rem] border border-gray-100 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-black uppercase enamel-orange flex items-center gap-3"><span class="bg-enamel-orange text-white w-7 h-7 rounded-full flex items-center justify-center text-xs">3</span> LAB (Finitura)</h2>
                    <button type="button" onclick="unlockSection('sect_lab')" class="text-gray-400 hover:text-orange-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                </div>
                <div class="step-body {{ $lock3 && !empty($production->lab_operator) ? 'step-content-locked' : '' }}">
                    <div class="bg-orange-50/50 p-6 rounded-3xl border border-orange-100 mb-6">
                        <label class="text-[10px] font-black enamel-orange uppercase block mb-3 tracking-widest text-center">Identificazione Dispositivi Prodotti (Multi-MDR)</label>
                        
                        <div class="mb-4">
                            <input type="text" id="deviceSearch" onkeyup="filterDevices(this)" placeholder="🔍 Cerca nel catalogo dispositivi (CSV)..." class="search-highlight">
                        </div>

                        <div id="mdr_device_list" class="space-y-3">
                            @if(empty($currentDevices))
                                <div class="flex gap-2 items-start bg-white p-3 rounded-xl border">
                                    <select name="manufactured_devices_slugs[]" required class="flex-1 device-selector">
                                        <option value="">-- Seleziona Dispositivo --</option>
                                        @foreach($availableDevices as $dev)
                                            <option value="{{ $dev->slug }}">
                                                {{ $dev->name }} | {{ $dev->risk_class }} | {{ $dev->duration_use ?? 'N/D' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                @foreach($currentDevices as $idx => $curr)
                                <div class="flex gap-2 items-start bg-white p-3 rounded-xl border relative">
                                    <select name="manufactured_devices_slugs[]" required class="flex-1 device-selector">
                                        @foreach($availableDevices as $dev)
                                            <option value="{{ $dev->slug }}" {{ $curr['slug'] == $dev->slug ? 'selected' : '' }}>
                                                {{ $dev->name }} | {{ $dev->risk_class }} | {{ $dev->duration_use ?? 'N/D' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($idx > 0) <button type="button" onclick="this.parentElement.remove()" class="bg-red-100 text-red-600 p-2 rounded-lg">✕</button> @endif
                                </div>
                                @endforeach
                            @endif
                        </div>
                        <button type="button" onclick="addMdrRow()" class="btn-add mt-4">+ Aggiungi altro dispositivo prodotto</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="lab_color" required placeholder="Colore Finale" value="{{ $production->lab_color }}">
                        <input type="text" name="lab_operator" required placeholder="Operatore LAB" value="{{ $production->lab_operator }}">
                    </div>

                    <div id="lab_rows" class="space-y-3 mt-4">
                        <label class="text-[10px] font-black text-gray-400 uppercase">Materiali Finitura</label>
                        @foreach(($labMaterials ?: [['material_name' => '', 'material_id' => '']]) as $idx => $mat)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 p-4 bg-gray-50 rounded-2xl border relative">
                            <input type="text" name="lab_materials[{{$idx}}][material_name]" placeholder="Materiale" value="{{ $mat['material_name'] }}" required>
                            <input type="text" name="lab_materials[{{$idx}}][material_id]" placeholder="Lotto" value="{{ $mat['material_id'] }}" required>
                            @if($idx > 0) <button type="button" onclick="this.parentElement.remove()" class="absolute -right-2 -top-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]">✕</button> @endif
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex justify-between items-center">
                        <button type="button" onclick="addRow('lab_rows', 'lab_materials')" class="btn-add">+ Aggiungi Materiale</button>
                        <button type="button" onclick="submitStep('step_lab', 'sect_lab')" class="bg-enamel-orange text-white px-8 py-3 rounded-xl font-black uppercase text-xs tracking-widest shadow-lg save-btn {{ $s !== 'finalizzazione' ? 'hidden' : '' }}">Salva Fase LAB</button>
                    </div>
                </div>
            </section>

            {{-- 4. QC --}}
            <section id="sect_qc" class="bg-white p-8 rounded-[2.5rem] border border-gray-100 mb-6 transition-all {{ $lock4 ? 'step-content-locked' : '' }}">
                <h2 class="text-xl font-black uppercase enamel-orange mb-8 text-center">4. QC & Rilascio</h2>
                <div class="space-y-3 mb-8">
                    @foreach(['conformity' => 'Prescrizione', 'dimensional' => 'Dimensioni', 'functional' => 'Occlusione'] as $k => $label)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border">
                            <span class="text-[10px] font-black uppercase text-gray-500">{{ $label }}</span>
                            <div class="flex gap-4">
                                <label class="cursor-pointer">
                                    <input type="radio" name="qc_checklist[{{ $k }}]" value="OK" required class="hidden peer" {{ (isset($production->qc_checklist[$k]) && $production->qc_checklist[$k] == 'OK') ? 'checked' : '' }}>
                                    <div class="px-4 py-1.5 rounded-lg border-2 text-[10px] font-black peer-checked:bg-green-500 peer-checked:text-white transition uppercase">Conforme</div>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                <input type="text" name="qc_verifier" required placeholder="Firma Responsabile Qualità" value="{{ $production->qc_verifier }}" class="mb-6">
                
                {{-- ✨ AGGIORNAMENTO: Tasto con protezione anti-doppio-clic --}}
                <button type="button" id="finalizeBtn" onclick="submitStep('finalize', 'sect_qc')" class="w-full bg-gray-900 text-white py-5 rounded-3xl font-black uppercase tracking-widest text-sm shadow-xl hover:bg-black transition">Finalizza e Rilascia Fascicolo MDR</button>
            </section>
        </form>
    </div>

    <script>
        // ✨ Variabile di stato globale per impedire doppi invii
        let isProcessing = false;

        function filterDevices(input) {
            const filter = input.value.toLowerCase();
            const selectors = document.querySelectorAll('.device-selector');
            selectors.forEach(select => {
                const options = select.options;
                for (let i = 0; i < options.length; i++) {
                    const txt = options[i].text.toLowerCase();
                    options[i].style.display = txt.includes(filter) || options[i].value === "" ? "" : "none";
                }
            });
        }

        function unlockSection(id) {
            const sect = document.getElementById(id);
            sect.querySelector('.step-body').classList.remove('step-content-locked');
            const btn = sect.querySelector('.save-btn'); if(btn) btn.classList.remove('hidden');
            sect.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function addRow(containerId, prefix) {
            const container = document.getElementById(containerId);
            const index = container.children.length;
            const div = document.createElement('div');
            div.className = "grid grid-cols-1 md:grid-cols-" + (prefix === 'cam_data' ? '3' : '2') + " gap-3 p-4 bg-orange-50/30 rounded-2xl border border-orange-100 relative mt-2";
            if (prefix === 'cam_data') {
                div.innerHTML = `<input type="text" name="cam_data[lots][${index}][material_name]" placeholder="Materiale" required><input type="text" name="cam_data[lots][${index}][material_id]" placeholder="Lotto" required><input type="text" name="cam_data[lots][${index}][machine]" placeholder="Macchina" required><button type="button" onclick="this.parentElement.remove()" class="absolute -right-2 -top-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]">✕</button>`;
            } else {
                div.innerHTML = `<input type="text" name="lab_materials[${index}][material_name]" placeholder="Materiale" required><input type="text" name="lab_materials[${index}][material_id]" placeholder="Lotto" required><button type="button" onclick="this.parentElement.remove()" class="absolute -right-2 -top-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px]">✕</button>`;
            }
            container.appendChild(div);
        }

        function addMdrRow() {
            const container = document.getElementById('mdr_device_list');
            const original = container.querySelector('div');
            const clone = original.cloneNode(true);
            const select = clone.querySelector('select');
            select.selectedIndex = 0;
            if(!clone.querySelector('button')) {
                const btn = document.createElement('button');
                btn.type = "button"; btn.className = "bg-red-100 text-red-600 p-2 rounded-lg"; btn.innerText = "✕";
                btn.onclick = function() { this.parentElement.remove(); };
                clone.appendChild(btn);
            } else {
                clone.querySelector('button').onclick = function() { this.parentElement.remove(); };
            }
            container.appendChild(clone);
        }

        function submitStep(act, sectionId) {
            // ✨ Protezione: Se stiamo già inviando, esci subito
            if (isProcessing) return;

            const section = document.getElementById(sectionId);
            const reqs = section.querySelectorAll('input[required], textarea[required], select[required]');
            let ok = true;
            
            reqs.forEach(i => { 
                if (!i.value.trim() && i.offsetParent !== null) { 
                    i.style.borderColor = '#ff6f3f'; 
                    ok = false; 
                } else { 
                    i.style.borderColor = '#e5e7eb'; 
                } 
            });

            if (!ok) { 
                alert("Compila i campi obbligatori della fase."); 
                return; 
            }

            // ✨ Attiviamo lo stato di caricamento per l'utente
            isProcessing = true;
            const currentBtn = section.querySelector('button[type="button"]:last-child');
            if (currentBtn) {
                currentBtn.innerHTML = "Sincronizzazione in corso...";
                currentBtn.classList.add('btn-loading');
            }

            document.getElementById('actionInput').value = act;
            document.getElementById('productionForm').submit();
        }
    </script>
</body>
</html>