<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabella log accessi all'archivio MDR (oc_enamel_archive_accesses).
 *
 * MDR Art. 10(8) richiede conservazione 10-15 anni della documentazione tecnica.
 * Una buona pratica di gestione qualità (SGQ ISO 13485) prevede di tracciare
 * anche gli ACCESSI ai documenti archiviati, non solo la loro esistenza.
 *
 * Questo log è append-only: nessun record viene mai modificato o cancellato.
 * Serve come audit trail per:
 *   - Verifiche ispettive (Ministero della Salute, Organismi Notificati)
 *   - Risposte a richieste GDPR Art. 15 (chi ha acceduto ai dati del paziente)
 *   - Forensics in caso di data breach
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_enamel_archive_accesses', function (Blueprint $table) {
            $table->id();

            $table->string('order_code', 10)
                ->comment('Codice ordine — non FK per preservare il log anche dopo soft delete');

            $table->string('accessed_by', 64)
                ->comment('UID Nextcloud dell\'utente che ha effettuato l\'accesso');

            $table->enum('access_type', ['view', 'download_zip', 'download_pdf', 'download_prescription', 'export_gdpr'])
                ->comment('Tipo di accesso effettuato');

            $table->string('file_name', 255)
                ->nullable()
                ->comment('Nome del file scaricato (per download specifici)');

            $table->timestamp('accessed_at')
                ->useCurrent()
                ->comment('Timestamp accesso — immutabile');

            $table->string('ip_address', 45)
                ->nullable()
                ->comment('IP dell\'utente al momento dell\'accesso');

            $table->string('user_agent', 500)
                ->nullable()
                ->comment('User agent del browser');

            // Nessun timestamps() — questo è un log append-only, no updated_at
            $table->index('order_code', 'idx_archive_order_code');
            $table->index('accessed_by', 'idx_archive_accessed_by');
            $table->index('accessed_at', 'idx_archive_accessed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_archive_accesses');
    }
};
