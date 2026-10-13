<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Mail\LeadConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadController extends Controller
{
    private const SOURCE = 'bfd-landing';

    /** Origini ammesse per la landing. Sorgente unica: config lab.landing_origins. */
    private function allowedOrigins(): array
    {
        return (array) config('lab.landing_origins');
    }

    /** Limite massimo del body JSON (bytes). Stop a payload abnormi. */
    private const MAX_BODY_BYTES = 8192;

    /** Tempo minimo (ms) tra apertura form e submit. Anti-bot. */
    private const MIN_FORM_TIME_MS = 3000;

    /** Tempo massimo (ms) — oltre, il timer è probabilmente falso/replay. */
    private const MAX_FORM_TIME_MS = 3600000; // 1 ora

    /**
     * Pattern pericolosi nei campi testo libero (nome, studio).
     * Difesa in profondità: Eloquent + Blade autoescape + strip_tags già coprono
     * gli scenari reali (SQLi, XSS), questi blocchi fermano alla porta i bot
     * che tentano signature note prima ancora di toccare il DB.
     */
    private const DANGEROUS_PATTERNS = [
        '/<[^>]+>/',                                                // tag HTML
        '/[\x00-\x1F\x7F]/',                                        // control chars (incluso \r\n, \0)
        '/(javascript:|data:|vbscript:|file:\/\/|php:\/\/)/i',      // schemi pericolosi
        '/(\${|\{\{|<%|%>)/',                                       // template injection (PHP/Blade/JSP/ASP)
        '/(`|\$\(|\b(eval|exec|system|passthru|shell_exec)\s*\()/', // command/eval injection
    ];

    public function store(Request $request): JsonResponse
    {
        $origin = $request->header('Origin', '');
        if ($origin !== '' && !in_array($origin, $this->allowedOrigins(), true)) {
            return response()->json(['success' => false], 403);
        }

        // Body size guard — stop a payload abnormi prima di parsarli
        $rawBody = $request->getContent();
        if (strlen($rawBody) > self::MAX_BODY_BYTES) {
            return response()->json(['success' => false], 413)
                             ->withHeaders($this->corsHeaders($request));
        }

        // Honeypot: campo nascosto che gli umani non vedono.
        // I bot che scrappano i form e li ricompilano lo riempiono.
        // Risposta 200 fake (silent reject) — non diamo feedback all'attaccante.
        if (filled($request->input('website'))) {
            logger()->info('Lead honeypot triggered', [
                'ip'    => $request->ip(),
                'value' => substr((string) $request->input('website'), 0, 50),
            ]);
            return response()->json(['success' => true, 'already' => false])
                             ->withHeaders($this->corsHeaders($request));
        }

        // Min submit time — i bot scriptati submittano in <100ms
        $formTime = (int) $request->input('_ft', 0);
        if ($formTime < self::MIN_FORM_TIME_MS || $formTime > self::MAX_FORM_TIME_MS) {
            logger()->info('Lead form-time rejected', [
                'ip'  => $request->ip(),
                '_ft' => $formTime,
            ]);
            return response()->json(['success' => true, 'already' => false])
                             ->withHeaders($this->corsHeaders($request));
        }

        // Sanitize prima della validazione
        $request->merge([
            'nome'          => strip_tags(trim((string) $request->input('nome', ''))),
            'email'         => mb_strtolower(trim((string) $request->input('email', ''))),
            'studio'        => strip_tags(trim((string) $request->input('studio', ''))),
            'piva'          => preg_replace('/\s+/', '', strtoupper((string) $request->input('piva', ''))),
            'tipo_attivita' => trim((string) $request->input('tipo_attivita', '')),
            'telefono'      => trim((string) $request->input('telefono', '')),
        ]);

        $dangerousRules = array_map(fn ($p) => 'not_regex:' . $p, self::DANGEROUS_PATTERNS);

        $validator = Validator::make($request->all(), [
            'nome' => array_merge([
                'required', 'string', 'min:2', 'max:80',
                'regex:/^[\pL\s\'\-\.]+$/u',
            ], $dangerousRules),

            'email' => [
                'required', 'string', 'max:150',
                'email:rfc,dns',
                'not_regex:/[\x00-\x1F\x7F<>"\\\\]/',  // no control char, no <>"\
                fn ($attr, $value, $fail) => $this->validateEmailRealism($attr, $value, $fail),
            ],

            'studio' => array_merge([
                'required', 'string', 'min:2', 'max:150',
                'regex:/^[\pL\pN\s\'\-\.\,\&\(\)\/]+$/u',
            ], $dangerousRules),

            'piva' => ['required', 'string', 'regex:/^[0-9]{11}$/'],

            'tipo_attivita' => [
                'required',
                Rule::in(['studio_odontoiatrico', 'laboratorio_odontotecnico', 'fornitore_servizi']),
            ],

            'telefono' => [
                'required', 'string', 'max:30',
                'regex:/^\+?[\d\s\-\.\(\)]{5,30}$/',
                'not_regex:/[\x00-\x1F\x7F]/',
            ],
        ], [
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
        ]);

        // Checksum P.IVA italiana (algoritmo Luhn modificato CEE)
        $validator->after(function ($v) use ($request) {
            $piva = (string) $request->input('piva', '');
            if (preg_match('/^[0-9]{11}$/', $piva) && !$this->isValidItalianVat($piva)) {
                $v->errors()->add('piva', 'La partita IVA non è valida (checksum errato).');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422)->withHeaders($this->corsHeaders($request));
        }

        // Lock pessimistico per prevenire doppia creazione su stessa email
        [$lead, $already] = DB::transaction(function () use ($request) {
            $existing = Lead::where('email', $request->email)->lockForUpdate()->first();

            if ($existing) {
                return [$existing, true];
            }

            $lead = Lead::create([
                'nome'          => $request->nome,
                'email'         => $request->email,
                'studio'        => $request->studio,
                'piva'          => $request->piva,
                'tipo_attivita' => $request->tipo_attivita,
                'telefono'      => $request->telefono,
                'source'        => self::SOURCE,
            ]);

            return [$lead, false];
        });

        if (!$already) {
            try {
                Mail::to($lead->email)->send(new LeadConfirmationMail($lead));
            } catch (\Throwable $e) {
                logger()->warning('LeadConfirmationMail non inviata', [
                    'email' => $lead->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'already' => $already,
        ])->withHeaders($this->corsHeaders($request));
    }

    public function preflight(Request $request): JsonResponse
    {
        $origin = $request->header('Origin', '');
        if (!in_array($origin, $this->allowedOrigins(), true)) {
            return response()->json(null, 403);
        }

        return response()->json(null, 204)->withHeaders([
            'Access-Control-Allow-Origin'  => $origin,
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Access-Control-Max-Age'       => '86400',
        ]);
    }

    /**
     * Verifica realismo dell'indirizzo email — blocca disposable e pattern noti
     * di indirizzi spazzatura usati da bot.
     */
    private function validateEmailRealism(string $attribute, mixed $value, callable $fail): void
    {
        if (!is_string($value) || !str_contains($value, '@')) {
            return; // gestito dalla regola email:rfc
        }

        [$mailbox, $domain] = explode('@', $value, 2);
        $mailbox = strtolower($mailbox);
        $domain  = strtolower($domain);

        // 1. TLD riservati / di test (RFC 2606 + RFC 6761)
        foreach (['.test', '.example', '.invalid', '.localhost', '.local'] as $tld) {
            if (str_ends_with($domain, $tld)) {
                $fail('Inserisci un indirizzo email reale.');
                return;
            }
        }

        // 2. Domini di esempio
        if (in_array($domain, ['example.com', 'example.org', 'example.net', 'test.com'], true)) {
            $fail('Inserisci un indirizzo email reale.');
            return;
        }

        // 3. Mailbox spazzatura
        $junkPrefixes = ['test', 'prova', 'asdf', 'aaa', 'xxx', 'qwerty', 'abcd', 'noreply', 'no-reply', 'donotreply'];
        foreach ($junkPrefixes as $prefix) {
            if ($mailbox === $prefix || str_starts_with($mailbox, $prefix . '.') || str_starts_with($mailbox, $prefix . '_')) {
                $fail('Inserisci un indirizzo email reale.');
                return;
            }
        }

        // 4. Sequenze di obfuscation
        if (str_contains($value, '..') || str_contains($value, '--') || str_contains($value, '__')) {
            $fail('L\'email contiene sequenze non consentite.');
            return;
        }

        // 5. Disposable / throwaway domain blocklist
        $disposable = config('disposable_email_domains', []);
        if (in_array($domain, $disposable, true)) {
            $fail('Gli indirizzi email temporanei non sono accettati.');
            return;
        }
    }

    /**
     * Algoritmo di controllo della Partita IVA italiana (DPR 633/72 art. 35).
     * Verifica checksum sulle 11 cifre. Indipendente dall'esistenza all'Anagrafe.
     */
    private function isValidItalianVat(string $piva): bool
    {
        if (!preg_match('/^\d{11}$/', $piva)) {
            return false;
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

        return $checksum === (int) $piva[10];
    }

    private function corsHeaders(Request $request): array
    {
        $origin = $request->header('Origin', '');
        if (!in_array($origin, $this->allowedOrigins(), true)) {
            return [];
        }

        return [
            'Access-Control-Allow-Origin'   => $origin,
            'Access-Control-Allow-Methods'  => 'POST, OPTIONS',
            'Access-Control-Allow-Headers'  => 'Content-Type, Accept',
            'X-Content-Type-Options'        => 'nosniff',
        ];
    }
}
