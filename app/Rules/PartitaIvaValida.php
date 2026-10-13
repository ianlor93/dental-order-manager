<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Checksum della Partita IVA italiana (DPR 633/72 art. 35 — Luhn modificato CEE).
 *
 * Estratto dal controllo inline di LeadController::store() (congelato) per riuso
 * SOLO nel funnel di registrazione (FunnelRegistrationRequest). Verifica il solo
 * checksum sulle 11 cifre: il formato (11 cifre numeriche) resta competenza della
 * regola `regex:/^[0-9]{11}$/`, così non si generano due errori sullo stesso campo.
 * Indipendente dall'esistenza all'Anagrafe tributaria.
 */
class PartitaIvaValida implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $piva = (string) $value;

        // Formato non di competenza di questa Rule: lo gestisce la regola regex.
        if (!preg_match('/^\d{11}$/', $piva)) {
            return;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $digit = (int) $piva[$i];
            if ($i % 2 === 0) {
                $sum += $digit;
            } else {
                $double = $digit * 2;
                $sum += ($double > 9) ? $double - 9 : $double;
            }
        }

        $checksum = (10 - ($sum % 10)) % 10;

        if ($checksum !== (int) $piva[10]) {
            $fail('La partita IVA non è valida (checksum errato).');
        }
    }
}
