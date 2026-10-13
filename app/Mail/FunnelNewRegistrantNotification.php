<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Funnel Blocco 1 — avviso interno "nuovo iscritto verificato" (a segreteria@).
 *
 * Inviata su `complete` (email verificata + password impostata): il lead è pronto per
 * il vaglio nel pannello Amministrazione. Usa il MASTER TEMPLATE unificato con il
 * blocco `details[]` (etichetta·valore) — risolve P2 (niente HTML in stringa).
 * Invio SINCRONO (->send()): in prod nessun worker drena la coda.
 */
class FunnelNewRegistrantNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
    ) {
    }

    public function build(): self
    {
        $logoPath = storage_path('app/branding/logo-email.png');

        return $this->subject('Nuovo iscritto verificato — ' . ($this->lead->studio ?: $this->lead->nome))
            ->replyTo(config('mail.reply_to.address'), config('mail.reply_to.name'))
            ->view('emails.master')
            ->with([
                'title'         => 'Nuovo iscritto verificato',
                'paragraphs'    => [
                    "Un nuovo iscritto ha confermato l'email e impostato la password. È pronto per il vaglio nel pannello Amministrazione.",
                ],
                'details'       => [
                    ['label' => 'Ragione sociale',  'value' => $this->lead->studio ?: '—'],
                    ['label' => 'Referente',        'value' => $this->lead->nome ?: '—'],
                    ['label' => 'Email',            'value' => $this->lead->email],
                    ['label' => 'P.IVA',            'value' => $this->lead->piva ?: '—'],
                    ['label' => 'Tipo attività',    'value' => $this->lead->tipo_attivita ?: '—'],
                    ['label' => 'Telefono',         'value' => $this->lead->telefono ?: '—'],
                    ['label' => 'Utente Nextcloud', 'value' => $this->lead->created_user_uid ?: '—'],
                ],
                'footerVariant' => 'internal',
                'footerReason'  => 'Notifica interna automatica del funnel di registrazione di ' . config('app.name') . '.',
                'logoPath'      => file_exists($logoPath) ? $logoPath : null,
            ]);
    }
}
