<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            if (!Schema::hasColumn('oc_enamel_studios', 'tier_level')) {
                $table->string('tier_level', 10)->default('L0')->after('legal_version');
            }
            if (!Schema::hasColumn('oc_enamel_studios', 'subscription_plan')) {
                $table->string('subscription_plan', 20)->default('monthly')->after('tier_level');
            }
            if (!Schema::hasColumn('oc_enamel_studios', 'credits_gift')) {
                $table->integer('credits_gift')->default(0)->after('subscription_plan');
            }
            if (!Schema::hasColumn('oc_enamel_studios', 'credits_purchased')) {
                $table->integer('credits_purchased')->default(0)->after('credits_gift');
            }
            if (!Schema::hasColumn('oc_enamel_studios', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('credits_purchased');
            }
            if (!Schema::hasColumn('oc_enamel_studios', 'grace_period_until')) {
                $table->timestamp('grace_period_until')->nullable()->after('subscription_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_studios', function (Blueprint $table) {
            $table->dropColumn([
                'tier_level', 
                'subscription_plan', 
                'credits_gift', 
                'credits_purchased', 
                'subscription_expires_at',
                'grace_period_until'
            ]);
        });
    }
};