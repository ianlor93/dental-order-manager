<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_mdr_catalog', function (Blueprint $table) {
            if (!Schema::hasColumn('oc_enamel_mdr_catalog', 'duration_use')) {
                // Inseriamo la colonna dopo il nome per ordine logico
                $table->string('duration_use')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_mdr_catalog', function (Blueprint $table) {
            $table->dropColumn('duration_use');
        });
    }
};