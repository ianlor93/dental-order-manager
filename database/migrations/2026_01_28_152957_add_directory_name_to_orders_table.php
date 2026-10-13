<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            // Aggiungiamo la colonna subito dopo upload_session_id
            $table->string('directory_name')->nullable()->after('upload_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropColumn('directory_name');
        });
    }
};