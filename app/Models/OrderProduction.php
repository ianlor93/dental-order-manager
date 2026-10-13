<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProduction extends Model
{
    // Tabella dedicata alla documentazione tecnica di produzione (MDR)
    protected $table = 'oc_enamel_order_productions';

    /**
     * 🛡️ MASS ASSIGNMENT
     * Inclusi tutti i campi per la tracciabilità CAD/CAM e Controllo Qualità.
     */
    protected $fillable = [
        'order_id',
        'cad_operator',
        'cad_teeth_library',
        'cad_implant_library',
        'cad_notes',
        'cam_data',      // JSON: Macchine e Lotti materiale
        'lab_color',
        'lab_operator',  // Fondamentale: operatore che ha rifinito il lavoro
        'lab_materials', // JSON: Materiali di rivestimento/rifinitura
        'lab_notes',
        'qc_verifier',
        'qc_checklist',  // JSON: Punti di controllo superati (OK/KO)
        'qc_status',      // 'approved' o 'rejected'
        'released_at'    // Data di emissione della dichiarazione di conformità
    ];

    /**
     * ⚙️ CASTING
     * Trasforma automaticamente le stringhe JSON del database in Array PHP e viceversa.
     */
    protected $casts = [
        'order_id'          => 'integer',
        'cam_data'          => 'array',
        'lab_materials'     => 'array',
        'qc_checklist'      => 'array',
        'cad_teeth_library' => 'array', 
        'released_at'       => 'datetime',
    ];

    /**
     * Riferimento all'ordine (prescrizione medica) originale.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Helper di sicurezza per evitare errori nei cicli dei PDF
     */
    public function getCamData(): array
    {
        return is_array($this->cam_data) ? $this->cam_data : [];
    }

    public function isApproved(): bool
    {
        return $this->qc_status === 'approved';
    }

    public function isReleased(): bool
    {
        return !is_null($this->released_at) && $this->isApproved();
    }
}