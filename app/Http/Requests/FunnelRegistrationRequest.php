<?php

namespace App\Http\Requests;

use App\Rules\PartitaIvaValida;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * FormRequest del funnel di registrazione (POST /api/register).
 *
 * Riusa — senza modificarlo — lo stesso scudo anti-abuso di LeadController::store()
 * (CORS origin-whitelist, body-size guard, honeypot, form-timing, sanitizzazione,
 * email:rfc,dns + realismo, checksum P.IVA via App\Rules\PartitaIvaValida).
 * La logica di scrittura/promote del lead vive nel RegistrationController.
 *
 * Anti-enumerazione: honeypot/timing restituiscono la STESSA risposta generica di
 * successo del flusso reale (silent reject), così un bot non distingue i casi.
 */
class FunnelRegistrationRequest extends FormRequest
{
    /**
     * Origini ammesse per il funnel. Include l'origine dell'app (il funnel è servito
     * da OM) oltre alle landing pubbliche configurate.
     * Single source of truth: il controller legge questo metodo per i CORS header
     * e per il preflight.
     */
    public static function allowedOrigins(): array
    {
        return array_values(array_unique(array_filter(array_merge(
            (array) config('lab.landing_origins'),
            [rtrim((string) config('app.url'), '/')]
        ))));
    }

    /** Risposta generica identica per ogni ramo (anti-enumerazione). */
    public const GENERIC_MESSAGE = 'Richiesta ricevuta. Ti ricontatteremo al più presto.';

    private const MAX_BODY_BYTES = 8192;
    private const MIN_FORM_TIME_MS = 3000;
    private const MAX_FORM_TIME_MS = 3600000; // 1 ora

    /** Pattern pericolosi nei campi testo libero (vedi LeadController::store). */
    private const DANGEROUS_PATTERNS = [
        '/<[^>]+>/',
        '/[\x00-\x1F\x7F]/',
        '/(javascript:|data:|vbscript:|file:\/\/|php:\/\/)/i',
        '/(\${|\{\{|<%|%>)/',
        '/(`|\$\(|\b(eval|exec|system|passthru|shell_exec)\s*\()/',
    ];

    /**
     * Endpoint pubblico: nessuna autorizzazione utente. Lo scudo (CORS incluso) è
     * applicato in prepareForValidation, per preservare l'ordine esatto di store().
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Scudo anti-abuso, stesso ordine di LeadController::store():
     * CORS → body-size → honeypot → form-timing → sanitizzazione.
     * I rami che devono essere "silenziosi" lanciano la risposta direttamente.
     */
    protected function prepareForValidation(): void
    {
        // 1. CORS origin-whitelist — 403 se Origin presente ma non ammessa.
        $origin = $this->header('Origin', '');
        if ($origin !== '' && !in_array($origin, self::allowedOrigins(), true)) {
            throw new HttpResponseException(response()->json(['success' => false], 403));
        }

        // 2. Body size guard — stop a payload abnormi prima di parsarli.
        if (strlen((string) $this->getContent()) > self::MAX_BODY_BYTES) {
            throw new HttpResponseException(
                response()->json(['success' => false], 413)->withHeaders($this->corsHeaders())
            );
        }

        // 3. Honeypot — campo nascosto: se valorizzato è un bot. Silent reject (200 fake).
        if (filled($this->input('website'))) {
            logger()->info('Funnel honeypot triggered', [
                'ip'    => $this->ip(),
                'value' => substr((string) $this->input('website'), 0, 50),
            ]);
            throw new HttpResponseException($this->genericSuccessResponse());
        }

        // 4. Form-timing — troppo veloce o timer falso/replay. Silent reject (200 fake).
        $formTime = (int) $this->input('_ft', 0);
        if ($formTime < self::MIN_FORM_TIME_MS || $formTime > self::MAX_FORM_TIME_MS) {
            logger()->info('Funnel form-time rejected', [
                'ip'  => $this->ip(),
                '_ft' => $formTime,
            ]);
            throw new HttpResponseException($this->genericSuccessResponse());
        }

        // 5. Sanitizzazione pre-validazione (identica a store()).
        $this->merge([
            'nome'          => strip_tags(trim((string) $this->input('nome', ''))),
            'email'         => mb_strtolower(trim((string) $this->input('email', ''))),
            'studio'        => strip_tags(trim((string) $this->input('studio', ''))),
            'piva'          => preg_replace('/\s+/', '', strtoupper((string) $this->input('piva', ''))),
            'tipo_attivita' => trim((string) $this->input('tipo_attivita', '')),
            'telefono'      => trim((string) $this->input('telefono', '')),
        ]);
    }

