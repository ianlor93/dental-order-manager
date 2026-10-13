<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_enamel_order_productions', function (Blueprint $table) {
            $table->id();
            // Relazione con l'ordine principale (la tua tabella oc_enamel_orders)
            $table->foreignId('order_id')->constrained('oc_enamel_orders')->onDelete('cascade');
            
            // Dati CAD (Progettazione) [cite: 3027, 3031]
            $table->string('cad_operator')->nullable();
            $table->string('cad_teeth_library')->nullable();
            $table->string('cad_implant_library')->nullable();
            $table->text('cad_notes')->nullable();

            // Dati CAM (Produzione e Lotti) [cite: 3038, 3045]
            $table->json('cam_data')->nullable(); // Tabella dinamica materiali/macchine
            
            // Lavorazioni LAB e Colore [cite: 2961, 2980]
            $table->string('lab_color')->nullable();
            $table->json('lab_materials')->nullable();
            $table->text('lab_notes')->nullable();

            // Controllo Qualità e Rilascio [cite: 3797, 3803]
            $table->string('qc_verifier')->nullable();
            $table->json('qc_checklist')->nullable();
            $table->enum('qc_status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('released_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_order_productions');
    }
};