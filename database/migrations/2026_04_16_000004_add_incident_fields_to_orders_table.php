<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge campi strutturati per la gestione degli incidenti MDR (Art. 87).
 *
 * Art. 87 MDR stabilisce termini perentori dalla data di CONOSCENZA dell'incidente:
 *   - Rischio grave imminente per salute pubblica → 2 giorni
 *   - Incidente grave                             → 15 giorni
 *   - Incidente non grave / problema prestazioni  → 30 giorni
 *
 * Senza incident_reported_at non è possibile dimostrare il rispetto dei termini
 * a un ispettore del Ministero della Salute.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {

            $table->timestamp('incident_reported_at')
                ->nullable()
                ->after('pms_next_review_at')
                ->comment('MDR Art. 87 — timestamp esatto della segnalazione incidente');

            $table->enum('incident_severity', ['imminente', 'grave', 'non_grave'])
                ->nullable()
                ->after('incident_reported_at')
                ->comment('Gravità: imminente=2gg, grave=15gg, non_grave=30gg per notifica Ministero');

            $table->timestamp('incident_notification_deadline')
                ->nullable()
                ->after('incident_severity')
                ->comment('Scadenza notifica Ministero della Salute — calcolata automaticamente');

            $table->timestamp('incident_resolved_at')
                ->nullable()
                ->after('incident_notification_deadline')
                ->comment('Timestamp chiusura indagine incidente');

            $table->text('incident_resolution_notes')
                ->nullable()
                ->after('incident_resolved_at')
                ->comment('Note obbligatorie alla chiusura: causa, misure adottate, esito');

            $table->index('incident_reported_at', 'idx_orders_incident_reported_at');
            $table->index(['pms_status', 'incident_notification_deadline'], 'idx_orders_incident_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_incident_reported_at');
            $table->dropIndex('idx_orders_incident_deadline');
            $table->dropColumn([
                'incident_reported_at',
                'incident_severity',
                'incident_notification_deadline',
                'incident_resolved_at',
                'incident_resolution_notes',
            ]);
        });
    }
};
