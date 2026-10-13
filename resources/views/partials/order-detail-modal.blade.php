<div id="detailModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-[70] hidden items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl rounded-[2.5rem] shadow-2xl overflow-hidden border-t-8 border-[#ff6f3f] flex flex-col max-h-[95vh]">
        
        <div class="flex border-b border-gray-100 bg-gray-50/50">
            <button onclick="switchSubModal('info')" id="tab_info" class="flex-1 py-4 text-[10px] font-black uppercase tracking-widest border-r bg-white transition">Dati Lavoro</button>
            <button onclick="switchSubModal('prod')" id="tab_prod" class="flex-1 py-4 text-[10px] font-black uppercase tracking-widest border-r hover:bg-white transition">Produzione</button>
            <button onclick="switchSubModal('arch')" id="tab_arch" class="flex-1 py-4 text-[10px] font-black uppercase tracking-widest border-r hover:bg-white transition">Documenti</button>
            <button onclick="switchSubModal('pms')" id="tab_pms" class="flex-1 py-4 text-[10px] font-black uppercase tracking-widest hover:bg-white transition hidden">Sorveglianza</button>
        </div>

        <div class="p-8 md:p-10 overflow-y-auto flex-1">
            
            <div id="sub_info" class="sub-modal space-y-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 id="detailCode" class="text-3xl font-black enamel-orange uppercase leading-none"></h3>
                        <p id="detailDate" class="text-[10px] font-black text-gray-400 uppercase mt-2 tracking-widest"></p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/50 p-6 rounded-[2rem] border border-gray-100">
                    <div class="space-y-4">
                        <div><label class="text-[9px] font-black text-gray-400 uppercase block mb-1">Riferimento Lavoro</label><p id="detailPatient" class="text-lg font-black uppercase text-gray-800"></p></div>
                        <div><label class="text-[9px] font-black text-gray-400 uppercase block mb-1">Tipo Dispositivo</label><p id="detailDevice" class="text-sm font-bold text-gray-600 uppercase"></p></div>
                        <div class="pt-2"><label class="text-[9px] font-black text-enamel-orange uppercase block mb-2">Pianificazione Consegna</label>
                            <div class="flex gap-2">
                                <input type="date" id="deliveryDateInput" class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold flex-1 shadow-sm {{ !$user->isStaff() ? 'opacity-70 pointer-events-none' : '' }}" {{ !$user->isStaff() ? 'readonly' : '' }}>
                                @if($user->isStaff())<button onclick="updateDeliveryDate(event)" class="bg-gray-900 text-white px-3 rounded-xl hover:bg-black transition flex items-center justify-center group"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></button>@endif
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div><label class="text-[9px] font-black text-gray-400 uppercase block mb-1">Cliente / Studio</label><p id="detailStudio" class="text-sm font-bold text-gray-800 uppercase"></p></div>
                        <div><label id="lblClinician" class="text-[9px] font-black text-gray-400 uppercase block mb-1">Responsabile / Referente</label><p id="detailClinician" class="text-sm font-bold text-gray-800 uppercase"></p><div class="mt-1"><span id="detailAlbo" class="bg-gray-200 text-gray-500 text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-tighter"></span></div></div>
                        @if($user->isStaff())
                        <div class="mt-6 pt-4 border-t border-gray-200/50 space-y-4">
                            <label class="flex items-center gap-3 cursor-pointer group"><input type="checkbox" id="isTrialInput" onchange="toggleTrialStatus()" class="sr-only peer"><div class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 transition-all after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5"></div><span class="text-[10px] font-black uppercase text-gray-400 group-hover:text-blue-600 transition">Lavoro In Prova</span></label>
                            <label class="flex items-center gap-3 cursor-pointer group"><input type="checkbox" id="isUrgentInput" onchange="togglePriorityStatus()" class="sr-only peer"><div class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-red-600 transition-all after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5"></div><span class="text-[10px] font-black uppercase text-gray-400 group-hover:text-red-600 transition">Priorità Urgente</span></label>
                        </div>

                        {{-- GDPR Art. 18 — Restrizione trattamento (solo staff) --}}
                        <div id="restrictionPanel" class="mt-4 pt-4 border-t border-gray-200/50">
                            <div id="restrictionActive" class="hidden bg-blue-50 border border-blue-200 rounded-2xl p-4 mb-3">
                                <div class="flex justify-between items-start gap-2">
                                    <div>
                                        <span class="text-[8px] font-black text-blue-700 uppercase tracking-widest block mb-1">⚠ Restrizione GDPR Art. 18 Attiva</span>
                                        <p id="restrictionReason" class="text-[9px] text-blue-600 font-bold"></p>
                                        <p id="restrictionReview" class="text-[8px] text-blue-500 mt-0.5"></p>
                                    </div>
                                    <form id="removeRestrictionForm" method="POST" class="shrink-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                onclick="return confirm('Rimuovere la restrizione GDPR Art. 18 dall\'ordine?')"
                                                class="text-[8px] font-black uppercase text-red-500 border border-red-200 px-3 py-1.5 rounded-xl hover:bg-red-50 transition">
                                            Rimuovi
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div id="restrictionForm" class="hidden">
                                <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-2">Applica Restrizione Art. 18</p>
                                <form id="applyRestrictionForm" method="POST">
                                    @csrf
                                    <div class="space-y-2">
                                        <select name="restricted_reason" required
                                                class="w-full border border-gray-200 rounded-xl px-3 py-2 text-[9px] bg-white focus:outline-none focus:ring-1 focus:ring-blue-400">
                                            <option value="">— Motivo restrizione —</option>
                                            <option value="accuracy_dispute">Contestazione esattezza dati (Art. 18(1)(a))</option>
                                            <option value="unlawful_processing">Trattamento illecito (Art. 18(1)(b))</option>
                                            <option value="legal_claim">Difesa diritto giudiziario (Art. 18(1)(c))</option>
                                            <option value="opposition_pending">Opposizione Art. 21 pendente (Art. 18(1)(d))</option>
                                        </select>
                                        <input type="date" name="restriction_review_date" required
                                               class="w-full border border-gray-200 rounded-xl px-3 py-2 text-[9px] bg-white focus:outline-none focus:ring-1 focus:ring-blue-400"
                                               placeholder="Data revisione">
                                        <input type="number" name="restriction_case_id" min="1"
                                               class="w-full border border-gray-200 rounded-xl px-3 py-2 text-[9px] bg-white focus:outline-none focus:ring-1 focus:ring-blue-400"
                                               placeholder="Rif. GDPR # (opzionale)">
                                        <button type="submit"
                                                class="w-full bg-blue-600 text-white py-2 rounded-xl text-[8px] font-black uppercase tracking-widest hover:bg-blue-700 transition">
                                            Applica Restrizione
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <button id="toggleRestrictionFormBtn" onclick="toggleRestrictionForm()"
                                    class="text-[8px] font-black uppercase text-gray-400 hover:text-blue-600 transition tracking-widest hidden">
                                + Applica Restrizione Art. 18
                            </button>
                        </div>

                        {{-- MDR Art. 87 — Segnalazione incidenti (solo staff/PRRC) --}}
                        <div id="incidentPanel" class="mt-4 pt-4 border-t border-gray-200/50">

                            {{-- Incidente attivo (aperto) --}}
                            <div id="incidentActive" class="hidden bg-red-50 border border-red-200 rounded-2xl p-4 mb-3">
                                <div class="flex justify-between items-start gap-2">
                                    <div>
                                        <span class="text-[8px] font-black text-red-700 uppercase tracking-widest block mb-1">🚨 Incidente MDR Art. 87 Aperto</span>
                                        <p id="incidentSeverityLabel" class="text-xs font-bold text-red-800"></p>
                                        <p id="incidentDeadlineLabel" class="text-[8px] text-red-500 mt-0.5"></p>
                                    </div>
                                    <button onclick="toggleResolveForm()"
                                            class="text-[8px] font-black uppercase text-red-400 hover:text-red-700 transition tracking-widest shrink-0">
                                        Chiudi Indagine
                                    </button>
                                </div>
                                <div id="resolveIncidentForm" class="hidden mt-3">
                                    <form id="resolveIncidentFormEl" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <textarea name="resolution_notes" rows="2" placeholder="Causa accertata, misure adottate, esito indagine... (min. 20 caratteri)"
                                                  class="w-full text-[9px] bg-white border border-red-200 rounded-xl p-2 mb-2 focus:ring-1 focus:ring-red-400 outline-none font-medium" required minlength="20" maxlength="3000"></textarea>
                                        <button type="submit" class="bg-red-600 text-white text-[8px] font-black uppercase tracking-widest px-4 py-1.5 rounded-xl hover:bg-red-700 transition">
                                            Chiudi Indagine
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Incidente chiuso --}}
                            <div id="incidentResolved" class="hidden bg-green-50 border border-green-200 rounded-2xl p-4 mb-3">
                                <span class="text-[8px] font-black text-green-700 uppercase tracking-widest block mb-1">✅ Incidente MDR Chiuso</span>
                                <p id="incidentResolvedLabel" class="text-[8px] text-green-600"></p>
                            </div>

                            {{-- Nessun incidente — form segnalazione --}}
                            <div id="reportIncidentForm" class="hidden">
                                <p class="text-[8px] font-black text-gray-400 uppercase tracking-widest mb-2">Segnala Incidente MDR Art. 87</p>
                                <form id="reportIncidentFormEl" method="POST">
                                    @csrf
                                    <select name="severity" class="w-full text-[9px] bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 mb-2 font-bold focus:ring-1 focus:ring-red-400 outline-none" required>
                                        <option value="">— Gravità incidente —</option>
                                        <option value="imminente">IMMINENTE — pericolo immediato (scad. 2 gg)</option>
                                        <option value="grave">GRAVE — incidente serio (scad. 15 gg)</option>
                                        <option value="non_grave">NON GRAVE (scad. 30 gg)</option>
                                    </select>
                                    <textarea name="description" rows="2" placeholder="Descrizione dettagliata dell'incidente... (min. 20 caratteri)"
                                              class="w-full text-[9px] bg-gray-50 border border-gray-200 rounded-xl p-2 mb-2 focus:ring-1 focus:ring-red-400 outline-none font-medium" required minlength="20" maxlength="3000"></textarea>
                                    <textarea name="immediate_action" rows="1" placeholder="Azioni immediate adottate (opzionale)"
                                              class="w-full text-[9px] bg-gray-50 border border-gray-200 rounded-xl p-2 mb-2 focus:ring-1 focus:ring-orange-400 outline-none font-medium" maxlength="1000"></textarea>
                                    <button type="submit" class="bg-red-600 text-white text-[8px] font-black uppercase tracking-widest px-4 py-1.5 rounded-xl hover:bg-red-700 transition">
                                        Segnala Incidente
                                    </button>
                                </form>
                            </div>

                            <button id="toggleIncidentFormBtn" onclick="toggleIncidentForm()"
                                    class="text-[8px] font-black uppercase text-gray-400 hover:text-red-600 transition tracking-widest hidden">
                                + Segnala Incidente MDR Art. 87
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div id="sub_prod" class="sub-modal hidden space-y-6">
                <div class="flex justify-between items-center"><span id="detailStatusBadge" class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest"></span></div>
                <div id="prescriptionSection" class="hidden bg-blue-50/50 p-6 rounded-[2rem] border border-blue-100"><label class="text-[9px] font-black text-blue-400 uppercase tracking-widest block mb-2">Specifiche Tecniche</label><p id="detailDesign" class="text-xs font-bold text-blue-900 leading-relaxed italic"></p></div>
                <a id="stepperBtn" href="#" class="block w-full bg-blue-600 text-white text-center py-4 rounded-2xl font-black uppercase text-xs tracking-widest shadow-lg hover:bg-blue-700 transition">Gestisci Fasi Produzione</a>
            </div>

            <div id="sub_arch" class="sub-modal hidden space-y-6">
                <div class="mb-8">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-4 ml-2">Asset Digitali & Scan Check</label>
                    <div id="fileScanList" class="space-y-2 bg-gray-50/50 p-6 rounded-3xl border border-gray-100 max-h-48 overflow-y-auto">
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-4 ml-2">Fascicolo Documentale MDR</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a id="downloadPdfBtn" href="#" class="doc-btn"><span class="text-[9px] font-black uppercase">Richiesta Lavorazione</span></a>
                        <a id="downloadPrescriptionBtn" href="#" class="doc-btn hidden"><span class="text-[9px] font-black uppercase">Prescrizione / Ordine</span></a>
                        <a id="downloadReportBtn" href="#" class="doc-btn hidden"><span class="text-[9px] font-black uppercase">Scheda Tecnica</span></a>
                        <a id="downloadConformityBtn" href="#" class="doc-btn hidden"><span class="text-[9px] font-black uppercase">Dichiarazione Conformità</span></a>
                        <a id="downloadLabelBtn" href="#" class="doc-btn hidden"><span class="text-[9px] font-black uppercase">Etichetta Spedizione</span></a>
                        <a id="downloadPatientInfoBtn" href="#" class="doc-btn hidden"><span class="text-[9px] font-black uppercase">Informativa Paziente</span></a>
                        <a id="downloadPmsBtn" href="#" class="doc-btn hidden border-slate-400 bg-slate-50 hover:bg-slate-100"><span class="text-[9px] font-black uppercase text-slate-700">Rapporto PMS</span></a>
                    </div>
                </div>

                <div class="mt-8 pt-8 border-t border-gray-100">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-4">Log & Comunicazioni</label>
                    <div id="commentFeed" class="max-h-56 overflow-y-auto bg-gray-50/50 p-6 rounded-3xl border border-gray-100 mb-4 text-[10px] space-y-4"></div>
                    <div class="flex flex-col gap-3">
                        <textarea id="commentBody" rows="2" placeholder="Scrivi un aggiornamento..." class="w-full bg-white border border-gray-200 rounded-2xl p-4 text-xs font-bold outline-none focus:ring-2 focus:ring-[#ff6f3f] shadow-sm"></textarea>
                        <div class="flex justify-between items-center px-1">
                            @if($user->isStaff())<label class="flex items-center gap-2 cursor-pointer group"><input type="checkbox" id="isInternalComment" class="rounded border-gray-300 text-enamel-orange focus:ring-[#ff6f3f] h-4 w-4"><span class="text-[10px] font-black uppercase text-gray-400 group-hover:text-yellow-600 transition">Nota Interna Staff</span></label>@endif
                            <button onclick="submitComment()" class="bg-gray-900 text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition hover:bg-black active:scale-95 shadow-lg">Invia Messaggio</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="sub_pms" class="sub-modal hidden space-y-6">

                {{-- HEADER: stato + scadenza (dinamico via JS) --}}
                <div id="pmsHeaderBox" class="p-6 rounded-[2rem] border-b-4 shadow-xl transition-colors duration-500">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="text-[8px] font-black uppercase tracking-[0.2em] opacity-60">Stato PMS</span>
                            <div id="pmsStatusLabel" class="text-lg font-black uppercase mt-1">ATTIVO</div>
                            <div id="pmsIncidentSeverityBadge" class="hidden mt-1 text-[9px] font-black uppercase bg-white/20 px-2 py-0.5 rounded inline-block"></div>
                        </div>
                        <div class="text-right">
                            <span id="pmsDeadlineLabel" class="text-[8px] font-black uppercase tracking-[0.2em] opacity-60">Scadenza PSUR</span>
                            <div id="pmsDeadline" class="text-xs font-bold mt-1"></div>
                            <div id="pmsMinisteroDeadline" class="hidden mt-2 text-[9px] font-black bg-white text-red-700 px-2 py-1 rounded-lg"></div>
                        </div>
                    </div>
                    {{-- Dati incidente aperto --}}
                    <div id="pmsIncidentMeta" class="hidden mt-3 pt-3 border-t border-white/20 grid grid-cols-2 gap-2 text-[8px]">
                        <div><span class="opacity-60 uppercase">Segnalato il</span><div id="pmsIncidentReportedAt" class="font-bold mt-0.5"></div></div>
                        <div class="text-right"><span class="opacity-60 uppercase">Scadenza Ministero</span><div id="pmsIncidentDeadline" class="font-bold mt-0.5"></div></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4">
                    {{-- Feedback clinico periodico --}}
                    <div id="pmsFeedbackSection" class="p-6 bg-green-50 border border-green-100 rounded-[2rem]">
                        <label class="text-[9px] font-black text-green-600 uppercase block mb-3">Feedback Periodico (PMS Data Collection Art. 83)</label>
                        <div class="flex gap-2">
                            <input type="text" id="clinicalFeedbackInput" placeholder="Feedback tecnico sul dispositivo..." class="flex-1 bg-white border border-green-200 rounded-xl px-4 py-2 text-xs font-bold shadow-sm">
                            <button onclick="recordPmsFeedback()" class="bg-green-600 text-white px-4 rounded-xl text-[9px] font-black uppercase hover:bg-green-700 transition">Registra</button>
                        </div>
                    </div>

                    {{-- Apertura incidente (visibile solo se nessun incidente aperto) --}}
                    <div id="pmsIncidentSection" class="p-6 bg-red-50 border border-red-100 rounded-[2rem]">
                        <div class="flex justify-between items-center mb-3">
                            <label class="text-[9px] font-black text-red-600 uppercase">Segnalazione Incidente (Art. 87 MDR)</label>
                            <span class="text-[7px] font-black bg-red-600 text-white px-2 py-0.5 rounded uppercase">Urgente</span>
                        </div>
                        <div class="mb-3">
                            <label class="text-[8px] font-black text-red-500 uppercase block mb-1">Gravità *</label>
                            <select id="incidentSeveritySelect" class="w-full bg-white border border-red-200 rounded-xl px-3 py-2 text-xs font-bold">
                                <option value="">— Seleziona gravità —</option>
                                <option value="imminente">Rischio grave imminente — notifica entro 2 giorni</option>
                                <option value="grave">Incidente grave — notifica entro 15 giorni</option>
                                <option value="non_grave">Incidente non grave — notifica entro 30 giorni</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="text-[8px] font-black text-red-500 uppercase block mb-1">Descrizione incidente *</label>
                            <textarea id="incidentDescriptionInput" rows="2" placeholder="Descrivi l'evento avverso osservato..." class="w-full bg-white border border-red-200 rounded-xl px-3 py-2 text-xs font-bold resize-none"></textarea>
                        </div>
                        <button onclick="triggerVigilance()" class="w-full bg-white border-2 border-red-600 text-red-600 py-3 rounded-2xl text-[9px] font-black uppercase hover:bg-red-600 hover:text-white transition shadow-sm">
                            Segnala Incidente
                        </button>
                    </div>

                    {{-- Chiusura incidente (visibile solo se incidente aperto, solo staff) --}}
                    <div id="pmsResolutionSection" class="hidden p-6 bg-slate-100 border border-slate-200 rounded-[2rem]">
                        <label class="text-[9px] font-black text-slate-600 uppercase block mb-3">Chiusura Indagine (solo Staff)</label>
                        <div class="mb-3">
                            <label class="text-[8px] font-black text-slate-500 uppercase block mb-1">Note di risoluzione obbligatorie *</label>
                            <textarea id="resolutionNotesInput" rows="3" placeholder="Indicare: causa accertata, misure correttive adottate, esito dell'indagine (min. 20 caratteri)..." class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold resize-none"></textarea>
                        </div>
                        <button onclick="resolveVigilance()" class="w-full bg-slate-800 text-white py-3 rounded-2xl text-[9px] font-black uppercase hover:bg-black transition shadow-lg">
                            Chiudi Indagine Tecnica
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 bg-gray-50 border-t border-gray-100 flex gap-3"><button onclick="toggleDetailModal(false)" class="flex-1 bg-white text-gray-400 py-4 rounded-2xl font-black uppercase text-[10px] border border-gray-200 hover:text-black transition shadow-sm">Chiudi Dettaglio</button></div>
    </div>
