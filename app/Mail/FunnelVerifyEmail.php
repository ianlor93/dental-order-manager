<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Funnel Blocco 1 — mail di verifica email + set-password (al cliente).
 *
 * Un solo CTA verso la pagina NC `enamel_setpassword/verify?t={token}`. Nel link c'è
 * SOLO il token (nessun email/uid/PII). Usa il MASTER TEMPLATE unificato (emails.master).
 *
 * Invio SINCRONO (->send()): in prod nessun worker drena la coda → niente queue().
 * Vantaggio collaterale: il plaintext del token non transita mai nella tabella `jobs`.
 */
class FunnelVerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public string $verifyToken,
    ) {
    }

    public function build(): self
    {
        $logoPath  = storage_path('app/branding/logo-email.png');
        $verifyUrl = rtrim((string) config('services.nextcloud.url'), '/') . '/apps/enamel_setpassword/verify?t='
                   . rawurlencode($this->verifyToken);

        return $this->subject('Conferma la tua email e imposta la password — ' . config('app.name'))
            ->replyTo(config('mail.reply_to.address'), config('mail.reply_to.name'))
            ->view('emails.master')
            ->with([
                'title'         => "Ciao {$this->lead->nome}, confermiamo la tua email",
                'paragraphs'    => [
                    "Per completare la registrazione alla piattaforma, conferma il tuo indirizzo email e imposta la password del tuo account con un clic sul pulsante qui sotto.",
                ],
                'cta'           => ['label' => 'Conferma e imposta la password', 'url' => $verifyUrl, 'variant' => 'primary'],
                'notes'         => [
                    "Il link è valido per 48 ore e può essere usato una sola volta. Dopo aver impostato la password, il tuo account sarà attivato a seguito della verifica da parte del nostro team.",
                    "Se il pulsante non funziona, copia e incolla questo indirizzo nel browser:\n{$verifyUrl}",
                    "Se non hai richiesto tu la registrazione, ignora pure questa email.",
                ],
                'footerVariant' => 'external',
                'footerReason'  => 'Hai ricevuto questa email perché è stata avviata una registrazione su ' . config('app.name') . ' con questo indirizzo.',
                'logoPath'      => file_exists($logoPath) ? $logoPath : null,
            ]);
    }
}
