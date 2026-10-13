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
        // 1. Crea la tabella delle Distinte di Consegna (Logistica)
        Schema::create('oc_enamel_delivery_notes', function (Blueprint $table) {
            $table->id();
            
            // Collegamento allo studio
            $table->foreignId('studio_id')
                  ->constrained('oc_enamel_studios')
                  ->onDelete('cascade');

            // Dati Logistici
            $table->string('courier')->nullable();      // Es: GLS, DHL, Consegna a Mano
            $table->string('tracking_code')->nullable(); // Es: 123456789
            $table->dateTime('delivery_date');          // Quando è uscito fisicamente il pacco

            // Dati Amministrativi (Fase 2)
            $table->dateTime('billed_at')->nullable();  // Se valorizzato, è stato incluso in un Riepilogo Mensile
            $table->string('monthly_summary_ref')->nullable(); // Riferimento al PDF riepilogativo (opzionale)

            $table->timestamps();
        });

        // 2. Modifica la tabella Ordini per collegarli alle distinte
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->foreignId('delivery_note_id')
                  ->nullable()
                  ->after('status') // Posizione puramente estetica nel DB
                  ->constrained('oc_enamel_delivery_notes')
                  ->nullOnDelete(); // Se cancelli la distinta, l'ordine torna "pendente" (non viene cancellato)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi prima la colonna dalla tabella ordini (per vincoli FK)
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_note_id']);
            $table->dropColumn('delivery_note_id');
        });

        // Rimuovi la tabella distinte
        Schema::dropIfExists('oc_enamel_delivery_notes');
    }
};