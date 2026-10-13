<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MULTI-PRESCRITTORE — Pivot oc_enamel_studio_users
 *
 * Permette a più utenti Nextcloud di operare sotto lo stesso studio (soggetto fiscale).
 * Ogni riga rappresenta l'appartenenza di un utente a uno studio con un ruolo specifico.
 *
 * Ruoli:
 *   owner      → titolare del contratto. Corrisponde a studios.user_id. Firma i documenti legali.
 *                È sempre presente (inserito automaticamente alla creazione dello studio).
 *   admin      → gestione amministrativa (ordini, fatturazione, sedi). Non necessariamente prescrittore.
 *   prescriber → dentista/odontoiatra che crea ordini. Ha dati albo propri.
 *   viewer     → sola lettura. Non può creare ordini.
 *
 * Note MDR: ogni ordine traccia user_id del prescrittore che ha caricato la prescrizione.
 * La firma della prescrizione (validation_hash) è associata all'uid del prescrittore,
 * non al titolare dello studio. Questo è conforme all'Allegato XIII MDR.
 *
 * Note GDPR: il DPA è firmato dal titolare (owner) per conto dello studio.
 * I prescrittori aggiunti operano sotto il DPA del titolare. Non richiedono onboarding
 * legale separato — il gate verifica compliance sullo studio, non sull'utente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_enamel_studio_users', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('studio_id');
            $table->string('user_uid', 64); // FK logica a oc_users.uid (no FK formale — tabella Nextcloud)

            // Ruolo nell'organizzazione dello studio
            $table->enum('role', ['owner', 'admin', 'prescriber', 'viewer'])->default('prescriber');

            // Dati clinici specifici di questo prescrittore su questo studio.
            // Se null, si usano i campi master di oc_enamel_studios come fallback.
            $table->string('dentist_name')->nullable();
            $table->string('dentist_license')->nullable();  // n. iscrizione albo
            $table->string('albo_provincia', 2)->nullable();
            $table->string('qualification')->nullable();    // es. "Medico Chirurgo - Odontoiatra"

            // Permessi granulari (sovrascrivono i default del ruolo se presenti)
            // Struttura JSON: { "create_orders": true, "validate_prescriptions": true,
            //                   "view_billing": false, "manage_members": false }
            $table->json('permissions')->nullable();

            // Stato
            $table->boolean('can_prescribe')->default(true);   // può creare ordini?
            $table->boolean('is_active')->default(true);       // account abilitato?

            // Ciclo di vita invito
            $table->string('invited_by_uid', 64)->nullable();  // chi ha invitato
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();        // quando ha accettato
            $table->timestamp('deactivated_at')->nullable();

            $table->text('notes')->nullable();                 // note interne (staff lab)

            $table->timestamps();

            // FK verso lo studio
            $table->foreign('studio_id')
                ->references('id')
                ->on('oc_enamel_studios')
                ->onDelete('cascade');

            // Un utente ha UN solo ruolo per studio
            $table->unique(['studio_id', 'user_uid']);

            // Indici per le query più frequenti
            $table->index('user_uid');
            $table->index(['user_uid', 'is_active']);
            $table->index(['studio_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oc_enamel_studio_users');
    }
};
