<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\LogRecord;

/**
 * PiiSanitizer — Monolog Processor
 *
 * Redacta automaticamente dati personali (PII) dai log:
 * patient_ref, indirizzi email, numeri di telefono italiani, nomi propri.
 *
 * KILL SWITCH:
 *   Cambia $enabled = false per disabilitare istantaneamente senza cache clear.
 *   Oppure rimuovi 'tap' => [...] dai canali in config/logging.php.
 *
 * TEST IN TINKER:
 *   Vedi il blocco in fondo al file.
 */
class PiiSanitizer
{
    /**
     * Flag per disabilitare la sanitizzazione in emergenza.
     * Cambia in FALSE per bypass immediato — nessun cache clear necessario.
     */
    private bool $enabled = true;

    /**
     * Pattern regex per rilevare PII nel testo dei log.
     * Chiave = nome tipo, valore = pattern regex.
     */
    private array $patterns = [
        // Riferimento paziente: patient_ref seguito da separatore e codice alfanumerico
        'patient_ref' => '/patient_ref[\'":\s=]+([A-Z0-9\-]{3,30})/i',

        // Indirizzi email
        'email' => '/\b[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}\b/',

        // Telefoni italiani: +39 o 0039 o prefisso 3xx, con spazi/trattini opzionali
        'phone' => '/(?:\+39|0039)?[\s\-]?(?:3[0-9]{2}[\s\-]?[0-9]{6,7}|0[0-9]{1,4}[\s\-]?[0-9]{4,8})/',

        // Nomi propri italiani (Cognome Nome — due parole, iniziale maiuscola)
        // Limitato a contesti noti per ridurre falsi positivi
        'names_in_context' => '/(?:dentist(?:_name)?|lab_operator|cad_operator|qc_verifier|nome|cognome)[\'"\s:=]+([A-ZÀ-Ü][a-zà-ü]+(?:\s[A-ZÀ-Ü][a-zà-ü]+)+)/u',
    ];

    /**
     * Testo sostitutivo per ogni tipo di PII.
     */
    private array $replacements = [
        'patient_ref'       => 'patient_ref: [REDACTED]',
        'email'             => '[EMAIL-REDACTED]',
        'phone'             => '[PHONE-REDACTED]',
        'names_in_context'  => '[NAME-REDACTED]',
    ];

    public function __invoke(mixed $record): mixed
    {
        if ($record instanceof Logger) {
            $record->pushProcessor($this);
            return $record;
        }

        if (! $this->enabled) {
            return $record;
        }

        $message = $this->sanitizeString($record->message);
        $context = $this->sanitizeArray($record->context);
        $extra   = $this->sanitizeArray($record->extra);

        return $record->with(
            message: $message,
            context: $context,
            extra: $extra,
        );
    }

    private function sanitizeString(string $text): string
    {
        foreach ($this->patterns as $type => $pattern) {
            $text = preg_replace($pattern, $this->replacements[$type], $text) ?? $text;
        }
        return $text;
    }

    /**
     * Sanitizza ricorsivamente un array (context/extra Monolog).
     * Redacta stringhe, scende negli array annidati.
     * Lascia inalterati int, bool, null, oggetti non stringificabili.
     */
    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            }
        }
        return $data;
    }
}

/*
 * ================================================================
 * TEST CASES — Verifica in php artisan tinker
 * ================================================================
 *
 * use App\Logging\PiiSanitizer;
 * use Monolog\Level;
 * use Monolog\LogRecord;
 *
 * $s = new PiiSanitizer();
 *
 * // ---- Test 1: patient_ref nel messaggio ----
 * $r = new LogRecord(now(), 'test', Level::Info,
 *     'Ordine per patient_ref: ROSSI2024X da dentist@studio.com');
 * $out = $s($r);
 * assert(str_contains($out->message, '[REDACTED]'));
 * assert(str_contains($out->message, '[EMAIL-REDACTED]'));
 * echo "Test 1 OK: " . $out->message . "\n";
 *
 * // ---- Test 2: dati nel context array ----
 * $r = new LogRecord(now(), 'test', Level::Warning,
 *     'GDPR DELETE REQUEST',
 *     context: ['email' => 'dentist@example.com', 'patient' => 'PAZIENTE001']);
 * $out = $s($r);
 * assert($out->context['email'] === '[EMAIL-REDACTED]');
 * echo "Test 2 OK: " . json_encode($out->context) . "\n";
 *
 * // ---- Test 3: array annidato ----
 * $r = new LogRecord(now(), 'test', Level::Info, 'nested test',
 *     context: ['order' => ['patient_ref: MARIO123', 'phone' => '+39 06 00000000']]);
 * $out = $s($r);
 * echo "Test 3 OK: " . json_encode($out->context) . "\n";
 *
 * // ---- Test 4: Kill switch ----
 * $prop = new ReflectionProperty($s, 'enabled');
 * $prop->setAccessible(true);
 * $prop->setValue($s, false);
 * $r = new LogRecord(now(), 'test', Level::Info, 'email: test@example.com');
 * $out = $s($r);
 * assert(str_contains($out->message, 'test@example.com')); // NON redactato
 * echo "Test 4 OK: kill switch funziona\n";
 *
 * ================================================================
 * ROLLBACK EMERGENZA
 * ================================================================
 *
 * Opzione A — Disabilita in-code (effetto immediato, no cache clear):
 *   Modifica riga: private bool $enabled = false;
 *
 * Opzione B — Rimuovi dal logging config (30 secondi):
 *   In config/logging.php, rimuovi 'tap' => [App\Logging\PiiSanitizer::class]
 *   poi: php artisan config:cache
 *
 * Opzione C — Ripristina backup config:
 *   cp config/logging.php.backup config/logging.php
 *   php artisan config:cache
 *
 * ================================================================
 */
