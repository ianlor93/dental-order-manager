<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Log append-only degli accessi all'archivio MDR.
 *
 * Non usare update() o delete() su questo modello — è un audit trail immutabile.
 * Solo insert tramite ArchiveAccess::log().
 */
class ArchiveAccess extends Model
{
    protected $table = 'oc_enamel_archive_accesses';

    // Nessun updated_at — log append-only
    const UPDATED_AT = null;

    protected $fillable = [
        'order_code',
        'accessed_by',
        'access_type',
        'file_name',
        'accessed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    /**
     * Registra un accesso all'archivio.
     * Da chiamare nei controller ogni volta che un ordine archiviato viene visualizzato o scaricato.
     *
     * @param  \App\Models\Order  $order
     * @param  string             $accessType  view|download_zip|download_pdf|download_prescription|export_gdpr
     * @param  string|null        $fileName    Nome file scaricato (opzionale)
     */
    public static function log(Order $order, string $accessType, ?string $fileName = null): void
    {
        // Logga solo per ordini archiviati (is_locked) — gli altri non richiedono audit
        if (!$order->is_locked) {
            return;
        }

        try {
            $request = request();
            static::create([
                'order_code'  => $order->code,
                'accessed_by' => auth()->user()?->uid ?? 'system',
                'access_type' => $accessType,
                'file_name'   => $fileName,
                'accessed_at' => now(),
                'ip_address'  => $request->ip(),
                'user_agent'  => substr($request->userAgent() ?? '', 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Il log non deve mai bloccare il download — fallback silenzioso
            \Illuminate\Support\Facades\Log::warning('MDR-ARCHIVE-LOG-FAIL: ' . $e->getMessage(), [
                'order_code'  => $order->code,
                'access_type' => $accessType,
            ]);
        }
    }
}
