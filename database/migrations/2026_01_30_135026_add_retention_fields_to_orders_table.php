<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esegue la migrazione: aggiunge i campi per la conformità MDR/GDPR.
     */
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            // Timestamp di quando il lavoro entra in ARCH_L
            $table->timestamp('archived_at')->nullable()->after('validated_at');
            
            // Data esatta di scadenza del lock legale (15 anni dopo)
            $table->timestamp('legal_retention_until')->nullable()->after('archived_at');
            
            // Nota: is_locked è già presente nel tuo modello, 
            // ma se non fosse presente nel DB, scommenta la riga sotto:
            // $table->boolean('is_locked')->default(false)->after('legal_retention_until');
        });
    }

    /**
     * Inverte la migrazione: rimuove i campi in caso di rollback.
     */
    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropColumn(['archived_at', 'legal_retention_until']);
            // $table->dropColumn('is_locked'); // Scommenta se l'hai aggiunto sopra
        });
    }
};