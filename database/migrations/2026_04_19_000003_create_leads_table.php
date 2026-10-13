<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leads — contatti pre-registrazione (form landing page, campagne, ecc.)
 *
 * Un lead rappresenta una persona che ha manifestato interesse ma non ha ancora
 * un account Nextcloud. Quando si registra, studio_id viene popolato e
 * converted_at impostato — il lead non viene mai cancellato (append-only).
 *
 * Non duplicare campi presenti in oc_enamel_studios: studio_id è il link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('studio');
            $table->string('telefono');
            $table->string('source')->default('bfd-landing')->comment('Canale di acquisizione');

            // Collegamento allo Studio reale quando il lead si converte
            $table->foreignId('studio_id')
                  ->nullable()
                  ->constrained('oc_enamel_studios')
                  ->nullOnDelete();

            $table->timestamp('converted_at')->nullable()->comment('Impostato quando il lead crea un account');
            $table->timestamps();

            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
