<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_addresses', function (Blueprint $table) {
            // Aggiungiamo i campi clinici necessari per il multi-prescrittore
            $table->string('dentist_name')->nullable()->after('label');
            $table->string('dentist_license')->nullable()->after('dentist_name');
            $table->string('albo_provincia', 2)->nullable()->after('dentist_license');
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_addresses', function (Blueprint $table) {
            $table->dropColumn(['dentist_name', 'dentist_license', 'albo_provincia']);
        });
    }
};