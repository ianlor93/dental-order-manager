<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esegui la migrazione: aggiunta colonna ITCA.
     * * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('oc_enamel_studios')) {
            Schema::table('oc_enamel_studios', function (Blueprint $table) {
                // Aggiungiamo il campo ITCA dopo la Partita IVA.
                // Nullable garantisce che i record dei medici esistenti rimangano intatti.
                if (!Schema::hasColumn('oc_enamel_studios', 'itca_code')) {
                    $table->string('itca_code')->nullable()->after('vat_number');
                }
            });
        }
    }

    /**
     * Annulla la migrazione: rimozione colonna ITCA.
     * * @return void
     */
    public function down(): void
    {
        if (Schema::hasTable('oc_enamel_studios')) {
            Schema::table('oc_enamel_studios', function (Blueprint $table) {
                if (Schema::hasColumn('oc_enamel_studios', 'itca_code')) {
                    $table->dropColumn('itca_code');
                }
            });
        }
    }
};