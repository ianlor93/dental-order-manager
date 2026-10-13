<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            // Aggiungiamo dentist_name (Titolare/Responsabile clinico per MDR)
            if (!Schema::hasColumn('oc_enamel_studios', 'dentist_name')) {
                $table->string('dentist_name')->nullable()->after('studio_name');
            }

            // Email per invio certificazioni MDR/GDPR
            if (!Schema::hasColumn('oc_enamel_studios', 'email')) {
                $table->string('email')->nullable()->after('dentist_name');
            }

            // Campo per note su privacy o consensi GDPR
            if (!Schema::hasColumn('oc_enamel_studios', 'privacy_consent')) {
                $table->boolean('privacy_consent')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            $table->dropColumn(['dentist_name', 'email', 'privacy_consent']);
        });
    }
};