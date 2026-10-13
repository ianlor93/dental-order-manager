<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    /**
     * Stati del lead nel funnel di vaglio.
     * Allineati all'enum `status` (migration add_funnel_fields_to_leads_table).
     */
    public const STATUS_NUOVO = 'nuovo';
    public const STATUS_DA_VAGLIARE = 'da_vagliare';
    public const STATUS_APPROVATO = 'approvato';
    public const STATUS_RIFIUTATO = 'rifiutato';

    protected $fillable = [
        'nome',
        'email',
        'studio',
        'piva',
        'tipo_attivita',
        'telefono',
        'source',
        'studio_id',
        'converted_at',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
        'vetted_at' => 'datetime',
        'activated_at' => 'datetime',
        'verify_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'welcome_granted_at' => 'datetime',
    ];

    public function studioRecord(): BelongsTo
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    public function isConverted(): bool
    {
        return $this->converted_at !== null;
    }
}