</div>

<script>
    let currentOrderCode = ''; 

    function switchSubModal(section) {
        document.querySelectorAll('.sub-modal').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('[id^="tab_"]').forEach(el => { el.classList.remove('bg-white', 'text-slate-800', 'enamel-orange'); el.classList.add('hover:bg-white', 'text-gray-400'); });
        document.getElementById('sub_' + section).classList.remove('hidden');
        document.getElementById('tab_' + section).classList.add('bg-white');
        document.getElementById('tab_' + section).classList.remove('hover:bg-white', 'text-gray-400');
    }

    function showQuickDetails(id) {
        if (typeof ordersData === 'undefined') return;
        const order = ordersData.find(o => o.id == id);
        if(!order) return;
        
        currentOrderCode = order.code; 
        switchSubModal('info');

        document.getElementById('detailCode').innerText = order.code;
        document.getElementById('detailDate').innerText = "RICEVUTO IL: " + new Date(order.created_at).toLocaleString('it-IT');
        document.getElementById('detailPatient').innerText = order.patient_ref;
        document.getElementById('detailDevice').innerText = order.device_type.replace(/_/g, ' ');
        document.getElementById('detailStudio').innerText = order.studio?.name || 'N/D';
        
        // --- LOGICA MULTI-PRESCRITTORE RECUPERATA E ADATTATA ---
        const clinicianName = order.address?.dentist_name || order.studio?.dentist_name || 'N/D';
        const itca = order.studio?.itca_code;
        const license = order.address?.dentist_license || order.studio?.dentist_license || 'N/D';
        const prov = order.address?.albo_provincia || order.studio?.albo_provincia || '';

        if(itca) {
             document.getElementById('lblClinician').innerText = "RESPONSABILE TECNICO";
             document.getElementById('detailClinician').innerText = `${clinicianName}`;
             document.getElementById('detailAlbo').innerText = `ITCA: ${itca}`;
        } else {
             document.getElementById('lblClinician').innerText = "CLINICO PRESCRITTORE";
             document.getElementById('detailClinician').innerText = `Dott. ${clinicianName}`;
             document.getElementById('detailAlbo').innerText = `ALBO: ${license} (${prov})`;
        }
        // ------------------------------------------------------

        if(order.delivery_date) { document.getElementById('deliveryDateInput').value = order.delivery_date.split(' ')[0]; } 
        else { document.getElementById('deliveryDateInput').value = ''; }

        const trialInput = document.getElementById('isTrialInput');
        if(trialInput) trialInput.checked = !!order.is_trial;
        const priorityInput = document.getElementById('isUrgentInput');
        if(priorityInput) priorityInput.checked = (order.priority === 'high');

        const badge = document.getElementById('detailStatusBadge');
        badge.innerText = order.status.replace(/_/g, ' ');
        badge.className = `px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest ${getStatusClass(order.status)}`;

        if(order.design_features) {
            document.getElementById('prescriptionSection').classList.remove('hidden');
            document.getElementById('detailDesign').innerText = order.design_features;
        } else { document.getElementById('prescriptionSection').classList.add('hidden'); }

        document.getElementById('stepperBtn').href = `/orders/${order.code}/production`;
        document.getElementById('downloadPdfBtn').href = `/orders/${order.code}/download-request-pdf`;
        const btnPresc = document.getElementById('downloadPrescriptionBtn');
        if(order.validated_at) { btnPresc.classList.remove('hidden'); btnPresc.href = `/orders/${order.code}/download-prescription`; } else { btnPresc.classList.add('hidden'); }
        const btnReport = document.getElementById('downloadReportBtn');
        const btnConf = document.getElementById('downloadConformityBtn');
        const btnLabel = document.getElementById('downloadLabelBtn');
        const btnInfo = document.getElementById('downloadPatientInfoBtn');
        if(['pronto_consegna', 'archived'].includes(order.status)) {
            btnReport.classList.remove('hidden'); btnReport.href = `/orders/${order.code}/download-production-report`;
            btnConf.classList.remove('hidden'); btnConf.href = `/orders/${order.code}/download-conformity`;
            btnLabel.classList.remove('hidden'); btnLabel.href = `/orders/${order.code}/download-label`;
            btnInfo.classList.remove('hidden'); btnInfo.href = `/orders/${order.code}/download-patient-info`;
        } else {
            if(['finalizzazione', 'controllo_qualita'].includes(order.status)) {
                btnReport.classList.remove('hidden'); btnReport.href = `/orders/${order.code}/download-production-report`;
            } else { btnReport.classList.add('hidden'); }
            btnConf.classList.add('hidden'); btnLabel.classList.add('hidden'); btnInfo.classList.add('hidden');
        }
        const btnPms = document.getElementById('downloadPmsBtn');
        if(order.status === 'archived') { btnPms.classList.remove('hidden'); btnPms.href = `/orders/${order.code}/download-pms-report`; } else { btnPms.classList.add('hidden'); }

        const fileList = document.getElementById('fileScanList');
        if(order.files && order.files.length) {
            fileList.innerHTML = order.files.map(f => {
                const isInfected = f.scan_status === 'infected';
                return `<div class="flex justify-between items-center p-3 rounded-2xl ${isInfected ? 'bg-red-100 border border-red-200' : 'bg-white shadow-sm'}"><span class="text-[10px] font-bold ${isInfected ? 'text-red-700' : 'text-gray-600'}">${f.original_name}</span><span class="text-[8px] font-black px-2 py-0.5 rounded uppercase ${isInfected ? 'bg-red-600 text-white animate-pulse' : 'bg-green-500 text-white'}">${isInfected ? 'MINACCIA DISTRUTTA' : 'SICURO'}</span></div>`;
            }).join('');
        } else { fileList.innerHTML = '<p class="text-center text-[9px] opacity-30 py-4 font-black uppercase">Nessun asset caricato</p>'; }

        const tabPms = document.getElementById('tab_pms');
        if(order.status === 'archived') {
            tabPms.classList.remove('hidden');
            const status = order.pms_status || 'active';
            const header         = document.getElementById('pmsHeaderBox');
            const statusLabel    = document.getElementById('pmsStatusLabel');
            const severityBadge  = document.getElementById('pmsIncidentSeverityBadge');
            const deadlineLabel  = document.getElementById('pmsDeadlineLabel');
            const deadlineEl     = document.getElementById('pmsDeadline');
            const ministerioEl   = document.getElementById('pmsMinisteroDeadline');
            const incidentMeta   = document.getElementById('pmsIncidentMeta');
            const reportedAtEl   = document.getElementById('pmsIncidentReportedAt');
            const incidentDlEl   = document.getElementById('pmsIncidentDeadline');
            const resSection     = document.getElementById('pmsResolutionSection');
            const feedbackSection= document.getElementById('pmsFeedbackSection');
            const incidentSection= document.getElementById('pmsIncidentSection');

            if(status === 'incident_reported') {
                // Header rosso allerta
                header.className = "p-6 rounded-[2rem] border-b-4 shadow-xl bg-red-600 text-white";
                statusLabel.innerText = '⚠️ VIGILANZA ATTIVA';

                // Badge gravità
                const severityMap = { imminente: 'RISCHIO IMMINENTE', grave: 'INCIDENTE GRAVE', non_grave: 'INCIDENTE NON GRAVE' };
                if(order.incident_severity) {
                    severityBadge.innerText = severityMap[order.incident_severity] ?? order.incident_severity.toUpperCase();
                    severityBadge.classList.remove('hidden');
                }

                // Scadenza Ministero
                deadlineLabel.innerText = 'Scadenza PSUR';
                deadlineEl.innerText = order.pms_next_review_at ? new Date(order.pms_next_review_at).toLocaleDateString('it-IT') : 'DA DEFINIRE';
                if(order.incident_notification_deadline) {
                    const dl = new Date(order.incident_notification_deadline);
                    const daysLeft = Math.ceil((dl - new Date()) / 86400000);
                    const daysText = daysLeft < 0 ? 'SCADUTA' : (daysLeft === 0 ? 'OGGI' : `${daysLeft} giorni`);
                    ministerioEl.innerText = `⚠️ Notifica Ministero: ${dl.toLocaleDateString('it-IT')} (${daysText})`;
                    ministerioEl.classList.remove('hidden');
                }

                // Metadati incidente
                if(order.incident_reported_at) {
                    reportedAtEl.innerText = new Date(order.incident_reported_at).toLocaleDateString('it-IT');
                    incidentDlEl.innerText = order.incident_notification_deadline ? new Date(order.incident_notification_deadline).toLocaleDateString('it-IT') : 'N/D';
                    incidentMeta.classList.remove('hidden');
                }

                // Mostra risoluzione, nascondi apertura
                resSection.classList.remove('hidden');
                feedbackSection.classList.add('opacity-50', 'pointer-events-none');
                incidentSection.classList.add('hidden');

            } else {
                // Header normale
                header.className = "p-6 rounded-[2rem] border-b-4 shadow-xl bg-slate-900 text-white";
                const statusLabels = { active: 'SOTTO SORVEGLIANZA', review_due: 'REVISIONE SCADUTA', deactivated: 'CICLO VITA CONCLUSO' };
                statusLabel.innerText = statusLabels[status] ?? status.toUpperCase();
                severityBadge.classList.add('hidden');
                ministerioEl.classList.add('hidden');
                incidentMeta.classList.add('hidden');

                deadlineLabel.innerText = 'Scadenza PSUR';
                deadlineEl.innerText = order.pms_next_review_at ? new Date(order.pms_next_review_at).toLocaleDateString('it-IT') : 'DA DEFINIRE';

                resSection.classList.add('hidden');
                feedbackSection.classList.remove('opacity-50', 'pointer-events-none');
                incidentSection.classList.remove('hidden');
            }
        } else { tabPms.classList.add('hidden'); }

        // --- GDPR Art. 18 restriction panel (staff only) ---
        @if($user->isStaff())
        const restrictionActive = document.getElementById('restrictionActive');
        const restrictionFormDiv = document.getElementById('restrictionForm');
        const toggleBtn = document.getElementById('toggleRestrictionFormBtn');
        const applyForm = document.getElementById('applyRestrictionForm');
        const removeForm = document.getElementById('removeRestrictionForm');

        applyForm.action = `/orders/${order.code}/restrict`;
        removeForm.action = `/orders/${order.code}/restrict`;

        if (order.restricted_at) {
            const reasonLabels = {
                accuracy_dispute: 'Contestazione esattezza dati',
                unlawful_processing: 'Trattamento illecito',
                legal_claim: 'Difesa diritto giudiziario',
                opposition_pending: 'Opposizione Art. 21 pendente'
            };
            document.getElementById('restrictionReason').innerText =
                (reasonLabels[order.restricted_reason] ?? order.restricted_reason) +
                (order.restriction_case_id ? ' — Rif. GDPR #' + order.restriction_case_id : '');
            document.getElementById('restrictionReview').innerText =
                'Revisione: ' + (order.restriction_review_date ? new Date(order.restriction_review_date).toLocaleDateString('it-IT') : 'N/D');
            restrictionActive.classList.remove('hidden');
            restrictionFormDiv.classList.add('hidden');
            toggleBtn.classList.add('hidden');
        } else {
            restrictionActive.classList.add('hidden');
            restrictionFormDiv.classList.add('hidden');
            toggleBtn.classList.remove('hidden');
            toggleBtn.innerText = '+ Applica Restrizione Art. 18';
        }
        @endif

        // --- MDR Art. 87 incident panel (staff only) ---
        @if($user->isStaff())
        const incidentActiveDiv    = document.getElementById('incidentActive');
        const incidentResolvedDiv  = document.getElementById('incidentResolved');
        const reportIncidentFormDiv = document.getElementById('reportIncidentForm');
        const incidentToggleBtn    = document.getElementById('toggleIncidentFormBtn');
        const reportFormEl         = document.getElementById('reportIncidentFormEl');
        const resolveFormEl        = document.getElementById('resolveIncidentFormEl');

        reportFormEl.action  = `/orders/${order.code}/report-incident`;
        resolveFormEl.action = `/orders/${order.code}/resolve-incident`;

        const severityLabels = { imminente: 'IMMINENTE (scad. 2 gg)', grave: 'GRAVE (scad. 15 gg)', non_grave: 'NON GRAVE (scad. 30 gg)' };

        if (order.incident_reported_at && !order.incident_resolved_at) {
            document.getElementById('incidentSeverityLabel').innerText =
                'Gravità: ' + (severityLabels[order.incident_severity] ?? order.incident_severity?.toUpperCase() ?? 'N/D');
            document.getElementById('incidentDeadlineLabel').innerText =
                'Scadenza notifica MDR: ' + (order.incident_notification_deadline
                    ? new Date(order.incident_notification_deadline).toLocaleDateString('it-IT') : 'N/D');
            incidentActiveDiv.classList.remove('hidden');
            incidentResolvedDiv.classList.add('hidden');
            reportIncidentFormDiv.classList.add('hidden');
            incidentToggleBtn.classList.add('hidden');
        } else if (order.incident_resolved_at) {
            document.getElementById('incidentResolvedLabel').innerText =
                'Chiuso il ' + new Date(order.incident_resolved_at).toLocaleDateString('it-IT') +
                ' — Gravità: ' + (severityLabels[order.incident_severity] ?? order.incident_severity?.toUpperCase() ?? '');
            incidentResolvedDiv.classList.remove('hidden');
            incidentActiveDiv.classList.add('hidden');
            reportIncidentFormDiv.classList.add('hidden');
            incidentToggleBtn.classList.add('hidden');
        } else {
            incidentActiveDiv.classList.add('hidden');
            incidentResolvedDiv.classList.add('hidden');
            reportIncidentFormDiv.classList.add('hidden');
            incidentToggleBtn.classList.remove('hidden');
        }
        @endif
        // ------------------------------------------------

        loadComments(order.code);
        toggleDetailModal(true);
    }

    function toggleRestrictionForm() {
        const formDiv = document.getElementById('restrictionForm');
        const btn = document.getElementById('toggleRestrictionFormBtn');
        const isHidden = formDiv.classList.contains('hidden');
        formDiv.classList.toggle('hidden', !isHidden);
        btn.innerText = isHidden ? '− Annulla' : '+ Applica Restrizione Art. 18';
    }

    function toggleIncidentForm() {
        const formDiv = document.getElementById('reportIncidentForm');
        const btn     = document.getElementById('toggleIncidentFormBtn');
        const isHidden = formDiv.classList.contains('hidden');
        formDiv.classList.toggle('hidden', !isHidden);
        btn.innerText = isHidden ? '− Annulla' : '+ Segnala Incidente MDR Art. 87';
    }

    function toggleResolveForm() {
        const formDiv = document.getElementById('resolveIncidentForm');
        formDiv.classList.toggle('hidden');
    }

    async function recordPmsFeedback() {
        const text = document.getElementById('clinicalFeedbackInput').value.trim();
        if(!text) return;
        try {
            const res = await fetch(`/orders/${currentOrderCode}/pms-update`, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ action: 'record_feedback', feedback: text }) });
            if(res.ok) { document.getElementById('clinicalFeedbackInput').value = ''; loadComments(currentOrderCode); alert("Feedback registrato."); location.reload(); } 
            else { const err = await res.json(); alert(err.error || "Errore."); }
        } catch (e) { alert("Errore rete."); }
    }
    async function triggerVigilance() {
        const severity = document.getElementById('incidentSeveritySelect').value;
        const description = document.getElementById('incidentDescriptionInput').value.trim();
        if(!severity) { alert("Seleziona la gravità dell'incidente."); return; }
        if(!description) { alert("La descrizione dell'incidente è obbligatoria."); return; }
        if(!confirm("Confermi la segnalazione incidente? Il PRRC riceverà una notifica immediata.")) return;
        try {
            const res = await fetch(`/orders/${currentOrderCode}/pms-update`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ action: 'trigger_incident', severity: severity, feedback: description })
            });
            const data = await res.json();
            if(res.ok) { location.reload(); }
            else { alert(data.error || "Errore durante la segnalazione."); }
        } catch (e) { alert("Errore di rete."); }
    }
    async function resolveVigilance() {
        const notes = document.getElementById('resolutionNotesInput').value.trim();
        if(notes.length < 20) { alert("Le note di risoluzione sono obbligatorie (min. 20 caratteri).\nIndicare: causa accertata, misure correttive adottate, esito dell'indagine."); return; }
        if(!confirm("Confermi la chiusura dell'indagine tecnica? L'operazione è irreversibile.")) return;
        try {
            const res = await fetch(`/orders/${currentOrderCode}/pms-update`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ action: 'resolve_incident', resolution_notes: notes })
            });
            const data = await res.json();
            if(res.ok) { location.reload(); }
            else { alert(data.error || "Errore durante la chiusura."); }
        } catch (e) { alert("Errore di rete."); }
    }
    async function loadComments(code) {
        const feed = document.getElementById('commentFeed');
        try {
            const res = await fetch(`/orders/${code}/comments`);
            const data = await res.json();
            feed.innerHTML = data.length ? data.map(c => {
                if(c.author === 'System' || c.is_system) { return `<div class="text-center text-[8px] uppercase font-black text-gray-300 py-1 tracking-widest">${c.body} — ${new Date(c.created_at).toLocaleString()}</div>`; }
                const isStaff = c.is_staff; const isInternal = c.is_internal; const alignment = isStaff ? 'items-end' : 'items-start';
                const colorClass = isInternal ? 'bg-yellow-50 border-yellow-200 text-yellow-800' : (isStaff ? 'bg-blue-600 text-white border-transparent' : 'bg-white text-gray-800 border-gray-100');
                return `<div class="flex flex-col ${alignment}"><div class="max-w-[80%] p-3 rounded-2xl border ${colorClass} text-xs shadow-sm"><div class="flex justify-between items-center gap-4 mb-1 opacity-70 text-[7px] font-black uppercase"><span>${isInternal ? 'Nota Interna' : c.author}</span><span>${new Date(c.created_at).toLocaleString()}</span></div><p class="font-bold leading-tight">${c.body}</p></div></div>`;
            }).join('') : '<p class="text-center py-4 uppercase font-black opacity-20">Nessun log disponibile</p>';
            feed.scrollTop = feed.scrollHeight;
        } catch (e) { feed.innerHTML = 'Errore caricamento log.'; }
    }
    async function submitComment() {
        const body = document.getElementById('commentBody').value.trim();
        if(!body) return;
        const isInternalEl = document.getElementById('isInternalComment');
        try {
            const res = await fetch(`/orders/${currentOrderCode}/comments`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ body, is_internal: isInternalEl?.checked || false }) });
            if(res.ok) { document.getElementById('commentBody').value = ''; if(isInternalEl) isInternalEl.checked = false; loadComments(currentOrderCode); }
        } catch (e) { alert("Errore invio."); }
    }
    function toggleDetailModal(show) {
        const modal = document.getElementById('detailModal');
        if(show) { modal.classList.remove('hidden'); modal.classList.add('flex'); }
        else { modal.classList.add('hidden'); modal.classList.remove('flex'); }
    }
    function getStatusClass(status) {
        const classes = { 'draft': 'bg-gray-100', 'validated': 'bg-green-100 text-green-700', 'in_produzione': 'bg-blue-600 text-white', 'pronto_consegna': 'bg-green-600 text-white', 'archived': 'bg-slate-800 text-white' };
        return classes[status] || 'bg-gray-100 text-gray-500';
    }
    async function toggleTrialStatus() { await fetch(`/orders/${currentOrderCode}/toggle-trial`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }); location.reload(); }
    async function togglePriorityStatus() { await fetch(`/orders/${currentOrderCode}/toggle-priority`, { method: 'PATCH', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }); location.reload(); }
    async function updateDeliveryDate(event) {
        const newDate = document.getElementById('deliveryDateInput').value;
        if(!newDate) return;
        const btn = event.currentTarget;
        btn.classList.add('opacity-50', 'pointer-events-none');
        try {
            const res = await fetch(`/orders/${currentOrderCode}/update-delivery-date`, { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ delivery_date: newDate }) });
            if(res.ok) location.reload();
        } catch (e) { alert("Errore."); btn.classList.remove('opacity-50', 'pointer-events-none'); }
    }
</script>