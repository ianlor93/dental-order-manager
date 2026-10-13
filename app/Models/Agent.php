<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agente / Rappresentante commerciale.
 *
 * Un agente gestisce un portfolio di studi clienti (oc_enamel_studios.agent_id).
 * Le commissioni sono calcolate sugli ordini fatturati degli studi del suo portfolio.
 *
 * In v1 l'agente non ha accesso diretto alla piattaforma come utente autenticato.
 * La dashboard agenti (v2) richiederà un account Nextcloud nel gruppo 'Agenti'.
 *
 * Note GDPR: l'agente accede a dati anagrafici degli studi (nome, P.IVA, volume aggregato).
 * Non ha mai accesso a patient_ref, file CAD/CAM, o qualsiasi dato clinico.
 * È nominato nel DPA del cliente come soggetto autorizzato con accesso limitato.
 *
 * @property int         $id
 * @property string      $code
 * @property string      $name
 * @property string      $email
 * @property string|null $phone
 * @property string|null $mobile
 * @property string|null $fiscal_code
 * @property string|null $vat_number
 * @property string|null $pec_email
 * @property string|null $iban
 * @property string|null $bank_name
 * @property float       $commission_rate
 * @property string      $commission_basis
 * @property string|null $territory
 * @property \Carbon\Carbon|null $contract_started_at
 * @property \Carbon\Carbon|null $contract_ended_at
 * @property string      $status
 * @property string|null $notes
 */
class Agent extends Model
{
    use SoftDeletes;

    protected $table = 'oc_enamel_agents';

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'mobile',
        'fiscal_code',
        'vat_number',
        'pec_email',
        'iban',
        'bank_name',
        'commission_rate',
        'commission_basis',
        'territory',
        'contract_started_at',
        'contract_ended_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'commission_rate'    => 'decimal:2',
        'contract_started_at' => 'date',
        'contract_ended_at'   => 'date',
    ];

    // -------------------------------------------------------------------------
    // Relazioni
    // -------------------------------------------------------------------------

    /**
     * Studi nel portfolio di questo agente.
     */
    public function studios(): HasMany
    {
        return $this->hasMany(Studio::class, 'agent_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Numero di studi attivi nel portfolio.
     */
    public function getPortfolioCountAttribute(): int
    {
        return $this->studios()->count();
    }

    /**
     * Etichetta status per la UI.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'Attivo',
            'inactive'  => 'Inattivo',
            'suspended' => 'Sospeso',
            default     => ucfirst($this->status),
        };
    }

    /**
     * Contratto attivo oggi?
     */
    public function isContractActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->contract_ended_at && $this->contract_ended_at->isPast()) {
            return false;
        }

        return true;
    }
}
