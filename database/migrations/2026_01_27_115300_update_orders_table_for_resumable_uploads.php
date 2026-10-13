<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            // Aggiungiamo la sorgente per future automazioni
            $table->string('source')->default('web')->after('status');
            
            // Sessione per il recupero upload in caso di refresh/crash
            $table->string('upload_session_id')->nullable()->after('source');

            // Numero di file che il sistema si aspetta (per validazione MDR)
            $table->integer('expected_files_count')->default(0)->after('upload_session_id');

            // Nota: Se la tua colonna status è un ENUM, dobbiamo assicurarci 
            // che accetti 'draft' e 'uploading'. Se è una string, siamo già a posto.
            // Per sicurezza, forziamo il cambio se necessario:
            $table->string('status')->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropColumn(['source', 'upload_session_id', 'expected_files_count']);
        });
    }
};