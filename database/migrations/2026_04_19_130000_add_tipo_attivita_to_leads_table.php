<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->enum('tipo_attivita', [
                'studio_odontoiatrico',
                'laboratorio_odontotecnico',
                'fornitore_servizi',
            ])->nullable()->after('piva');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('tipo_attivita');
        });
    }
};
