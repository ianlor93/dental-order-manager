<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge il flag is_implantable al catalogo MDR.
 *
 * La distinzione è necessaria per applicare la retention corretta (MDR Art. 10(8)):
 *   - Dispositivi NON impiantabili (protesi rimovibili, allineatori, bite, faccette): 10 anni
 *   - Dispositivi impiantabili (corone cementate, ponti fissi, protesi fissa, impianti): 15 anni
 *
 * Definizione di "impiantabile" ex Art. 2(5) MDR 2017/745:
 *   qualsiasi dispositivo destinato a essere introdotto totalmente o parzialmente nel corpo umano
 *   mediante intervento clinico e a rimanere in sede dopo l'intervento. È anche impiantabile
 *   qualsiasi dispositivo destinato a sostituire una superficie epiteliale o oculare.
 *   In ambito odontotecnico: qualsiasi restauro che il paziente non possa rimuovere autonomamente
 *   (corona cementata, ponte fisso, protesi su impianti) è da considerarsi impiantabile.
 *
 * Default: false (non impiantabile) — richiede aggiornamento esplicito tramite mdr:sync-catalog
 * per i dispositivi di classe pertinente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_mdr_catalog', function (Blueprint $table) {
            $table->boolean('is_implantable')
                ->default(false)
                ->after('duration_use')
                ->comment('true = dispositivo impiantabile ex Art. 2(5) MDR → retention 15 anni; false → 10 anni');
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_mdr_catalog', function (Blueprint $table) {
            $table->dropColumn('is_implantable');
        });
    }
};
