<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Welcome credits una-tantum (2026-06-25).
 *
 * `welcome_granted_at` = timestamp del grant dei 25 crediti di benvenuto.
 * È l'ancora di idempotenza (anti signup-farming v2): un solo grant per lead/email
 * verificata, indipendente dallo studio (sopravvive a cancellazione+ricreazione studio).
 * Letto/scritto in App\Http\Controllers\LegalController::processStep4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('welcome_granted_at')->nullable()->after('email_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('welcome_granted_at');
        });
    }
};
