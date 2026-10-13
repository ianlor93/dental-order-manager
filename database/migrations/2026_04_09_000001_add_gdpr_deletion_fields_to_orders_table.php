<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge campi per il tracciamento delle richieste di cancellazione GDPR (Art. 17).
 *
 * NOTA: deleted_at esiste già tramite SoftDeletes — NON viene toccato.
 * Questa migration aggiunge solo:
 *   - deletion_requested_at: quando l'utente ha richiesto la cancellazione
 *   - deletion_reason:       motivo (user_request, retention_expired, ecc.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->timestamp('deletion_requested_at')
                ->nullable()
                ->after('legal_retention_until')
                ->comment('Timestamp richiesta cancellazione GDPR Art. 17');

            $table->string('deletion_reason', 50)
                ->nullable()
                ->after('deletion_requested_at')
                ->comment('Motivo: user_request | gdpr_right_to_deletion | retention_expired');

            $table->index('deletion_requested_at', 'idx_orders_deletion_requested');
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_deletion_requested');
            $table->dropColumn(['deletion_requested_at', 'deletion_reason']);
        });
    }
};
