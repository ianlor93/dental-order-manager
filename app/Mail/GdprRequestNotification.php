<?php

namespace App\Mail;

use App\Models\GdprRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica al DPO di una nuova richiesta GDPR (Art. 15 accesso, Art. 16 rettifica,
 * Art. 21 opposizione). Inviata in coda per non rallentare la risposta HTTP.
 *
 * Usa il MASTER TEMPLATE unificato (emails.master) con blocco `details[]` — sostituisce
 * il vecchio template fuori brand (Arial/navy/rosso, senza logo).
 *
 * Destinatario: config('lab.dpo.email') con fallback su config('lab.manufacturer.email').
 * Scadenza risposta Art. 12: 30 giorni dalla ricezione (prorogabili a 90 per complessità).
 */
class GdprRequestNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly GdprRequest $gdprRequest,
        public readonly User $interested
    ) {}

    public function build(): self
    {
        $logoPath = storage_path('app/branding/logo-email.png');

        $label = GdprRequest::$rightLabels[$this->gdprRequest->right_article]
            ?? $this->gdprRequest->right_article;

        $details = [
            ['label' => 'Riferimento',            'value' => '#' . $this->gdprRequest->id],
            ['label' => 'Interessato',            'value' => $this->interested->displayname . ' (' . $this->interested->uid . ')'],
            ['label' => 'Email',                  'value' => $this->interested->email ?? '(non disponibile)'],
            ['label' => 'Categoria dati',         'value' => $this->gdprRequest->object_category ?? '—'],
            ['label' => 'Ricevuta il',            'value' => $this->gdprRequest->received_at->format('d/m/Y H:i')],
            ['label' => 'Scadenza Art. 12 GDPR',  'value' => $this->gdprRequest->deadline_at->format('d/m/Y')],
            ['label' => 'IP richiedente',         'value' => $this->gdprRequest->ip_address ?? '—'],
        ];

        $infobox = $this->gdprRequest->motivation
            ? ['label' => 'Motivazione / Dettagli', 'text' => $this->gdprRequest->motivation]
            : null;

        return $this
            ->subject('[DPO] Nuova richiesta GDPR — ' . $label)
            ->replyTo(config('mail.reply_to.address'), config('mail.reply_to.name'))
            ->view('emails.master')
            ->with([
                'title'         => 'Nuova richiesta GDPR — ' . $label,
                'paragraphs'    => [
                    "È stata registrata una nuova richiesta per il diritto: {$label}.",
                ],
                'details'       => $details,
                'infobox'       => $infobox,
                'notes'         => [
                    "Accedi al backoffice " . config('app.name') . " per gestire la richiesta, registrare la decisione e inviare risposta all'interessato entro la scadenza indicata.",
                ],
                'footerVariant' => 'internal',
                'footerReason'  => 'Ricevi questa email perché sei tra i destinatari operativi (DPO / PRRC) di ' . config('lab.manufacturer.name') . '.',
                'logoPath'      => file_exists($logoPath) ? $logoPath : null,
            ]);
    }
}
