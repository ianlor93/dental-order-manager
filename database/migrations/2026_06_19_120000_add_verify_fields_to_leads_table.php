<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Funnel di registrazione — Blocco 1 (verifica email + imposta password).
 *
 * Campi additivi su `leads` per il meccanismo verifica-email → set-password:
 *  - verify_token       : HASH SHA-256 del token di verifica (mai il plaintext), indicizzato.
 *  - verify_expires_at  : scadenza del token (48h dal mint).
 *  - email_verified_at  : timestamp di avvenuta verifica email + set password.
 *
 * Tutto nullable e additivo: nessuna colonna o rotta esistente toccata. I 3 campi
 * NON entrano in Lead::$fillable — set espliciti dai controller (fail-closed contro
 * mass-assignment, coerente con i campi di sistema del funnel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('verify_token', 64)->nullable()->after('activated_at')
                  ->comment('Hash SHA-256 del token di verifica email (Blocco 1)');
            $table->timestamp('verify_expires_at')->nullable()->after('verify_token')
                  ->comment('Scadenza del token di verifica (48h)');
            $table->timestamp('email_verified_at')->nullable()->after('verify_expires_at')
                  ->comment('Verifica email + set password completata');

            // Indice per il match veloce sul token in fase di validate/complete.
            $table->index('verify_token');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['verify_token']); // leads_verify_token_index
            $table->dropColumn(['verify_token', 'verify_expires_at', 'email_verified_at']);
        });
    }
};