    public function rules(): array
    {
        $dangerousRules = array_map(fn ($p) => 'not_regex:' . $p, self::DANGEROUS_PATTERNS);

        return [
            'nome' => array_merge([
                'required', 'string', 'min:2', 'max:80',
                'regex:/^[\pL\s\'\-\.]+$/u',
            ], $dangerousRules),

            'email' => [
                'required', 'string', 'max:150',
                'email:rfc,dns',
                'not_regex:/[\x00-\x1F\x7F<>"\\\\]/',
                fn ($attr, $value, $fail) => $this->validateEmailRealism($attr, $value, $fail),
            ],

            'studio' => array_merge([
                'required', 'string', 'min:2', 'max:150',
                'regex:/^[\pL\pN\s\'\-\.\,\&\(\)\/]+$/u',
            ], $dangerousRules),

            // Formato dalla regex; checksum dalla Rule (skippa se formato errato → un solo errore).
            'piva' => ['required', 'string', 'regex:/^[0-9]{11}$/', new PartitaIvaValida()],

            'tipo_attivita' => [
                'required',
                Rule::in(['studio_odontoiatrico', 'laboratorio_odontotecnico', 'fornitore_servizi']),
            ],

            'telefono' => [
                'required', 'string', 'max:30',
                'regex:/^\+?[\d\s\-\.\(\)]{5,30}$/',
                'not_regex:/[\x00-\x1F\x7F]/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'          => 'Il nome è obbligatorio.',
            'nome.min'               => 'Il nome deve contenere almeno 2 caratteri.',
            'nome.max'               => 'Il nome è troppo lungo.',
            'nome.regex'             => 'Il nome contiene caratteri non consentiti.',
            'nome.not_regex'         => 'Il nome contiene caratteri non consentiti.',
            'email.required'         => 'L\'email è obbligatoria.',
            'email.email'            => 'L\'indirizzo email non è valido o il dominio non esiste.',
            'email.not_regex'        => 'L\'email contiene caratteri non consentiti.',
            'studio.required'        => 'La ragione sociale è obbligatoria.',
            'studio.min'             => 'La ragione sociale deve contenere almeno 2 caratteri.',
            'studio.regex'           => 'La ragione sociale contiene caratteri non consentiti.',
            'studio.not_regex'       => 'La ragione sociale contiene caratteri non consentiti.',
            'piva.required'          => 'La partita IVA è obbligatoria.',
            'piva.regex'             => 'Inserisci una partita IVA italiana valida (11 cifre).',
            'tipo_attivita.required' => 'Seleziona il tipo di attività.',
            'tipo_attivita.in'       => 'Seleziona un tipo di attività valido.',
            'telefono.required'      => 'Il telefono è obbligatorio.',
            'telefono.regex'         => 'Inserisci un numero di telefono valido.',
            'telefono.not_regex'     => 'Il telefono contiene caratteri non consentiti.',
        ];
    }

    /**
     * Errori di validazione nello stesso formato di store(): { success:false, errors }
     * con status 422 e CORS header (invece del default {message, errors} di Laravel).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422)->withHeaders($this->corsHeaders())
        );
    }

    /** Risposta generica di successo — identica per flusso reale e silent reject. */
    public function genericSuccessResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => self::GENERIC_MESSAGE,
        ])->withHeaders($this->corsHeaders());
    }

    public function corsHeaders(): array
    {
        $origin = $this->header('Origin', '');
        if (!in_array($origin, self::allowedOrigins(), true)) {
            return [];
        }

        return [
            'Access-Control-Allow-Origin'  => $origin,
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'X-Content-Type-Options'       => 'nosniff',
        ];
    }

    /**
     * Realismo dell'email — blocca TLD/domini di test, mailbox spazzatura, obfuscation,
     * domini disposable. Copia fedele di LeadController::validateEmailRealism().
     */
    private function validateEmailRealism(string $attribute, mixed $value, callable $fail): void
    {
        if (!is_string($value) || !str_contains($value, '@')) {
            return; // gestito dalla regola email:rfc
        }

        [$mailbox, $domain] = explode('@', $value, 2);
        $mailbox = strtolower($mailbox);
        $domain  = strtolower($domain);

        foreach (['.test', '.example', '.invalid', '.localhost', '.local'] as $tld) {
            if (str_ends_with($domain, $tld)) {
                $fail('Inserisci un indirizzo email reale.');
                return;
            }
        }

        if (in_array($domain, ['example.com', 'example.org', 'example.net', 'test.com'], true)) {
            $fail('Inserisci un indirizzo email reale.');
            return;
        }

        $junkPrefixes = ['test', 'prova', 'asdf', 'aaa', 'xxx', 'qwerty', 'abcd', 'noreply', 'no-reply', 'donotreply'];
        foreach ($junkPrefixes as $prefix) {
            if ($mailbox === $prefix || str_starts_with($mailbox, $prefix . '.') || str_starts_with($mailbox, $prefix . '_')) {
                $fail('Inserisci un indirizzo email reale.');
                return;
            }
        }

        if (str_contains($value, '..') || str_contains($value, '--') || str_contains($value, '__')) {
            $fail('L\'email contiene sequenze non consentite.');
            return;
        }

        $disposable = config('disposable_email_domains', []);
        if (in_array($domain, $disposable, true)) {
            $fail('Gli indirizzi email temporanei non sono accettati.');
            return;
        }
    }
}
