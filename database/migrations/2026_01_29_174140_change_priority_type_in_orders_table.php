<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cambiamo PRIMA il tipo di colonna in stringa
        // Così il database smetterà di aspettarsi solo numeri
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->string('priority')->default('normal')->change();
        });

        // 2. ORA aggiorniamo i dati esistenti
        // I vecchi numeri (come il 5 che vedevamo in Tinker) ora sono trattati come testo
        DB::table('oc_enamel_orders')
            ->where('priority', '5')
            ->orWhere('priority', '>', 0) 
            ->update(['priority' => 'high']);

        DB::table('oc_enamel_orders')
            ->where('priority', '0')
            ->orWhereNull('priority')
            ->update(['priority' => 'normal']);
    }

    public function down(): void
    {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            // In caso di rollback, torna a integer (attenzione: i dati 'high' potrebbero diventare 0)
            $table->integer('priority')->default(0)->change();
        });
    }
};