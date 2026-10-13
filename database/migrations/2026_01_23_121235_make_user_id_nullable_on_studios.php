<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Esegui la migrazione per rendere user_id opzionale.
     * Questo risolve l'errore SQL 1364 durante i test iniziali.
     */
    public function up(): void
    {
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            // Modifichiamo la colonna esistente rendendola nullabile
            $table->string('user_id')->nullable()->change();
        });
    }

    /**
     * Torna indietro rendendo il campo di nuovo obbligatorio.
     */
    public function down(): void
    {
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            // Nota: prima di tornare indietro, assicurati che non ci siano user_id nulli nel DB
            $table->string('user_id')->nullable(false)->change();
        });
    }
};