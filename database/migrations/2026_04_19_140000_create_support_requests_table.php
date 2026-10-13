<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_enamel_support_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->string('user_uid');
            $table->string('subject', 200);
            $table->text('body');
            $table->enum('priority', ['normale', 'urgente'])->default('normale');
            $table->enum('status', ['aperta', 'in_lavorazione', 'chiusa'])->default('aperta');
            $table->text('staff_notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign('studio_id')
                ->references('id')->on('oc_enamel_studios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_support_requests');
    }
};
