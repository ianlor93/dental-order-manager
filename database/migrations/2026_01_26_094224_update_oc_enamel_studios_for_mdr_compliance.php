<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            // --- Sezione Amministrativa & Fiscale ---
            if (!Schema::hasColumn('oc_enamel_studios', 'sdi_code')) {
                $table->string('sdi_code', 7)->nullable()->after('vat_number');
            }
            if (!Schema::hasColumn('oc_enamel_studios', 'pec_email')) {
                $table->string('pec_email')->nullable()->after('sdi_code');
            }

            // --- Sezione Logistica & Contatti ---
            if (!Schema::hasColumn('oc_enamel_studios', 'phone')) {
                $table->string('phone')->nullable()->after('pec_email');
            }
            if (!Schema::hasColumn('oc_enamel_studios', 'mobile')) {
                $table->string('mobile')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('oc_enamel_studios', 'zip_code')) {
                $table->string('zip_code', 10)->nullable()->after('city');
            }

            // --- Sezione Professionale MDR ---
            if (!Schema::hasColumn('oc_enamel_studios', 'albo_provincia')) {
                $table->string('albo_provincia', 2)->nullable()->after('dentist_license');
            }

            // --- Sezione Legal Gate (I Timestamp di Accettazione) ---
            $table->timestamp('tos_accepted_at')->nullable()->after('updated_at');
            $table->timestamp('mdr_accepted_at')->nullable()->after('tos_accepted_at');
            $table->timestamp('dpa_accepted_at')->nullable()->after('mdr_accepted_at');
            
            // Versione dei testi legali per gestire i futuri update
            $table->string('legal_version')->default('2026.1')->after('dpa_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            $table->dropColumn([
                'sdi_code', 'pec_email', 'phone', 'mobile', 'zip_code', 
                'albo_provincia', 'tos_accepted_at', 'mdr_accepted_at', 
                'dpa_accepted_at', 'legal_version'
            ]);
        });
    }
};