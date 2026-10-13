<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\LegalAcceptance;

class Studio extends Model
{
    /**
     * 🛡️ CONSERVAZIONE DATI (MDR 2017/745)
     * La cancellazione fisica è inibita. I dati vengono marcati come eliminati
     * ma restano nel database per gli obblighi di tracciabilità decennale.
     */
    use SoftDeletes; 

    protected $table = 'oc_enamel_studios';

    /**
     * 📝 CAMPI COMPILABILI MASSIVAMENTE (Mass Assignment)
     * Inclusi i nuovi campi per la gestione dei Tier e del Billing.
     */
    protected $fillable = [
        'user_id',             // UID Nextcloud (Ponte di collegamento)
        'name',                // Ragione Sociale
        'fiscal_code',         // Codice Fiscale
        'vat_number',          // Partita IVA
        'sdi_code',            // Codice Univoco SDI
        'itca_code',           // ✨ NUOVO: Codice Fabbricante Dispositivi Medici (ITCA)
        'pec_email',           // Indirizzo PEC
        'phone',               // Telefono Fisso
        'mobile',              // Cellulare di riferimento
        'address',             // Indirizzo Sede Legale
        'city',                // Città Sede Legale
        'zip_code',            // CAP Sede Legale
        'dentist_name',        // Nome Medico Prescrivente (Master)
        'dentist_license',     // N. Iscrizione Albo (Master)
        'albo_provincia',      // Provincia Albo (Master)
        'privacy_accepted_at', // Timestamp GDPR
        'tos_accepted_at',     // Timestamp Termini Servizio
        'mdr_accepted_at',     // Timestamp Protocollo MDR
        'dpa_accepted_at',     // Timestamp Nomina DPA
        'legal_version',       // Versione dei documenti sottoscritti
        
        // 💳 NUOVI CAMPI BILLING & TIERS
        'tier_level',              // L0, L1, L2, L3, L4
        'subscription_plan',       // monthly, annual
        'credits_gift',            // Crediti omaggio mensili
        'credits_purchased',       // Crediti acquistati (Pack)
        'subscription_expires_at', // Scadenza abbonamento
        'grace_period_until',      // Paracadute di 3 giorni

        // 🤝 RETE VENDITA
        'agent_id',                // Agente/rappresentante commerciale assegnato (nullable)
        'agent_assigned_at',       // Data assegnazione agente

        // 🛑 GDPR Art. 18 — Restrizione trattamento
        'restricted_at',
        'restricted_reason',
        'restriction_review_date',
        'restriction_case_id',
    ];

    /**
     * 📅 CASTING DELLE PROPRIETÀ
     */
    protected $casts = [
        'privacy_accepted_at'     => 'datetime',
        'tos_accepted_at'         => 'datetime',
        'mdr_accepted_at'         => 'datetime',
        'dpa_accepted_at'         => 'datetime',
        'subscription_expires_at' => 'datetime',
        'grace_period_until'      => 'datetime',
        'agent_assigned_at'       => 'date',
        'restricted_at'           => 'datetime',
        'restriction_review_date' => 'date',
        'deleted_at'              => 'datetime',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI (IL CUORE DEL SISTEMA)
    |--------------------------------------------------------------------------
    */

    /**
     * 👤 RELAZIONE UTENTE
     * Collega lo studio all'identità Nextcloud tramite l'UID.
     * Fondamentale per i permessi di Dashboard.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'uid');
    }

    /**
     * 📍 RELAZIONE LOGISTICA (MULTI-SEDE)
     * Questo metodo permette al DashboardController di caricare
     * tutte le sedi operative legate a questa entità fiscale.
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class, 'studio_id');
    }

    /**
     * 📦 RELAZIONE ORDINI
     * Tracciabilità totale degli ordini emessi dallo studio.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'studio_id');
    }
    
    /**
     * 🚚 RELAZIONE DISTINTE DI CONSEGNA (NUOVA)
     * Tracciabilità delle spedizioni effettuate verso lo studio.
     */
    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class, 'studio_id');
    }

    /**
     * 👥 MULTI-PRESCRITTORE — Membership pivot
     * Tutti gli utenti (prescrittori, admin, viewer) associati a questo studio,
     * incluso il titolare (role='owner').
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(StudioUser::class, 'studio_id');
    }

    /**
     * Soli utenti attivi tramite BelongsToMany per query comode.
     * Usa la tabella pivot oc_enamel_studio_users con i campi extra.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'oc_enamel_studio_users', 'studio_id', 'user_uid', 'id', 'uid')
            ->using(StudioUser::class)
            ->withPivot([
                'role', 'dentist_name', 'dentist_license', 'albo_provincia',
                'qualification', 'permissions', 'can_prescribe', 'is_active',
                'invited_at', 'joined_at',
            ])
            ->withTimestamps()
            ->wherePivot('is_active', true);
    }

    /**
     * Solo i prescrittori attivi (ruolo prescriber + owner con can_prescribe=true).
     */
    public function activePrescribers(): BelongsToMany
    {
        return $this->members()
            ->wherePivotIn('role', ['owner', 'prescriber', 'admin'])
            ->wherePivot('can_prescribe', true);
    }

    /**
     * 📜 REGISTRO LEGALE GRANULARE (GDPR Art. 7 + 5(2), Art. 1341 c.c.)
     * Ogni record = una firma di un documento in una versione specifica.
     * Append-only: non aggiornare record esistenti.
     * I timestamp *_accepted_at su questa tabella restano come cache denormalizzata
     * per il legal gate (EnsureLegalCompliance middleware) — non modificare.
     */
    public function legalAcceptances(): HasMany
    {
        return $this->hasMany(LegalAcceptance::class, 'studio_id')
            ->orderByDesc('accepted_at');
    }

    /**
     * L'ultima accettazione valida di un tipo documento per la versione corrente.
     */
    public function latestAcceptance(string $documentType, ?string $version = null): ?LegalAcceptance
    {
        $version = $version ?? config('legal.version');
        return $this->legalAcceptances()
            ->where('document_type', $documentType)
            ->where('version', $version)
            ->first();
    }

    /**
     * 🤝 RETE VENDITA — Agente assegnato a questo studio.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * Questo studio ha un agente commerciale attivo?
     */
    public function hasActiveAgent(): bool
    {
        return $this->agent_id !== null
            && $this->agent !== null
            && $this->agent->status === 'active';
    }
}