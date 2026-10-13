<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot arricchito: appartenenza di un utente Nextcloud a uno studio.
 *
 * Ruoli:
 *   owner      → titolare del contratto (corrisponde a Studio::user_id). Creato automaticamente
 *                alla creazione dello studio. Non eliminabile finché è l'unico owner.
 *   admin      → gestione amministrativa: ordini, fatturazione, sedi, membri.
 *                Non necessariamente abilitato alla prescrizione.
 *   prescriber → dentista/odontoiatra che crea e valida ordini. Ha dati albo propri.
 *   viewer     → sola lettura. Non può creare ordini né gestire lo studio.
 *
 * Dati clinici per sede: i campi dentist_name/dentist_license/albo_provincia di questo modello
 * sovrascrivono i campi master dello Studio per questo specifico prescrittore.
 * Se null, si usa il fallback su Studio (accessor clinician_summary su Address).
 *
 * @property int         $id
 * @property int         $studio_id
 * @property string      $user_uid
 * @property string      $role
 * @property string|null $dentist_name
 * @property string|null $dentist_license
 * @property string|null $albo_provincia
 * @property string|null $qualification
 * @property array|null  $permissions
 * @property bool        $can_prescribe
 * @property bool        $is_active
 * @property string|null $invited_by_uid
 * @property \Carbon\Carbon|null $invited_at
 * @property \Carbon\Carbon|null $joined_at
 * @property \Carbon\Carbon|null $deactivated_at
 * @property string|null $notes
 */
class StudioUser extends Model
{
    protected $table = 'oc_enamel_studio_users';

    protected $fillable = [
        'studio_id',
        'user_uid',
        'role',
        'dentist_name',
        'dentist_license',
        'albo_provincia',
        'qualification',
        'permissions',
        'can_prescribe',
        'is_active',
        'invited_by_uid',
        'invited_at',
        'joined_at',
        'deactivated_at',
        'notes',
    ];

    protected $casts = [
        'permissions'    => 'array',
        'can_prescribe'  => 'boolean',
        'is_active'      => 'boolean',
        'invited_at'     => 'datetime',
        'joined_at'      => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relazioni
    // -------------------------------------------------------------------------

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    /**
     * L'utente Nextcloud associato a questa membership.
     * Usa la FK logica user_uid → oc_users.uid (chiave non-incrementale stringa).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    /**
     * Chi ha inviato l'invito (opzionale — può essere null per owner creati automaticamente).
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_uid', 'uid');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrescribers($query)
    {
        return $query->where('can_prescribe', true)->where('is_active', true);
    }

    public function scopeOwners($query)
    {
        return $query->where('role', 'owner');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Controlla se questo membro ha un permesso specifico.
     * Segue la gerarchia: permissions JSON > default del ruolo.
     */
    public function hasPermission(string $permission): bool
    {
        // Permessi espliciti nel JSON sovrascrivono i default
        if ($this->permissions && isset($this->permissions[$permission])) {
            return (bool) $this->permissions[$permission];
        }

        // Default per ruolo
        return match ($this->role) {
            'owner'      => true, // owner ha tutto
            'admin'      => in_array($permission, [
                'create_orders', 'validate_prescriptions',
                'view_billing', 'manage_members', 'manage_addresses',
            ]),
            'prescriber' => in_array($permission, [
                'create_orders', 'validate_prescriptions',
            ]),
            'viewer'     => false,
            default      => false,
        };
    }

    /**
     * Etichetta leggibile del ruolo per la UI.
     */
    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'owner'      => 'Titolare',
            'admin'      => 'Amministratore',
            'prescriber' => 'Prescrittore',
            'viewer'     => 'Lettore',
            default      => ucfirst($this->role),
        };
    }

    /**
     * Nome del prescrittore da mostrare: usa il campo locale se compilato,
     * altrimenti torna al displayname Nextcloud dell'utente.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->dentist_name) {
            return $this->dentist_name;
        }

        return $this->user?->displayname ?? $this->user_uid;
    }
}
