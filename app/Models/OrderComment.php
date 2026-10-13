<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class OrderComment extends Model
{
    /**
     * Campi compilabili massivamente.
     */
    protected $fillable = [
        'order_id', 
        'user_uid', 
        'body', 
        'is_internal'
    ];

    /**
     * Cast dei tipi per una gestione corretta dei booleani.
     */
    protected $casts = [
        'is_internal' => 'boolean',
        'created_at'  => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELAZIONI
    |--------------------------------------------------------------------------
    */

    /**
     * Relazione con l'Ordine di appartenenza.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relazione con l'Utente (Nextcloud) che ha scritto il commento.
     * Collega user_uid alla tabella oc_users tramite il modello User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES DI SICUREZZA (FILTRI)
    |--------------------------------------------------------------------------
    */

    /**
     * 🛡️ SCOPE: Filtra i commenti in base ai permessi dell'utente.
     * * Esempio d'uso nel controller: 
     * OrderComment::where('order_id', $id)->visibleTo(Auth::user())->get();
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (\App\Support\AccessContext::for($user)->isStaff()) {
            // Lo staff ha il "passpartout": vede tutto (interni e pubblici)
            return $query;
        }

        // Il cliente vede solo i commenti NON interni
        return $query->where('is_internal', false);
    }
}