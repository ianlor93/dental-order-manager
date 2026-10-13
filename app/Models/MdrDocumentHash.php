<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * MdrDocumentHash — Registro append-only degli hash SHA-256 dei PDF MDR.
 *
 * REGOLE DI IMMUTABILITÀ:
 *   - UPDATE bloccato a livello applicativo (booted::updating → exception)
 *   - DELETE bloccato a livello applicativo (booted::deleting → exception)
 *   - Nessun SoftDeletes (la riga non può essere mai rimossa, neanche logicamente)
 *
 * Per ogni evento di generazione/rigenerazione di un PDF MDR si APPENDE una nuova
 * riga. La storia completa di un file è data dalla query:
 *
 *   MdrDocumentHash::where('order_id', $id)
 *                  ->where('file_name', $name)
 *                  ->orderBy('generated_at')
 *                  ->get();
 *
 * L'hash "corrente" (quello da confrontare col file su disco oggi) è l'ultimo record
 * di quella catena. Se la catena ha più di un record è un file rigenerato — legittimo
 * solo se ogni record successivo al primo ha generation_source ∈ ['capa', 'manual']
 * e capa_reference / notes valorizzati (audit trail SGQ).
 */
class MdrDocumentHash extends Model
{
    protected $table = 'oc_enamel_mdr_document_hashes';

    public $timestamps = false; // usiamo generated_at (firma temporale) + created_at automatico

    protected $fillable = [
        'order_id',
        'file_name',
        'disk',
        'sha256',
        'size_bytes',
        'generated_at',
        'generation_source',
        'generated_by',
        'capa_reference',
        'notes',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (MdrDocumentHash $hash) {
            Log::critical('MDR-HASH-IMMUTABILITY: tentativo di UPDATE su registro hash MDR', [
                'id' => $hash->id,
                'order_id' => $hash->order_id,
                'file_name' => $hash->file_name,
                'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ]);
            throw new \RuntimeException(
                'Il registro degli hash MDR è APPEND-ONLY. Non è possibile modificare un record esistente. ' .
                'Per registrare una rigenerazione, creare un nuovo record con generation_source=capa o manual.'
            );
        });

        static::deleting(function (MdrDocumentHash $hash) {
            Log::critical('MDR-HASH-IMMUTABILITY: tentativo di DELETE su registro hash MDR', [
                'id' => $hash->id,
                'order_id' => $hash->order_id,
                'file_name' => $hash->file_name,
                'caller' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ]);
            throw new \RuntimeException(
                'Il registro degli hash MDR è APPEND-ONLY. Non è possibile eliminare un record. ' .
                'Conservazione obbligatoria MDR Art. 10(8) + ISO 13485 §4.2.5.'
            );
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Registra un nuovo hash calcolandolo dal file su disco.
     * Helper canonico — usare sempre questo metodo per garantire coerenza.
     *
     * @param Order  $order
     * @param string $diskName            es. 'unraid_fin_l'
     * @param string $relativePath        path relativo al disco, es. "ABC1234_PAZIENTE/CONFORMITA_MDR_ABC1234.pdf"
     * @param string $source              'initial' | 'archive_seal' | 'capa' | 'manual'
     * @param array  $opts                ['by' => uid, 'capa_reference' => ..., 'notes' => ...]
     */
    public static function record(Order $order, string $diskName, string $relativePath, string $source, array $opts = []): self
    {
        $disk = Storage::disk($diskName);
        if (!$disk->exists($relativePath)) {
            throw new \RuntimeException("MdrDocumentHash::record: file inesistente {$diskName}:{$relativePath}");
        }
        $fullPath = $disk->path($relativePath);
        $sha = hash_file('sha256', $fullPath);
        if ($sha === false) {
            throw new \RuntimeException("MdrDocumentHash::record: impossibile calcolare SHA-256 di {$relativePath}");
        }

        if (!in_array($source, ['initial', 'archive_seal', 'capa', 'manual'], true)) {
            throw new \InvalidArgumentException("generation_source non valido: {$source}");
        }

        // Per source 'capa' e 'manual' richiediamo motivazione + riferimento CAPA (per audit trail)
        if (in_array($source, ['capa', 'manual'], true)) {
            if (empty($opts['notes'])) {
                throw new \InvalidArgumentException("Per source={$source} è obbligatorio fornire 'notes' con motivazione.");
            }
        }

        return self::create([
            'order_id'          => $order->id,
            'file_name'         => basename($relativePath),
            'disk'              => $diskName,
            'sha256'            => $sha,
            'size_bytes'        => filesize($fullPath) ?: null,
            'generated_at'      => now(),
            'generation_source' => $source,
            'generated_by'      => $opts['by'] ?? (auth()->user()?->uid ?? 'system'),
            'capa_reference'    => $opts['capa_reference'] ?? null,
            'notes'             => $opts['notes'] ?? null,
        ]);
    }

    /**
     * Ritorna l'hash "corrente" (l'ultimo registrato) per uno specifico file di un ordine.
     */
    public static function currentFor(int $orderId, string $fileName): ?self
    {
        return self::where('order_id', $orderId)
            ->where('file_name', $fileName)
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Catena completa di hash per un file (ordinata cronologicamente).
     */
    public static function chainFor(int $orderId, string $fileName)
    {
        return self::where('order_id', $orderId)
            ->where('file_name', $fileName)
            ->orderBy('generated_at')
            ->orderBy('id')
            ->get();
    }
}
