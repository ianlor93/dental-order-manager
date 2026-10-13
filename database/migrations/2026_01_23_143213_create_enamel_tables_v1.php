<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_enamel_studios', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 64)->unique();
            $table->string('name');
            $table->string('fiscal_code', 16)->nullable();
            $table->string('vat_number', 13)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('dentist_name');
            $table->string('dentist_license');
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oc_enamel_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 7)->unique();
            $table->string('user_id', 64);
            $table->foreignId('studio_id')->constrained('oc_enamel_studios');
            $table->string('patient_ref');
            $table->string('device_type');
            $table->string('teeth_notation');
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'scanning', 'processing', 'ready', 'completed', 'shipped', 'failed', 'cancelled'])->default('pending');
            $table->unsignedTinyInteger('priority')->default(5);
            $table->string('material_lot')->nullable();
            $table->timestamp('delivery_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('oc_enamel_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->char('hash_previous', 64)->nullable();
            $table->char('hash_current', 64);
            $table->timestamp('timestamp', 6)->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_audit_log');
        Schema::dropIfExists('oc_enamel_orders');
        Schema::dropIfExists('oc_enamel_studios');
    }
};