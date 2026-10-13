<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_comments', function (Blueprint $table) {
            $table->id();
            // Usiamo order_id come chiave esterna (presumendo che Order abbia un id numerico)
            $table->unsignedBigInteger('order_id'); 
            $table->string('user_uid'); // UID dell'utente Nextcloud
            $table->text('body');       // Il contenuto del commento
            
            /**
             * 🚩 IL CUORE DELLA LOGICA:
             * true  = Solo Staff
             * false = Staff + Cliente
             */
            $table->boolean('is_internal')->default(false);
            
            $table->timestamps();

            // Indice per velocizzare il caricamento della cronologia
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_comments');
    }
};