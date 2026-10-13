<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $colonna) {
            // Aggiungiamo il flag booleano, di default a false (0)
            $colonna->boolean('is_trial')->default(false)->after('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $colonna) {
            $colonna->dropColumn('is_trial');
        });
    }
};