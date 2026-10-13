<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge campi per il diritto di restrizione del trattamento (GDPR Art. 18).
 *
 * L'Art. 18 consente all'interessato di ottenere la restrizione del trattamento
 * nei seguenti casi:
 *   a) contesta l'esattezza dei dati (per il tempo necessario alla verifica)
 *   b) il trattamento è illecito ma si oppone alla cancellazione
 *   c) i dati servono per l'accertamento, esercizio o difesa di un diritto in sede giudiziaria
 *   d) ha presentato opposizione ex Art. 21 in attesa di verifica
 *
 * Nota MDR: la restrizione NON interferisce con is_locked — un ordine può essere
 * contemporaneamente locked (MDR) e restricted (GDPR): il locked impedisce la cancellazione,
 * il restricted limita ulteriori trattamenti diversi dalla conservazione.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Campi su oc_enamel_orders
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->timestamp('restricted_at')
                ->nullable()
                ->after('deletion_reason')
                ->comment('GDPR Art. 18 — timestamp inizio restrizione trattamento');

            $table->string('restricted_reason', 255)
                ->nullable()
                ->after('restricted_at')
                ->comment('Motivo restrizione: accuracy_dispute | unlawful_processing | legal_claim | opposition_pending');

            $table->date('restriction_review_date')
                ->nullable()
                ->after('restricted_reason')
                ->comment('Data revisione restrizione — scadenza automatica o valutazione DPO');

            $table->string('restriction_case_id', 50)
                ->nullable()
                ->after('restriction_review_date')
                ->comment('Collegamento al record oc_enamel_gdpr_requests.id');

            $table->index('restricted_at', 'idx_orders_restricted_at');
        });

        // Campi simmetrici su oc_enamel_studios
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            $table->timestamp('restricted_at')
                ->nullable()
                ->after('deleted_at')
                ->comment('GDPR Art. 18 — timestamp inizio restrizione trattamento');

            $table->string('restricted_reason', 255)
                ->nullable()
                ->after('restricted_at')
                ->comment('Motivo restrizione');

            $table->date('restriction_review_date')
                ->nullable()
                ->after('restricted_reason')
                ->comment('Data revisione restrizione');

            $table->string('restriction_case_id', 50)
                ->nullable()
                ->after('restriction_review_date')
                ->comment('Collegamento al record oc_enamel_gdpr_requests.id');
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_restricted_at');
            $table->dropColumn(['restricted_at', 'restricted_reason', 'restriction_review_date', 'restriction_case_id']);
        });

        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            $table->dropColumn(['restricted_at', 'restricted_reason', 'restriction_review_date', 'restriction_case_id']);
        });
    }
};
