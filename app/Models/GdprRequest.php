<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GdprRequest extends Model
{
    protected $table = 'oc_enamel_gdpr_requests';

    protected $fillable = [
        'user_uid',
        'right_article',
        'object_category',
        'motivation',
        'received_at',
        'deadline_at',
        'classified_as',
        'decision',
        'decided_at',
        'response_file_ref',
        'case_pdf_ref',
        'handled_by',
        'internal_notes',
        'ip_address',
    ];

    protected $casts = [
        'received_at'  => 'datetime',
        'deadline_at'  => 'datetime',
        'decided_at'   => 'datetime',
    ];

    /**
     * Etichette leggibili per i diritti GDPR.
     */
    public static array $rightLabels = [
        'art15' => 'Art. 15 — Diritto di accesso',
        'art16' => 'Art. 16 — Diritto di rettifica',
        'art17' => 'Art. 17 — Diritto alla cancellazione',
        'art18' => 'Art. 18 — Diritto di restrizione',
        'art19' => 'Art. 19 — Notifica rettifica/cancellazione',
        'art20' => 'Art. 20 — Diritto alla portabilità',
        'art21' => 'Art. 21 — Diritto di opposizione',
    ];

    /**
     * Restituisce le richieste in attesa di risposta scadute o in scadenza.
     */
    public function scopePending($query)
    {
        return $query->where('decision', 'pending');
    }

    /**
     * L'utente Nextcloud che ha fatto la richiesta.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    /**
     * Verifica se la scadenza Art. 12 è stata superata.
     */
    public function isOverdue(): bool
    {
        return $this->decision === 'pending'
            && $this->deadline_at !== null
            && $this->deadline_at->isPast();
    }
}
