<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RETE VENDITA — Agenti/Rappresentanti
 *
 * Struttura:
 *   oc_enamel_agents           → anagrafica agente/rappresentante
 *   oc_enamel_studios.agent_id → agente primario assegnato allo studio (FK nullable)
 *
 * Un agente può gestire N studi (portfolio clienti).
 * Uno studio ha al massimo UN agente primario (per semplicità v1 — estendibile a pivot se necessario).
 *
 * L'agente non ha accesso diretto alla piattaforma come utente autenticato in v1.
 * In v2 potrà avere un account Nextcloud nel gruppo 'Agenti' con dashboard dedicata.
 *
 * Le commissioni sono calcolate sugli ordini fatturati degli studi nel portfolio dell'agente.
 * Il calcolo commissioni è fuori scope di questa migration — avviene a livello applicativo.
 *
 * Note GDPR: gli agenti trattano dati anagrafici degli studi (non dati clinici dei pazienti).
 * Sono nominati come soggetti autorizzati nel DPA del cliente con accesso limitato
 * a: nome studio, P.IVA, volume ordini aggregato, fatture. Nessun accesso a patient_ref o file CAD.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabella agenti
        Schema::create('oc_enamel_agents', function (Blueprint $table) {
            $table->id();

            // Codice univoco breve (es. "AG001", "MARIO") — usato in report e comunicazioni
            $table->string('code', 10)->unique();

            // Anagrafica
            $table->string('name');                         // Nome e cognome / Ragione sociale
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();

            // Dati fiscali (per pagamento commissioni e fatturazione)
            $table->string('fiscal_code', 16)->nullable();
            $table->string('vat_number', 13)->nullable();
            $table->string('pec_email')->nullable();
            $table->string('iban', 34)->nullable();         // per bonifico commissioni
            $table->string('bank_name')->nullable();

            // Contratto
            $table->decimal('commission_rate', 5, 2)->default(0.00); // % su ordini fatturati
            $table->string('commission_basis')->default('fatturato');  // 'fatturato' | 'incassato'
            $table->text('territory')->nullable();           // zone geografiche di competenza
            $table->date('contract_started_at')->nullable();
            $table->date('contract_ended_at')->nullable();

            // Stato
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');

            $table->text('notes')->nullable();              // note interne staff lab

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('code');
        });

        // Aggiunge agent_id a oc_enamel_studios
        // Nullable: uno studio può non avere un agente assegnato (acquisito direttamente)
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            $table->unsignedBigInteger('agent_id')->nullable()->after('user_id');
            $table->date('agent_assigned_at')->nullable()->after('agent_id'); // data assegnazione

            $table->foreign('agent_id')
                ->references('id')
                ->on('oc_enamel_agents')
                ->onDelete('set null'); // se l'agente viene eliminato, lo studio non viene toccato

            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropIndex(['agent_id']);
            $table->dropColumn(['agent_id', 'agent_assigned_at']);
        });

        Schema::dropIfExists('oc_enamel_agents');
    }
};
