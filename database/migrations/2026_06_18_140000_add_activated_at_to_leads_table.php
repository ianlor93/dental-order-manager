<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Funnel T4b — `activated_at` su `leads`.
 *
 * Timestamp di avvenuta abilitazione su Nextcloud (enable + assegnazione gruppo +
 * invio email "imposta password"). Serve all'idempotenza del ramo `approvato` del
 * runner: un lead `approvato` è "da abilitare" finché `activated_at` è null.
 *
 * Additiva e nullable: nessun comportamento esistente cambia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable()->after('rejection_reason')
                  ->comment('Abilitazione NC completata (enable+gruppo+email)');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('activated_at');
        });
    }
};
