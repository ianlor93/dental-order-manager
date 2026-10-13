<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            // Cambiamo da timestamp a datetime per superare il limite dell'anno 2038
            $table->dateTime('archived_at')->nullable()->change();
            $table->dateTime('legal_retention_until')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->change();
            $table->timestamp('legal_retention_until')->nullable()->change();
        });
    }
};