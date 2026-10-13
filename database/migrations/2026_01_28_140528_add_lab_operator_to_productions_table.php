<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oc_enamel_order_productions', function (Blueprint $table) {
            // Aggiungiamo la colonna mancante per l'operatore di laboratorio
            $table->string('lab_operator')->nullable()->after('lab_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oc_enamel_order_productions', function (Blueprint $table) {
            $table->dropColumn('lab_operator');
        });
    }
};