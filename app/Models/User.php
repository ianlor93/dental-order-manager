<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use Notifiable;

    // Puntiamo alla tabella di Nextcloud
    protected $table = 'oc_users';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['uid', 'displayname'];
    protected $hidden = ['password'];

    /**
     * Sincronizzazione Display Name
     * FIX: Legge l'attributo grezzo per evitare conflitti e gestisce stringhe vuote.
     */
    public function getDisplayNameAttribute()
    {
        // Leggiamo direttamente l'array degli attributi grezzi
        $name = $this->attributes['displayname'] ?? null;
        
        // Se è null o una stringa vuota, usiamo l'UID
        return !empty($name) ? $name : $this->uid;
    }

    /**
     * ✨ Sincronizzazione Email Nextcloud
     * Recupera l'email dalla tabella delle preferenze di Nextcloud.
     * Necessario per le comunicazioni di sistema.
     */
    public function getEmailAttribute()
    {
        return DB::table('oc_preferences')
            ->where('userid', $this->uid)
            ->where('appid', 'settings')
            ->where('configkey', 'email')
            ->value('configvalue');
    }

    /**
     * Studio di cui l'utente è TITOLARE (owner).
     * Corrisponde a studios.user_id = user.uid — rapporto 1:1 con vincolo UNIQUE.
     * Usa withDefault() per non restituire null in caso di onboarding non completato.
     */
    public function studio(): HasOne
    {
        return $this->hasOne(Studio::class, 'user_id', 'uid')->withDefault([
            'name' => 'Studio in fase di configurazione',
            'dentist_name' => 'N/D',
            'tier_level' => 'L0'
        ]);
    }

    /**
     * Membership negli studi come prescrittore/admin/viewer (multi-prescrittore).
     * Un utente può essere membro di N studi di cui non è titolare.
     * Relazione verso la tabella pivot oc_enamel_studio_users.
     */
    public function studioMemberships(): HasMany
    {
        return $this->hasMany(StudioUser::class, 'user_uid', 'uid');
    }

    /**
     * Restituisce lo Studio "attivo" per questo utente:
     * 1. Se l'utente è titolare (studio.user_id = uid), usa quello.
     * 2. Altrimenti cerca il primo studio attivo di cui è membro via pivot.
     *
     * Questo metodo è usato dal gate EnsureLegalCompliance e dai controller
     * per determinare il contesto operativo dell'utente corrente.
     * Il risultato viene messo in sessione per evitare query ripetute.
     *
     * @return Studio|null  null solo se l'utente non ha nessuno studio (nuovo utente)
     */
    public function activeStudio(): ?Studio
    {
        // 1. Titolare: recupera il suo studio direttamente
        $owned = Studio::where('user_id', $this->uid)->first();
        if ($owned) {
            return $owned;
        }

        // 2. Membro: prende il primo studio attivo nel pivot
        $membership = $this->studioMemberships()
            ->where('is_active', true)
            ->with('studio')
            ->first();

        return $membership?->studio;
    }

    /**
     * Ruolo dell'utente nello studio specificato.
     * Restituisce 'owner' se è il titolare, il ruolo dal pivot altrimenti, null se non appartiene.
     */
    public function roleInStudio(Studio $studio): ?string
    {
        if ($studio->user_id === $this->uid) {
            return 'owner';
        }

        $membership = StudioUser::where('studio_id', $studio->id)
            ->where('user_uid', $this->uid)
            ->where('is_active', true)
            ->first();

        return $membership?->role;
    }

    /**
     * Controllo Gruppi Nextcloud (Tabella oc_group_user)
     */
    public function isInGroups($groups): bool
    {
        try {
            $groups = is_array($groups) ? $groups : [$groups];
            return DB::table('oc_group_user')
                ->where('uid', $this->uid)
                ->whereIn('gid', $groups)
                ->exists();
        } catch (\Exception $e) {
            Log::error("MDR-DB-SECURITY: Errore query gruppi per {$this->uid}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Logica Ruoli Staff
     */
    public function isStaff(): bool
    {
        if ($this->uid === 'admin') return true;
        return $this->isInGroups(['admin', 'Amministrazione', 'Operativi']);
    }

    /**
     * Logica Ruoli Cliente
     */
    public function isCliente(): bool
    {
        return $this->isInGroups('Clienti');
    }

    /**
     * ✨ Logica Ruolo Laboratorio Partner
     * Riconosce i membri del gruppo 'Clienti-Laboratorio'
     */
    public function isLaboratorio(): bool
    {
        return $this->isInGroups('Clienti-Laboratorio');
    }

    /**
     * ✨ Logica Ruolo Agente / Rappresentante commerciale
     * Riconosce i membri del gruppo 'Agenti' (rete vendita).
     */
    public function isAgente(): bool
    {
        return $this->isInGroups('Agenti');
    }

    /**
     * 🚩 DISABILITAZIONE REMEMBER TOKEN 
     * Poiché la tabella oc_users non ha la colonna remember_token,
     * sovrascriviamo i metodi per evitare crash di database.
     */
    public function getRememberTokenName() { return ''; }
}