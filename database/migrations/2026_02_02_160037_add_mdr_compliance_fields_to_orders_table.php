<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $blueprint) {
            // Campi per la validazione clinica
            if (!Schema::hasColumn('oc_enamel_orders', 'validation_hash')) {
                $blueprint->string('validation_hash')->nullable()->after('status');
            }
            if (!Schema::hasColumn('oc_enamel_orders', 'validated_at')) {
                $blueprint->timestamp('validated_at')->nullable()->after('validation_hash');
            }
            
            // Campi per il blocco MDR e multi-dispositivo
            if (!Schema::hasColumn('oc_enamel_orders', 'is_locked')) {
                $blueprint->boolean('is_locked')->default(false)->after('validated_at');
            }
            if (!Schema::hasColumn('oc_enamel_orders', 'manufactured_devices')) {
                $blueprint->json('manufactured_devices')->nullable()->after('device_type');
            }
            if (!Schema::hasColumn('oc_enamel_orders', 'risk_class')) {
                $blueprint->string('risk_class')->nullable()->after('manufactured_devices');
            }
            if (!Schema::hasColumn('oc_enamel_orders', 'mdr_rule_applied')) {
                $blueprint->string('mdr_rule_applied')->nullable()->after('risk_class');
            }

            // Campi per la firma digitale del fabbricante (Finalizzazione)
            if (!Schema::hasColumn('oc_enamel_orders', 'production_hash')) {
                $blueprint->string('production_hash')->nullable()->after('is_locked');
            }
            if (!Schema::hasColumn('oc_enamel_orders', 'conformity_hash')) {
                $blueprint->string('conformity_hash')->nullable()->after('production_hash');
            }
            if (!Schema::hasColumn('oc_enamel_orders', 'lot_number')) {
                $blueprint->string('lot_number')->nullable()->after('mdr_rule_applied');
            }
            if (!Schema::hasColumn('oc_enamel_orders', 'materials_used')) {
                $blueprint->text('materials_used')->nullable()->after('lot_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $blueprint) {
            $blueprint->dropColumn([
                'validation_hash', 'validated_at', 'is_locked', 
                'manufactured_devices', 'risk_class', 'mdr_rule_applied',
                'production_hash', 'conformity_hash', 'lot_number', 'materials_used'
            ]);
        });
    }
};