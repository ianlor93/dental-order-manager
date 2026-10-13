<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryNote extends Model
{
    protected $table = 'oc_enamel_delivery_notes';

    protected $fillable = [
        'studio_id',
        'courier',
        'tracking_code',
        'delivery_date',
        'billed_at',            // Data in cui è stata inclusa nel riepilogo finale
        'monthly_summary_ref'   // Riferimento al PDF riepilogativo
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
        'billed_at' => 'datetime',
    ];

    /**
     * Lo Studio a cui è intestata la consegna
     */
    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    /**
     * Gli ordini contenuti in questa consegna
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'delivery_note_id');
    }
    
    /**
     * Scope per trovare le distinte non ancora riepilogate (per la fase 2)
     */
    public function scopeUnbilled($query)
    {
        return $query->whereNull('billed_at');
    }
}