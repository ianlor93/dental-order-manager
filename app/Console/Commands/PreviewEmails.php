<?php

namespace App\Console\Commands;

use App\Mail\EnamelNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;

/**
 * Anteprime READ-ONLY del master template email (emails.master).
 *
 * Renderizza ogni "famiglia" di mail con dati di esempio nei file HTML sotto
 * storage/app/email-previews/ — da aprire nel browser. NESSUN invio.
 * Il logo è iniettato come data-URI base64 così è visibile nel browser
 * (nelle Mailable reali si usa il CID embed via $message->embed()).
 *
 * Uso: php artisan mail:preview
 */
class PreviewEmails extends Command
{
    protected $signature = 'mail:preview';
    protected $description = 'Genera le anteprime HTML del master template email (nessun invio)';

    public function handle(): int
    {
        $dir = storage_path('app/email-previews');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Logo come data-URI (solo per l'anteprima nel browser)
        $logoPath = storage_path('app/branding/logo-email.png');
        if (!file_exists($logoPath)) {
            $this->warn("⚠️  Logo email assente: {$logoPath}");
            $this->warn("    Trascina il PNG TRASPARENTE qui, poi rilancia mail:preview.");
        }
        $logoSrc = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $welcome = $this->safeRoute('welcome');
        $archive = $this->safeRoute('dashboard.archive');

        $previews = [
            '01-funnel-verify' => [
                'title'         => 'Ciao Marco, confermiamo la tua email',
                'paragraphs'    => ["Per completare la registrazione alla piattaforma, conferma il tuo indirizzo email e imposta la password del tuo account con un clic sul pulsante qui sotto."],
                'cta'           => ['label' => 'Conferma e imposta la password', 'url' => '#', 'variant' => 'primary'],
                'notes'         => [
                    "Il link è valido per 48 ore e può essere usato una sola volta. Dopo aver impostato la password, il tuo account sarà attivato a seguito della verifica da parte del nostro team.",
                    "Se il pulsante non funziona, copia e incolla questo indirizzo nel browser:\n" . rtrim((string) config('services.nextcloud.url'), '/') . "/apps/enamel_setpassword/verify?t=ESEMPIO",
                    "Se non hai richiesto tu la registrazione, ignora pure questa email.",
                ],
                'footerVariant' => 'external',
                'footerReason'  => 'Hai ricevuto questa email perché è stata avviata una registrazione su ' . config('app.name') . ' con questo indirizzo.',
            ],
            '02-lead-confirmation' => [
                'title'         => 'Ciao Marco, grazie!',
                'paragraphs'    => [
                    "Abbiamo ricevuto la tua pre-registrazione e ci fa davvero piacere l'interesse che hai mostrato verso " . config('lab.manufacturer.name') . ".",
                    "Stiamo lavorando a qualcosa di nuovo per odontotecnici, studi odontoiatrici e fornitori di servizi per l'odontoiatria. Sarai tra i primi a saperlo non appena tutto sarà pronto.",
                ],
                'infobox'       => ['text' => "Ti ricontatteremo entro 24 ore all'indirizzo marco@studio-demo.it per condividere con te le prime novità in anteprima."],
                'notes'         => [
                    "Se hai domande nel frattempo, scrivici pure a " . config('lab.manufacturer.email') . ": saremo felici di risponderti.",
                    "A presto,\nIl team di " . config('lab.manufacturer.name'),
                ],
                'footerVariant' => 'external',
                'footerReason'  => 'Questa email è stata inviata perché hai compilato il form di pre-registrazione su ' . config('lab.landing_url') . '.',
            ],
            '03-ordine-ricezione' => [
                'title'         => 'Ordine Ricevuto',
                'paragraphs'    => EnamelNotification::messageToParagraphs("Gentile Studio Demo,\nabbiamo ricevuto correttamente il tuo ordine per il paziente PZ-0042. I file sono in fase di controllo.\n\nRiceverai un aggiornamento appena la prescrizione sarà pronta."),
                'cta'           => ['label' => 'Vedi Dettagli', 'url' => $welcome],
                'footerVariant' => 'external',
                'footerReason'  => 'Ricevi questa email perché hai un ordine attivo con ' . config('lab.manufacturer.name') . '.',
            ],
            '05-funnel-new-registrant' => [
                'title'         => 'Nuovo iscritto verificato',
                'paragraphs'    => ["Un nuovo iscritto ha confermato l'email e impostato la password. È pronto per il vaglio nel pannello Amministrazione."],
                'details'       => [
                    ['label' => 'Ragione sociale',  'value' => 'Studio Demo S.r.l.'],
                    ['label' => 'Referente',        'value' => 'Dr. Marco Rossi'],
                    ['label' => 'Email',            'value' => 'marco@studio-demo.it'],
                    ['label' => 'P.IVA',            'value' => '01234567890'],
                    ['label' => 'Tipo attività',    'value' => 'studio_odontoiatrico'],
                    ['label' => 'Telefono',         'value' => '+39 06 00000000'],
                    ['label' => 'Utente Nextcloud', 'value' => 'marco.rossi'],
                ],
                'footerVariant' => 'internal',
                'footerReason'  => 'Notifica interna automatica del funnel di registrazione di ' . config('app.name') . '.',
            ],
            '06-gdpr-richiesta' => [
                'title'         => 'Nuova richiesta GDPR — Cancellazione dati (Art. 17)',
                'paragraphs'    => ["È stata registrata una nuova richiesta per il diritto: Cancellazione dati (Art. 17)."],
                'details'       => [
                    ['label' => 'Riferimento',           'value' => '#42'],
                    ['label' => 'Interessato',           'value' => 'Marco Rossi (marco.rossi)'],
                    ['label' => 'Email',                 'value' => 'marco@studio-demo.it'],
                    ['label' => 'Categoria dati',        'value' => 'Dati account'],
                    ['label' => 'Ricevuta il',           'value' => '18/06/2026 14:32'],
                    ['label' => 'Scadenza Art. 12 GDPR', 'value' => '18/07/2026'],
                    ['label' => 'IP richiedente',        'value' => '203.0.113.7'],
                ],
                'infobox'       => ['label' => 'Motivazione / Dettagli', 'text' => "Chiedo la cancellazione di tutti i miei dati personali ai sensi dell'art. 17 GDPR."],
                'notes'         => ["Accedi al backoffice " . config('app.name') . " per gestire la richiesta, registrare la decisione e inviare risposta all'interessato entro la scadenza indicata."],
                'footerVariant' => 'internal',
                'footerReason'  => 'Ricevi questa email perché sei tra i destinatari operativi (DPO / PRRC) di ' . config('lab.manufacturer.name') . '.',
            ],
            '07-incidente-mdr' => [
                'title'         => 'Segnalazione Incidente MDR — Azione Richiesta',
                'paragraphs'    => ["È stato segnalato un incidente sul dispositivo AB12CD3."],
                'details'       => [
                    ['label' => 'Gravità',      'value' => 'Grave'],
                    ['label' => 'Segnalato da', 'value' => 'Dr. Marco Rossi'],
                    ['label' => 'Descrizione',  'value' => 'Frattura della struttura in zona molare durante la masticazione.'],
                    ['label' => '⚠️ Scadenza notifica Ministero', 'value' => '25/06/2026'],
                ],
                'notes'         => ["Accedi alla piattaforma per gestire l'indagine e documentare le misure correttive."],
                'cta'           => ['label' => "Vai all'Archivio", 'url' => $archive],
                'footerVariant' => 'internal',
                'footerReason'  => 'Ricevi questa email perché sei tra i destinatari operativi (DPO / PRRC) di ' . config('lab.manufacturer.name') . '.',
            ],
            // Prova del normalizzatore P2: message HTML legacy → paragrafi puliti
            '08-retention-report' => [
                'title'         => 'Report Retention Mensile',
                'paragraphs'    => EnamelNotification::messageToParagraphs(
                    "Trovati <strong>2 ordini</strong> con retention MDR scaduta:<br>" .
                    "• <code>AB12CD3</code> — Scadenza: 01/06/2026 — Studio: Studio Demo<br>" .
                    "• <code>XY98ZT1</code> — Scadenza: 03/06/2026 — Studio: Altro Studio<br><br>" .
                    "Eseguire <code>gdpr:retention-sweep --apply</code> per sbloccare la cancellazione."
                ),
                'footerVariant' => 'internal',
                'footerReason'  => 'Ricevi questa email perché sei tra i destinatari operativi (DPO / PRRC) di ' . config('lab.manufacturer.name') . '.',
            ],
            // Mail di benvenuto / account attivo (ramo approvato T4b)
            '09-welcome-account-attivo' => [
                'title'         => 'Benvenuto in ' . config('app.name') . ', Marco!',
                'paragraphs'    => [
                    "Il tuo account è stato attivato: da ora puoi accedere alla piattaforma con la tua email e la password che hai impostato in fase di registrazione.",
                    "Dal portale gestisci gli ordini e i dispositivi del tuo studio in un unico posto.",
                ],
                'cta'           => ['label' => 'Accedi alla piattaforma', 'url' => config('services.nextcloud.url')],
                'notes'         => ["Hai dimenticato la password? Usa «Hai dimenticato la password?» nella pagina di accesso. Per qualsiasi necessità scrivici a " . config('lab.manufacturer.email') . "."],
                'footerVariant' => 'external',
                'footerReason'  => 'Ricevi questa email perché il tuo account ' . config('app.name') . ' è stato attivato.',
            ],
        ];

        $links = [];
        foreach ($previews as $name => $contract) {
            $html = View::make('emails.master', array_merge($contract, ['logoSrc' => $logoSrc]))->render();
            $file = "{$dir}/{$name}.html";
            file_put_contents($file, $html);
            $links[] = "<li><a href=\"{$name}.html\">{$name}</a></li>";
            $this->info("✓ {$file}");
        }

        // Indice
        $index = "<!doctype html><meta charset=utf-8><title>Anteprime email " . config('app.name') . "</title>"
               . "<body style='font-family:sans-serif;padding:40px;background:#e7e5df'>"
               . "<h1>Anteprime master template email</h1><ul style='font-size:18px;line-height:2'>"
               . implode('', $links) . "</ul></body>";
        file_put_contents("{$dir}/index.html", $index);

        $this->newLine();
        $this->info("Anteprime in: {$dir}");
        $this->info("Apri index.html nel browser (via SMB: storage/app/email-previews/index.html).");

        return self::SUCCESS;
    }

    private function safeRoute(string $name): string
    {
        try {
            return route($name);
        } catch (\Throwable $e) {
            return '#';
        }
    }
}
