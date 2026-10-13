<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    use SoftDeletes; // Conservazione obbligatoria per audit trail MDR

    // 🚩 Questa riga è il "salvavita" per evitare l'errore 500 sui nomi tabella
    protected $table = 'oc_enamel_addresses';

    /**
     * 📝 ATTRIBUTI MASS ASSIGNABLE
     */
    protected $fillable = [
        'studio_id',
        'label',           // Es: "Sede Centrale", "Punto Prelievo"
        'dentist_name',    // Referente clinico della sede
        'dentist_license', // N. Albo specifico della sede
        'albo_provincia',  // Provincia Albo specifica
        'address',
        'city',
        'zip_code',
        'phone',
        'is_main'          // Identifica la sede principale/fiscale
    ];

    /**
     * ⚙️ CASTING DEI DATI
     */
    protected $casts = [
        'is_main'    => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    /**
     * Ogni sede appartiene a uno studio professionale Master.
     */
    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    /**
     * Gli ordini emessi specificatamente per questa sede.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'address_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS (Utility per UI e PDF MDR)
    |--------------------------------------------------------------------------
    */

    /**
     * Indirizzo formattato per logistica e Mover Bridge.
     * Utilizzo: $address->full_address
     */
    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->zip_code} - {$this->city}";
    }

    /**
     * Riepilogo clinico della sede con fallback automatico sui dati Studio.
     * Blindato per evitare errori 500 in caso di studio mancante.
     * Utilizzo: $address->clinician_summary
     */
    public function getClinicianSummaryAttribute(): string
    {
        // Se i dati sulla sede mancano, prova a prenderli dallo studio. 
        // Se manca anche lo studio (emergenza), restituisce "N/D".
        $name = $this->dentist_name ?: ($this->studio->dentist_name ?? 'N/D');
        $license = $this->dentist_license ?: ($this->studio->dentist_license ?? '-');
        $prov = $this->albo_provincia ?: ($this->studio->albo_provincia ?? '-');

        return "Dott. {$name} (Albo: {$license} {$prov})";
    }
}