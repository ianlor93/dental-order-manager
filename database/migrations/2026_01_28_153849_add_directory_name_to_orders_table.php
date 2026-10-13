<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esegue la migrazione.
     * Aggiunge la colonna directory_name indispensabile per il tracciamento NAS.
     */
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('oc_enamel_orders', 'directory_name')) {
                $table->string('directory_name')
                      ->nullable()
                      ->after('upload_session_id')
                      ->comment('Nome della cartella fisica su Unraid ING_L/BAC_L');
            }
        });
    }

    /**
     * Annulla la migrazione.
     */
    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            if (Schema::hasColumn('oc_enamel_orders', 'directory_name')) {
                $table->dropColumn('directory_name');
            }
        });
    }
};