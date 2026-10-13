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
        Schema::create('oc_enamel_orders', function (Blueprint $table) {
            $table->id();
            // Collegamento allo studio
            $table->foreignId('studio_id')->constrained('oc_enamel_studios')->onDelete('cascade');
            
            // MDR & GDPR Compliance
            $table->string('patient_code');               // Codice paziente (GDPR)
            $table->string('device_type');                // Tipo di lavoro (MDR)
            $table->string('material_lot')->nullable();   // Lotto del materiale (MDR)
            
            // Gestione Lavoro
            $table->date('delivery_date')->nullable();
            $table->enum('status', ['new', 'working', 'done', 'shipped'])->default('new');
            $table->boolean('is_certified')->default(false); // Conformità generata?
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_orders');
    }
};