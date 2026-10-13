<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop tabella vouchers — il sistema coupon è stato rimosso.
 * I lead restano in leads (la nuova landing è una pre-registrazione, non genera più voucher).
 *
 * Down: ricostruisce la tabella allo stato finale (post-refactor lead_id) senza i dati.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('vouchers');
    }

    public function down(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('code', 16)->unique();
            $table->string('sconto')->default('20%');
            $table->string('campagna')->default('bfd-landing');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('campagna');
            $table->unique(['lead_id', 'campagna'], 'vouchers_lead_campagna_unique');
        });
    }
};
