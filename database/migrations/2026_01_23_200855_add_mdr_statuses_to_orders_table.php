<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Aggiorniamo l'elenco dei valori ammessi per la colonna status
        DB::statement("ALTER TABLE oc_enamel_orders MODIFY COLUMN status ENUM('pending', 'scanning', 'processing', 'finalizing', 'completed', 'failed') DEFAULT 'pending'");
    }

    public function down(): void
    {
        // In caso di rollback, torniamo agli stati base
        DB::statement("ALTER TABLE oc_enamel_orders MODIFY COLUMN status ENUM('pending', 'scanning', 'processing', 'failed') DEFAULT 'pending'");
    }
};