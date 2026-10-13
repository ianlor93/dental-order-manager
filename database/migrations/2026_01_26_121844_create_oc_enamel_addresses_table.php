<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_enamel_addresses', function (Blueprint $table) {
            $table->id();
            // Il vincolo che garantisce la "certezza matematica" dell'appartenenza
            $table->foreignId('studio_id')->constrained('oc_enamel_studios')->onDelete('cascade');
            
            $table->string('label')->comment('Es: Sede Principale, Studio Mare, Clinica X');
            $table->string('address');
            $table->string('city');
            $table->string('zip_code', 10);
            $table->string('phone')->nullable()->comment('Telefono specifico della sede');
            
            $table->boolean('is_main')->default(false)->comment('Se questa è la sede predefinita');
            
            $table->timestamps();
            $table->softDeletes(); // Per non perdere lo storico degli ordini passati
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_addresses');
    }
};