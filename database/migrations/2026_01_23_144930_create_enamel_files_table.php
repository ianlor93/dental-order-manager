<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esegue la migrazione per la tabella dei file degli ordini.
     */
    public function up(): void
    {
        Schema::create('oc_enamel_order_files', function (Blueprint $table) {
            $table->id();
            
            // Relazione con l'ordine (MDR Traceability)
            $table->foreignId('order_id')
                  ->constrained('oc_enamel_orders')
                  ->onDelete('cascade');
            
            // Dettagli File
            $table->string('file_path', 1024);
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            
            // Sicurezza e Integrità (SHA-256 richiesto dal blueprint)
            $table->char('hash_sha256', 64);
            $table->enum('scan_status', ['pending', 'clean', 'infected', 'error'])
                  ->default('pending');
            
            // Timestamps e SoftDeletes per conservazione 10 anni
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverta la migrazione.
     */
    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_order_files');
    }
};