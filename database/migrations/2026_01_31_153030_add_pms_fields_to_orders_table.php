<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            // Campi richiesti per la Sorveglianza Post-Commercializzazione (MDR Art. 83-86)
            $table->string('risk_class')->nullable()->after('device_type');
            $table->string('pms_status')->default('active')->after('risk_class');
            $table->timestamp('pms_last_review_at')->nullable()->after('pms_status');
            $table->timestamp('pms_next_review_at')->nullable()->after('pms_last_review_at');
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropColumn(['risk_class', 'pms_status', 'pms_last_review_at', 'pms_next_review_at']);
        });
    }
};