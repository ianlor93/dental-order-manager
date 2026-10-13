<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            // Aggiungiamo i campi per la gestione contabile
            if (!Schema::hasColumn('oc_enamel_orders', 'billed_at')) {
                $table->timestamp('billed_at')->nullable()->after('archived_at');
            }
            if (!Schema::hasColumn('oc_enamel_orders', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->after('billed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropColumn(['billed_at', 'invoice_number']);
        });
    }
};