<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro append-only degli hash SHA-256 dei PDF MDR.
 *
 * Ogni record rappresenta un evento di generazione/rigenerazione/sigillatura
 * di un PDF MDR. La tabella è APPEND-ONLY: nessun UPDATE o DELETE è ammesso
 * (enforced a livello applicativo nel modello MdrDocumentHash).
 *
 * Un singolo PDF (es. CONFORMITA_MDR_ABC1234.pdf) può avere più righe nel tempo:
 *   - 1 'initial' al momento della generazione iniziale su FIN_L
 *   - 1 'archive_seal' al momento del move FIN_L → ARCH_L
 *   - N 'capa' eventuali, ognuno collegato a un numero CAPA per la rigenerazione
 *
 * MDR Art. 10(8): documentazione tecnica integra per 10-15 anni
 * ISO 13485 §4.2.5: registri leggibili, identificabili, recuperabili e immutabili
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oc_enamel_mdr_document_hashes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('oc_enamel_orders')->cascadeOnDelete();
            $table->string('file_name', 255);                       // es. CONFORMITA_MDR_ABC1234.pdf
            $table->string('disk', 64);                             // unraid_fin_l / unraid_arch_l
            $table->char('sha256', 64);                             // SHA-256 esadecimale lowercase
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('generated_at');                       // momento esatto di registrazione
            $table->enum('generation_source', [
                'initial',          // prima generazione su FIN_L (handleGeneratePdfs)
                'archive_seal',     // sigillo all'archiviazione su ARCH_L (handleMoveToArch)
                'capa',             // rigenerazione postuma per sanatoria (mdr:complete-archived-docs)
                'manual',           // rigenerazione manuale documentata (es. fix dato errato)
            ]);
            $table->string('generated_by', 64)->default('system');   // uid utente o 'system'
            $table->string('capa_reference', 64)->nullable();        // es. CAPA-2026-001
            $table->text('notes')->nullable();                       // motivazione (obbligatoria per capa/manual)
            $table->timestamp('created_at')->useCurrent();
            // NO updated_at — la tabella è append-only

            $table->index(['order_id', 'file_name']);
            $table->index('generation_source');
            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        // NB: in produzione questa tabella non dovrebbe MAI essere droppata.
        // Il down è presente solo per coerenza con il pattern Laravel ed eventuale
        // rollback in ambiente di sviluppo prima del deploy.
        Schema::dropIfExists('oc_enamel_mdr_document_hashes');
    }
};
