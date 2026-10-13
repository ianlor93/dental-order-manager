<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\LegalText;

/**
 * Accettazione legale granulare (GDPR Art. 7 + 5(2), Art. 1341 c.c. B2B IT).
 *
 * Regole d'uso:
 *   - Mai aggiornare un record esistente — creare nuovo record per correzioni/ri-firme
 *   - Mai cancellare — il record resta come prova legale
 *   - Il legal gate continua a leggere la cache denormalizzata su Studio
 *     (tos_accepted_at, privacy_accepted_at, mdr_accepted_at, dpa_accepted_at)
 *
 * Il LegalController (onboarding 4-step, Blocco 2) scrive sia su questa tabella
 * sia sulla cache Studio al completamento dello step 4.
 */
class LegalAcceptance extends Model
{
    protected $table = 'oc_enamel_legal_acceptances';

    protected $fillable = [
        'studio_id',
        'user_uid',
        'document_type',
        'version',
        'accepted_at',
        'ip_address',
        'user_agent',
        'content_hash',
        'content_snapshot_path',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public const DOCUMENT_TYPES = ['tos', 'vessatorie', 'privacy', 'dpa', 'mdr'];

    // -------------------------------------------------------------------------
    // RELAZIONI
    // -------------------------------------------------------------------------

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    // -------------------------------------------------------------------------
    // UTILITY
    // -------------------------------------------------------------------------

    /**
     * Verifica se uno studio ha già accettato un documento in una versione specifica.
     * Usato dal LegalController (step avanzati) e dal EnsureLegalCompliance middleware.
     */
    public static function hasAccepted(int $studioId, string $type, string $version): bool
    {
        return static::where('studio_id', $studioId)
            ->where('document_type', $type)
            ->where('version', $version)
            ->exists();
    }

    /**
     * Calcola SHA-256 del file di testo legale corrispondente.
     * Usare in LegalController prima di creare il record, per tracciare
     * quale versione esatta del testo è stata firmata.
     *
     * @throws \InvalidArgumentException se il tipo documento non è riconosciuto
     * @throws \RuntimeException se il file legale non esiste su disco
     */
    public static function hashLegalFile(string $documentType): string
    {
        $map = [
            'tos'        => 'tos-mdr.txt',
            'vessatorie' => 'vessatorie-mdr.txt',  // 2026.4: file dedicato (clausole Art. 1341)
            'privacy'    => 'privacy-mdr.txt',
            'dpa'        => 'dpa-mdr.txt',
            'mdr'        => 'tos-mdr.txt',    // Deprecato — stesso file ToS per storico
        ];

        $filename = $map[$documentType]
            ?? throw new \InvalidArgumentException("Document type non valido: {$documentType}");

        $path = LegalText::path($filename);

        if (!file_exists($path)) {
            throw new \RuntimeException("File legale mancante: {$path}");
        }

        return hash_file('sha256', $path);
    }
}
