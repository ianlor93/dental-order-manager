<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Model, SoftDeletes, Builder};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Support\Facades\Storage;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'oc_enamel_orders';

    public function getRouteKeyName()
    {
        return 'code';
    }

    protected $fillable = [
        'code', 'user_id', 'studio_id', 'address_id', 'patient_ref',
        'device_type', 
        'manufactured_devices',
        'final_device_slug', 'final_device_name', 
        'teeth_notation', 'material', 'notes', 'status', 
        'upload_session_id', 'expected_files_count', 'priority', 'delivery_date', 
        'source', 'directory_name', 'prescriber_qualification', 'sanitary_institution', 
        'design_features', 'lot_number', 'materials_used', 'technician_signature', 
        'validated_at', 'validation_hash', 'production_hash', 'conformity_hash', 'is_locked', 'is_trial',
        'archived_at', 'legal_retention_until',
        'risk_class', 'mdr_rule_applied',
        'pms_status', 'pms_last_review_at', 'pms_next_review_at',
        'billed_at', 'invoice_number',
        'delivery_note_id',
        'deletion_requested_at', 'deletion_reason',
        'incident_reported_at', 'incident_severity', 'incident_notification_deadline',
        'incident_resolved_at', 'incident_resolution_notes',
        // 🛑 GDPR Art. 18 — Restrizione trattamento (non interferisce con is_locked MDR)
        'restricted_at', 'restricted_reason', 'restriction_review_date', 'restriction_case_id',
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
        'validated_at' => 'datetime',
        'archived_at' => 'datetime',
        'legal_retention_until' => 'datetime',
        'deleted_at' => 'datetime',
        'is_locked' => 'boolean',
        'is_trial' => 'boolean',
        'pms_last_review_at'              => 'datetime',
        'pms_next_review_at'              => 'datetime',
        'incident_reported_at'            => 'datetime',
        'incident_notification_deadline'  => 'datetime',
        'incident_resolved_at'            => 'datetime',
        'manufactured_devices' => 'array',
        'billed_at'                => 'datetime',
        'deletion_requested_at'    => 'datetime',
        'restricted_at'           => 'datetime',
        'restriction_review_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeUnbilled(Builder $query): Builder
    {
        return $query->where('status', 'archived')->whereNull('billed_at');
    }

    public function scopeArchivedIn(Builder $query, $month, $year): Builder
    {
        return $query->whereMonth('archived_at', $month)->whereYear('archived_at', $year);
    }
    
    /**
     * ✨ SCOPE LOGISTICO: Filtra ordini pronti ma non ancora consegnati
     */
    public function scopeReadyForDelivery(Builder $query): Builder
    {
        return $query->where('status', 'pronto_consegna')->whereNull('delivery_note_id');
    }

    /**
     * 🛑 GDPR Art. 18 — Ordini sotto restrizione del trattamento attiva.
     */
    public function scopeRestricted(Builder $query): Builder
    {
        return $query->whereNotNull('restricted_at');
    }

    /**
     * 🛑 GDPR Art. 18 — Ordini NON sotto restrizione.
     */
    public function scopeNotRestricted(Builder $query): Builder
    {
        return $query->whereNull('restricted_at');
    }

    /*
    |--------------------------------------------------------------------------
    | METODI DI SISTEMA
    |--------------------------------------------------------------------------
    */

    public function unlockPermissions(string $diskName)
    {
        if (!$this->directory_name) return;

        try {
            $path = Storage::disk($diskName)->path($this->directory_name);
            if (file_exists($path)) {
                exec("chmod -R 777 " . escapeshellarg($path));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("MDR-CHMOD-FAIL [{$this->code}] su {$diskName}: " . $e->getMessage());
        }
    }

    /**
     * Risolve la cartella fisica dell'ordine su un disco cercandola per prefisso "{CODE}_".
     * Robusto al drift di directory_name (caso reale riscontrato in produzione): una cartella col nome vecchio
     * viene comunque trovata. Ritorna il nome cartella reale se ESATTAMENTE 1 match; null se 0;
     * eccezione se >1 (mai scegliere a caso — l'ambiguità va risolta a mano).
     */
    public function resolveDirectoryOnDisk(string $diskName): ?string
    {
        $prefix  = $this->code . '_';
        $matches = array_values(array_filter(
            Storage::disk($diskName)->directories(),
            fn ($d) => str_starts_with(basename($d), $prefix)
        ));

        if (count($matches) > 1) {
            throw new \RuntimeException(
                "MDR-DIR-AMBIGUOUS [{$this->code}] su {$diskName}: " . count($matches) .
                " cartelle {$prefix}* trovate — " . implode(', ', array_map('basename', $matches))
            );
        }

        return count($matches) === 1 ? basename($matches[0]) : null;
    }

    protected static function booted()
    {
        static::deleting(function ($order) {
            if ($order->is_locked) {
                throw new \Exception("MDR LOCK: Eliminazione inibita per conservazione legale.");
            }
        });

        static::updated(function ($order) {
            if ($order->isDirty('status')) {
                $oldS = strtoupper(str_replace('_', ' ', $order->getOriginal('status') ?? 'DRAFT'));
                $newS = strtoupper(str_replace('_', ' ', $order->status));
                $order->logSystemEvent("Avanzamento Workflow: da {$oldS} a {$newS}");
            }
            
            if ($order->isDirty('billed_at') && $order->billed_at) {
                $order->logSystemEvent("AREA CONTABILE: Ordine marcato come FATTURATO.");
            }
        });
    }

    public function logSystemEvent($body)
    {
        return $this->comments()->create([
            'user_uid' => auth()->user()->uid ?? 'system',
            'body' => "🤖 SISTEMA: " . $body,
            'is_internal' => false
        ]);
    }

    /**
     * Elenco dei 4 PDF MDR che devono esistere prima dell'archiviazione (Art. 10(8) MDR).
     */
    public function requiredMdrDocuments(): array
    {
        return [
            "SCHEDA_TECNICA_{$this->code}.pdf",
            "CONFORMITA_MDR_{$this->code}.pdf",
            "ETICHETTA_{$this->code}.pdf",
            "INFORMATIVA_PAZIENTE_{$this->code}.pdf",
        ];
    }

    /**
     * Ritorna l'elenco dei PDF MDR mancanti su un disco (default: FIN_L).
     */
    public function missingMdrDocuments(string $diskName = 'unraid_fin_l'): array
    {
        $disk = Storage::disk($diskName);
        // FIX ④ — coerente coi mover: cerca nella cartella REALE (robusto al drift), fallback al canonico.
        $dir = $this->resolveDirectoryOnDisk($diskName) ?? $this->directory_name;
        $missing = [];
        foreach ($this->requiredMdrDocuments() as $pdf) {
            if (!$disk->exists("{$dir}/{$pdf}")) $missing[] = $pdf;
        }
        return $missing;
    }

    /**
     * True se tutti i 4 PDF MDR sono presenti su FIN_L.
     */
    public function hasMdrDocuments(string $diskName = 'unraid_fin_l'): bool
    {
        return empty($this->missingMdrDocuments($diskName));
    }

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    public function comments(): HasMany { return $this->hasMany(OrderComment::class, 'order_id'); }
    public function studio(): BelongsTo { return $this->belongsTo(Studio::class, 'studio_id'); }
    public function address(): BelongsTo { return $this->belongsTo(Address::class, 'address_id'); }
    
    // 🛡️ REINSERITA: La relazione che causava il 500
    public function files(): HasMany { return $this->hasMany(OrderFile::class, 'order_id'); }
    
    public function production(): HasOne { return $this->hasOne(OrderProduction::class, 'order_id'); }
    public function deliveryNote(): BelongsTo { return $this->belongsTo(DeliveryNote::class, 'delivery_note_id'); }
}