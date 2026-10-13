<?php

namespace App\Support;

/**
 * Testi legali in resources/views/legal/pro/text/*.txt.
 * Unica sede del percorso e della risoluzione dei segnaposto [[TOKEN]].
 * L'hash di accettazione (LegalAcceptance::hashLegalFile) si calcola sul file
 * grezzo, con i segnaposto: vincola il modello del documento, non la sua resa.
 */
final class LegalText
{
    public static function path(string $filename): string
    {
        if ($filename === '' || str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, '..')) {
            throw new \InvalidArgumentException("Nome di file legale non valido: {$filename}");
        }
        return resource_path('views/legal/pro/text/' . $filename);
    }

    /** @return array<string, string> */
    public static function tokens(): array
    {
        return [
            '[[LAB_NAME]]'    => (string) config('lab.manufacturer.name'),
            '[[LAB_ADDRESS]]' => (string) config('lab.manufacturer.address'),
            '[[LAB_ZIP]]'     => (string) config('lab.manufacturer.zip'),
            '[[LAB_CITY]]'    => (string) config('lab.manufacturer.city'),
            '[[LAB_VAT]]'     => (string) config('lab.manufacturer.vat'),
            '[[LAB_EMAIL]]'   => (string) config('lab.manufacturer.email'),
            '[[LAB_MDR_REG]]' => (string) config('lab.manufacturer.mdr_registration'),
            '[[LAB_COURT]]'   => (string) config('lab.manufacturer.court'),
            '[[APP_NAME]]'    => (string) config('app.name'),
        ];
    }

    /** Legge il file e risolve i segnaposto. Lancia se il file manca o se resta un segnaposto. */
    public static function render(string $filename): string
    {
        $path = self::path($filename);
        if (! is_file($path)) {
            throw new \RuntimeException("File legale mancante: {$filename}");
        }
        $text = strtr((string) file_get_contents($path), self::tokens());
        if (preg_match('/\[\[[A-Z_]+\]\]/', $text, $m)) {
            throw new \RuntimeException("Segnaposto non risolto {$m[0]} in {$filename}");
        }
        return $text;
    }
}
