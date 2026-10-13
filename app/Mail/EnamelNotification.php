<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable generica — usa il MASTER TEMPLATE unificato (emails.master).
 *
 * Accetta lo stesso array storico ({subject,title,message,actionUrl,actionText}) e lo
 * mappa sul contratto del master. Il `message` viene normalizzato in PARAGRAFI:
 * qualsiasi HTML legacy (<strong>/<br>/<code>) passato dai chiamanti interni (MDR/GDPR)
 * viene ripulito → niente più tag grezzi mostrati al destinatario (risolve P2).
 *
 * Chiavi opzionali nuove (per i chiamanti che vogliono il contratto strutturato):
 *   'paragraphs' (string[]), 'details' ([['label','value']]), 'infobox' (['label','text']),
 *   'meta', 'notes' (string[]), 'audience' ('internal'|'external'), 'footerReason', 'ctaVariant'.
 */
class EnamelNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $data     = $this->data;
        $logoPath = storage_path('app/branding/logo-email.png');

        // Corpo: se il chiamante passa già 'paragraphs', usalo; altrimenti normalizza 'message'.
        $paragraphs = $data['paragraphs'] ?? self::messageToParagraphs($data['message'] ?? '');

        // CTA dal vecchio actionUrl/actionText.
        $cta = null;
        if (!empty($data['actionUrl'])) {
            $cta = [
                'label'   => $data['actionText'] ?? 'Accedi al Portale',
                'url'     => $data['actionUrl'],
                'variant' => $data['ctaVariant'] ?? 'primary',
            ];
        }

        // Pubblico: 'external' (cliente) di default; le notifiche interne passano 'internal'.
        $audience = $data['audience'] ?? 'external';

        $defaultReason = $audience === 'internal'
            ? 'Ricevi questa email perché sei tra i destinatari operativi (DPO / PRRC) di ' . config('lab.manufacturer.name') . '.'
            : 'Ricevi questa email perché hai un ordine attivo con ' . config('lab.manufacturer.name') . '.';

        return $this->subject($data['subject'] ?? 'Notifica ' . config('app.name'))
            ->replyTo(config('mail.reply_to.address'), config('mail.reply_to.name'))
            ->view('emails.master')
            ->with([
                'title'          => $data['title'] ?? ($data['subject'] ?? 'Notifica ' . config('app.name')),
                'paragraphs'     => $paragraphs,
                'meta'           => $data['meta'] ?? null,
                'details'        => $data['details'] ?? [],
                'infobox'        => $data['infobox'] ?? null,
                'cta'            => $cta,
                'notes'          => $data['notes'] ?? [],
                'footerVariant'  => $audience,
                'footerReason'   => $data['footerReason'] ?? $defaultReason,
                'unsubscribeUrl' => $data['unsubscribeUrl'] ?? null,
                'preheader'      => $data['preheader'] ?? null,
                'logoPath'       => file_exists($logoPath) ? $logoPath : null,
            ]);
    }

    /**
     * Normalizza un messaggio (testo con \n OPPURE HTML legacy con <br>/<strong>/<code>)
     * in un array di paragrafi puliti. <br> → newline, tag rimossi, entità decodificate
     * una volta (il master ri-escapa con e()). Doppio newline = nuovo paragrafo.
     */
    public static function messageToParagraphs(string $message): array
    {
        $s = preg_replace('/<br\s*\/?>/i', "\n", $message);
        $s = preg_replace('/<\/(p|div|li|tr)>/i', "\n", $s);
        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = preg_split('/\n{2,}/', trim($s));

        return array_values(array_filter(
            array_map(fn ($p) => trim($p), $parts),
            fn ($p) => $p !== ''
        ));
    }
}
