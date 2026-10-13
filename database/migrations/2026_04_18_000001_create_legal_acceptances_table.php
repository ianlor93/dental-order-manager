<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro granulare delle accettazioni legali (GDPR Art. 7 + 5(2), Art. 1341 c.c.).
 *
 * Ogni record rappresenta UNA firma di UN documento in UNA versione da parte di UN utente.
 * Append-only: mai UPDATE (le rettifiche avvengono con nuovi record con versione successiva).
 *
 * I quattro timestamp su oc_enamel_studios restano come CACHE DENORMALIZZATA per retrocompatibilità
 * con EnsureLegalCompliance middleware. Sono aggiornati dal LegalController al completamento step 4.
 *
 * CAMPO user_uid:
 *   - VARCHAR(64) indicizzato
 *   - NON è FK verso oc_users (cross-schema Nextcloud vietato per vincolo di progetto)
 *
 * CAMPO document_type:
 *   - enum esplicito per prevenire drift verso stringhe arbitrarie
 *   - 'vessatorie' separato da 'tos' perché richiede accettazione specifica Art. 1341 c.c. B2B IT
 *
 * CAMPO content_hash:
 *   - SHA-256 del file legale (es. resources/views/legal/pro/text/tos-mdr.txt) al momento della firma
 *   - Permette di dimostrare che il testo sottoscritto == testo pubblicato
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_enamel_legal_acceptances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('studio_id')
                ->constrained('oc_enamel_studios')
                ->cascadeOnDelete()
                ->comment('Studio titolare dell\'accettazione — cascade su soft delete studio');

            $table->string('user_uid', 64)
                ->comment('UID Nextcloud dell\'utente che ha firmato (sempre owner — delegati non firmano)');

            $table->enum('document_type', [
                'tos',         // Termini e Condizioni
                'vessatorie',  // Clausole vessatorie Art. 1341 c.c. — sottoscrizione separata B2B
                'privacy',     // Informativa Privacy Art. 13 GDPR
                'dpa',         // Accordo Responsabile Trattamento Art. 28 GDPR
                'mdr',         // Protocollo MDR (deprecato — resta per storico, nuovi studi non lo firmano)
            ])->comment('Tipo documento sottoscritto');

            $table->string('version', 10)
                ->comment('Versione del documento al momento della firma (es. 2026.3)');

            $table->timestamp('accepted_at')
                ->useCurrent()
                ->comment('Timestamp preciso della firma');

            $table->string('ip_address', 45)
                ->nullable()
                ->comment('IP del firmatario — IPv4 o IPv6');

            $table->string('user_agent', 500)
                ->nullable()
                ->comment('User-Agent browser — troncato a 500 char');

            $table->char('content_hash', 64)
                ->comment('SHA-256 del testo del documento firmato (prova integrità)');

            $table->string('content_snapshot_path', 500)
                ->nullable()
                ->comment('Path relativo al file snapshot del testo firmato (opzionale — abilitato post MVP)');

            $table->timestamps();

            // Indici per lookup legal gate e export Art. 15
            $table->index(['studio_id', 'document_type', 'version'], 'idx_legal_acc_sdv');
            $table->index('user_uid', 'idx_legal_acc_user_uid');
            $table->index('accepted_at', 'idx_legal_acc_accepted_at');

            // NON usiamo unique su (studio_id, document_type, version) perché il testo può essere
            // ri-sottoscritto dopo una correzione (edge case — due record validi, si usa il più recente).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_legal_acceptances');
    }
};
