<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_enamel_ibex_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('studio_id'); // ID dello studio (oc_enamel_studios)
            $table->string('nc_uid', 64);            // UID di Nextcloud
            $table->string('project_name');
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->integer('credits_cost')->default(0);
            $table->string('input_file_path', 512)->nullable();
            $table->string('output_file_path', 512)->nullable();
            $table->text('error_log')->nullable();
            $table->timestamps(); // Crea created_at e updated_at automaticamente

            // Indici per velocità di ricerca
            $table->index('studio_id');
            $table->index('nc_uid');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_ibex_projects');
    }
};