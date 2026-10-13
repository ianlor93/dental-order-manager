<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Funnel di registrazione — campi additivi su `leads`.
 *
 * Estende `leads` con lo stato di vaglio dello staff e i metadati di provisioning
 * dell'utente Nextcloud creato (disabilitato) dal funnel. Tutto additivo e nullable
 * (tranne `status`, che ha default 'nuovo'): l'INSERT esistente di LeadController::store()
 * resta valido senza modifiche e le righe già presenti ereditano status='nuovo'.
 *
 * Estende inoltre l'enum `source` con il nuovo canale 'funnel-registrazione'.
 * La landing BFD (source='bfd-landing') non è toccata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Stato del lead nel funnel di vaglio. NOT NULL con default 'nuovo':
            // le 2 righe esistenti (landing BFD) lo ereditano senza rotture.
            $table->enum('status', ['nuovo', 'da_vagliare', 'approvato', 'rifiutato'])
                  ->default('nuovo')
                  ->after('converted_at')
                  ->comment('Stato nel funnel di vaglio');

            // uid Nextcloud dell'utente creato (disabilitato) dal funnel di registrazione.
            $table->string('created_user_uid')->nullable()->after('status')
                  ->comment('uid NC utente creato (disabilitato) dal funnel');

            // Gruppo NC deciso al momento del vaglio — autoritativo.
            $table->string('assigned_group')->nullable()->after('created_user_uid')
                  ->comment('Gruppo NC deciso al vaglio (autoritativo)');

            // Tracciamento del vaglio dello staff.
            $table->string('vetted_by_uid')->nullable()->after('assigned_group')
                  ->comment('uid staff che ha vagliato');
            $table->timestamp('vetted_at')->nullable()->after('vetted_by_uid')
                  ->comment('Timestamp del vaglio');
            $table->text('rejection_reason')->nullable()->after('vetted_at')
                  ->comment('Motivo del rifiuto');

            // Indice per il filtro per stato nel pannello admin.
            $table->index('status');
        });

        // Lo Schema builder non MODIFY-a gli enum in modo pulito: usare DB::statement
        // (stessa tecnica di 2026_04_19_000006_convert_leads_source_to_enum).
        // L'indice su `source` (create migration) sopravvive al MODIFY — non toccarlo.
        DB::statement("ALTER TABLE `leads` MODIFY `source` ENUM('bfd-landing','funnel-registrazione') NOT NULL DEFAULT 'bfd-landing'");
    }

    public function down(): void
    {
        // Ripristina l'enum `source` allo stato pre-funnel.
        // ⚠️ Assume che nessuna riga abbia source='funnel-registrazione'
        // (vero in tranche 1, pre-funnel). Se in futuro esisteranno righe funnel,
        // il rollback dell'enum andrà gestito a parte.
        DB::statement("ALTER TABLE `leads` MODIFY `source` ENUM('bfd-landing') NOT NULL DEFAULT 'bfd-landing'");

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['status']); // leads_status_index
            $table->dropColumn([
                'status',
                'created_user_uid',
                'assigned_group',
                'vetted_by_uid',
                'vetted_at',
                'rejection_reason',
            ]);
        });
    }
};
