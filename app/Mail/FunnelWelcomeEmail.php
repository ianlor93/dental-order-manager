<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Funnel — mail di BENVENUTO "account attivo" (al cliente approvato).
 *
 * Inviata quando l'admin approva l'iscritto e il runner T4b abilita l'utente NC
 * (transizione `activated_at` null→now in ProvisioningController::activated()).
 * Sostituisce il reset stock NC: l'utente ha GIÀ impostato la password in fase di
 * verifica (Blocco 1), quindi qui è solo un benvenuto con CTA al login.
 *
 * Usa il MASTER TEMPLATE unificato (emails.master). Invio SINCRONO (->send()),
 * best-effort: un errore SMTP non deve far fallire l'ack di attivazione.
 *
 * NB: copy redatta alla costruzione del master (prima istanza reale) — rivedibile
 * nel pillar copy.
 */
class FunnelWelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
    ) {
    }

    public function build(): self
    {
        $logoPath = storage_path('app/branding/logo-email.png');

        $nome  = trim((string) ($this->lead->nome ?? ''));
        $title = $nome !== '' ? "Benvenuto in " . config('app.name') . ", {$nome}!" : 'Benvenuto in ' . config('app.name') . '!';

        return $this->subject('Il tuo account ' . config('app.name') . ' è attivo')
            ->replyTo(config('mail.reply_to.address'), config('mail.reply_to.name'))
            ->view('emails.master')
            ->with([
                'title'         => $title,
                'paragraphs'    => [
                    "Il tuo account è stato attivato: da ora puoi accedere alla piattaforma con la tua email e la password che hai impostato in fase di registrazione.",
                    "Dal portale gestisci gli ordini e i dispositivi del tuo studio in un unico posto.",
                ],
                'cta'           => ['label' => 'Accedi alla piattaforma', 'url' => config('services.nextcloud.url')],
                'notes'         => [
                    "Hai dimenticato la password? Usa «Hai dimenticato la password?» nella pagina di accesso. Per qualsiasi necessità scrivici a " . config('lab.manufacturer.email') . ".",
                ],
                'footerVariant' => 'external',
                'footerReason'  => 'Ricevi questa email perché il tuo account ' . config('app.name') . ' è stato attivato.',
                'logoPath'      => file_exists($logoPath) ? $logoPath : null,
            ]);
    }
}
