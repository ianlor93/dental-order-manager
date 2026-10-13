<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dismissione della tabella oc_enamel_audit_log (dormiente).
 *
 * Definita nella migration originale 2026_01_23_143213 ma mai referenziata
 * nel codebase in 3+ mesi di produzione. L'audit trail generale è coperto da:
 *   - OrderComment (workflow ordini con prefix 🤖 SISTEMA)
 *   - ArchiveAccess (accessi archivio MDR Art. 10(8))
 *   - GdprRequest (richieste GDPR Art. 15-22)
 *   - LegalAcceptance (firme legali con hash SHA-256 — 2026_04_18_000001)
 *   - Log Laravel (prefix MDR-*, GDPR, BILLING-SECURITY, SCHEDULER)
 *
 * In assenza di dati la dismissione è a rischio zero. Rollback disponibile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('oc_enamel_audit_log');
    }

    public function down(): void
    {
        Schema::create('oc_enamel_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->char('hash_previous', 64)->nullable();
            $table->char('hash_current', 64);
            $table->timestamp('timestamp', 6)->useCurrent();
        });
    }
};
