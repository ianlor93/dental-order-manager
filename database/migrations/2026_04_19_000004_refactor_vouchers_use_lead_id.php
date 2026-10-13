<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refactor vouchers: i dati anagrafici escono dalla tabella vouchers
 * e vivono in leads. Il voucher referenzia solo lead_id + campagna.
 *
 * Unico per (lead_id, campagna): un lead può avere un voucher per campagna.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            // Rimuovi il vecchio unique (email, campagna)
            $table->dropUnique('vouchers_email_campagna_unique');

            // Rimuovi i campi anagrafici ora in leads
            $table->dropColumn(['nome', 'email', 'studio', 'telefono']);

            // Aggiungi FK verso leads
            $table->foreignId('lead_id')
                  ->after('id')
                  ->constrained('leads')
                  ->cascadeOnDelete();

            // Nuovo unique: un voucher per lead per campagna
            $table->unique(['lead_id', 'campagna'], 'vouchers_lead_campagna_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropUnique('vouchers_lead_campagna_unique');
            $table->dropForeign(['lead_id']);
            $table->dropColumn('lead_id');

            $table->string('nome')->default('');
            $table->string('email')->default('');
            $table->string('studio')->default('');
            $table->string('telefono')->default('');
            $table->unique(['email', 'campagna'], 'vouchers_email_campagna_unique');
        });
    }
};
