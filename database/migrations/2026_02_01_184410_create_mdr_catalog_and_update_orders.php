<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabella Catalogo (Specchio del CSV)
        Schema::create('oc_enamel_mdr_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // Categoria del Clinico
            $table->string('slug')->unique(); // Identificativo Staff
            $table->string('name'); // Nome commerciale
            $table->string('risk_class'); // IIa, IIb, etc.
            $table->string('mdr_rule'); // Regola 8, etc.
            $table->integer('pms_years'); // 1 o 2
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Aggiornamento Ordini per lo Snapshot Legale
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->string('final_device_slug')->nullable()->after('device_type');
            $table->string('final_device_name')->nullable()->after('final_device_slug');
            $table->string('mdr_rule_applied')->nullable()->after('risk_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_mdr_catalog');
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropColumn(['final_device_slug', 'final_device_name', 'mdr_rule_applied']);
        });
    }
};