<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabella di registro per tutte le richieste di esercizio dei diritti GDPR (Art. 15-22).
 *
 * Serve come audit trail obbligatorio: il Titolare deve poter dimostrare di aver
 * risposto alle richieste entro i termini dell'Art. 12 (1 mese, prorogabile a 3).
 *
 * Copre:
 *   Art. 15 — Accesso
 *   Art. 16 — Rettifica
 *   Art. 17 — Cancellazione (già gestita parzialmente con deletion_requested_at)
 *   Art. 18 — Restrizione
 *   Art. 19 — Notifica rettifica/cancellazione/restrizione
 *   Art. 20 — Portabilità (già gestita con /gdpr/export)
 *   Art. 21 — Opposizione
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_enamel_gdpr_requests', function (Blueprint $table) {
            $table->id();

            $table->string('user_uid', 64)
                ->comment('UID Nextcloud del richiedente');

            $table->enum('right_article', [
                'art15', // Accesso
                'art16', // Rettifica
                'art17', // Cancellazione
                'art18', // Restrizione
                'art19', // Notifica
                'art20', // Portabilità
                'art21', // Opposizione
            ])->comment('Articolo GDPR invocato');

            $table->string('object_category', 100)
                ->nullable()
                ->comment('Categoria dati oggetto della richiesta: ordini | studio | tutto | altro');

            $table->text('motivation')
                ->nullable()
                ->comment('Motivazione fornita dall\'interessato');

            $table->timestamp('received_at')
                ->useCurrent()
                ->comment('Data ricezione — termine Art. 12 decorre da qui');

            $table->timestamp('deadline_at')
                ->nullable()
                ->comment('Scadenza risposta (received_at + 30 gg, prorogabile a 90)');

            $table->string('classified_as', 50)
                ->nullable()
                ->comment('Classificazione interna: fondato | infondato | eccessivo | MDR_bloccato');

            $table->enum('decision', ['pending', 'accepted', 'rejected', 'partial'])
                ->default('pending')
                ->comment('Esito della valutazione');

            $table->timestamp('decided_at')
                ->nullable()
                ->comment('Data decisione');

            $table->string('response_file_ref', 255)
                ->nullable()
                ->comment('Path al file di risposta inviato all\'interessato');

            $table->string('case_pdf_ref', 255)
                ->nullable()
                ->comment('Path al fascicolo PDF del caso');

            $table->string('handled_by', 64)
                ->nullable()
                ->comment('UID staff che ha gestito la richiesta');

            $table->text('internal_notes')
                ->nullable()
                ->comment('Note interne DPO/staff — non visibili all\'interessato');

            $table->string('ip_address', 45)
                ->nullable()
                ->comment('IP del richiedente al momento della richiesta');

            $table->timestamps();

            $table->index('user_uid', 'idx_gdpr_user_uid');
            $table->index('decision', 'idx_gdpr_decision');
            $table->index('received_at', 'idx_gdpr_received_at');
            $table->index(['decision', 'deadline_at'], 'idx_gdpr_pending_deadline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_gdpr_requests');
    }
};
