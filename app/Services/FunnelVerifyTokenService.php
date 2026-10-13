<?php

namespace App\Services;

use App\Models\Lead;

/**
 * Funnel — token di verifica email / set-password (Blocco 1).
 *
 * Decisione d'impianto A: il token È la credenziale. In `leads` viene salvato SOLO
 * l'hash SHA-256; il plaintext vive esclusivamente nel link della mail di verifica.
 * Single-use (azzerato dal controller su `complete`) + scadenza 48h.
 */
class FunnelVerifyTokenService
{
    /** Validità del token di verifica (ore). */
    public const TTL_HOURS = 48;

    /**
     * Genera un token random URL-safe (256 bit → 64 hex), imposta sul lead l'HASH
     * e la scadenza, e ritorna il PLAINTEXT (da mettere SOLO nella mail).
     *
     * NON salva il modello: la persistenza è a carico del chiamante (così resta nella
     * stessa scrittura/transazione del campo `created_user_uid` in ProvisioningController).
     */
    public function mint(Lead $lead): string
    {
        $plain = bin2hex(random_bytes(32)); // 64 hex, URL-safe

        $lead->verify_token      = $this->hash($plain); // guarded → set esplicito
        $lead->verify_expires_at = now()->addHours(self::TTL_HOURS);

        return $plain;
    }

    /** Hash canonico del token — lo stesso usato per il match in `leads`. */
    public function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
