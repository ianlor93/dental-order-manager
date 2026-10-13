<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('nome');
            $table->string('email');
            $table->string('studio');
            $table->string('telefono');
            $table->string('sconto')->default('20%');
            $table->string('campagna')->default('bfd-landing')->comment('Identifica la campagna di origine del voucher');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('campagna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
