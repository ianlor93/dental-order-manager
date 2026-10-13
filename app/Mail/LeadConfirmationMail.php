<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LeadConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
    ) {}

    public function build(): self
    {
        $logoPath = storage_path('app/branding/logo-email.png');

        return $this->subject('Grazie per esserti pre-registrato — ' . config('app.name'))
            ->replyTo(config('mail.reply_to.address'), config('mail.reply_to.name'))
            ->view('emails.master')
            ->with([
                'title'         => "Ciao {$this->lead->nome}, grazie!",
                'paragraphs'    => [
                    "Abbiamo ricevuto la tua pre-registrazione e ci fa davvero piacere l'interesse che hai mostrato verso " . config('lab.manufacturer.name') . ".",
                    "Stiamo lavorando a qualcosa di nuovo per odontotecnici, studi odontoiatrici e fornitori di servizi per l'odontoiatria. Sarai tra i primi a saperlo non appena tutto sarà pronto.",
                ],
                'infobox'       => [
                    'text' => "Ti ricontatteremo entro 24 ore all'indirizzo {$this->lead->email} per condividere con te le prime novità in anteprima.",
                ],
                'notes'         => [
                    "Se hai domande nel frattempo, scrivici pure a " . config('lab.manufacturer.email') . ": saremo felici di risponderti.",
                    "A presto,\nIl team di " . config('lab.manufacturer.name'),
                ],
                'footerVariant' => 'external',
                'footerReason'  => 'Questa email è stata inviata perché hai compilato il form di pre-registrazione su ' . config('lab.landing_url') . '.',
                'logoPath'      => file_exists($logoPath) ? $logoPath : null,
            ]);
    }
}
