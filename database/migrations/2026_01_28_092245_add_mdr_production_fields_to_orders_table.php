<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            // --- DATI PRESCRIZIONE (Art. 2(3) e Allegato XIII) ---
            // Nome e qualifica professionale del prescrittore [cite: 19, 85]
            $table->string('prescriber_qualification')->nullable(); 
            // Nome dell'istituzione sanitaria (se del caso) 
            $table->string('sanitary_institution')->nullable();    
            // Caratteristiche specifiche di progettazione [cite: 23, 87]
            $table->text('design_features')->nullable();           
            
            // --- DATI FABBRICAZIONE E TRACCIABILITÀ (Allegato XIII) ---
            // Dati per identificare univocamente il dispositivo (lotto/serie) [cite: 32, 33]
            $table->string('lot_number')->nullable();              
            // Materiali utilizzati (se rilevanti per la progettazione) [cite: 23]
            $table->text('materials_used')->nullable();            
            $table->string('technician_signature')->nullable();    
            
            // --- WORKFLOW DI VALIDAZIONE E LOCK ---
            // Data della prescrizione/validazione [cite: 81, 90]
            $table->timestamp('validated_at')->nullable();         
            $table->string('validation_hash')->nullable();          
            $table->boolean('is_locked')->default(false);          
        });
    }

    public function down(): void {
        Schema::table('oc_enamel_orders', function (Blueprint $table) {
            $table->dropColumn([
                'prescriber_qualification', 'sanitary_institution', 'design_features',
                'lot_number', 'materials_used', 'technician_signature', 
                'validated_at', 'validation_hash', 'is_locked'
            ]);
        });
    }
};