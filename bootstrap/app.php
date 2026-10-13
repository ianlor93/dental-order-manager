<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule; 
use App\Http\Middleware\AllowNextcloudFrame;
use App\Http\Middleware\EnsureLegalCompliance;
use App\Http\Middleware\SmartThrottle;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        // 1. Proxy fidati: subnet della rete Docker del reverse proxy. Adattare alla propria rete.
        $middleware->trustProxies(at: ['172.18.0.0/16']);

        // 2. Permetti l'iframe: Rimuove i blocchi X-Frame-Options
        $middleware->append(AllowNextcloudFrame::class);

        // 3. Registrazione Alias per il Legal Gate e Rate Limiting
        $middleware->alias([
            'legal.gate'     => EnsureLegalCompliance::class,
            'throttle.smart' => SmartThrottle::class, // Rate limiting graduato (rollback: rimuovi alias e middleware dalle route)
        ]);

        // NOTA: Il NextcloudAuthBridge rimane in routes/web.php come concordato.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        // 🕒 RESET CREDITI AUTOMATICO (Ogni notte a mezzanotte)
        // Cerca gli abbonamenti scaduti, ricarica i crediti regalo e sposta la scadenza
        // Il metodo dailyAt('00:00') garantisce l'esecuzione puntuale a inizio giornata
        $schedule->command('ibex:reset-credits')->dailyAt('00:00');

        // 🛡️ VERIFICA INTEGRITÀ ARCHIVIO MDR (Ogni primo del mese alle 03:00)
        // Ricalcola SHA-256 di tutti i file archiviati (is_locked=true) e confronta con l'hash nel DB.
        // Genera INTEGRITY_REPORT_{YYYY-MM}.txt su ARCH_L. Notifica il PRRC via email in caso di anomalie.
        // MDR Art. 10(8) — ISO 13485 §4.2.5
        $schedule->command('mdr:verify-integrity')->monthlyOn(1, '03:00');

        // 📋 RETENTION SWEEP GDPR/MDR (Ogni primo del mese alle 04:00)
        // Identifica ordini con retention MDR scaduta (legal_retention_until <= now).
        // Senza --apply: solo report al DPO. Con --apply: rimuove is_locked per abilitare cancellazione GDPR Art. 17.
        // RT-D — LEGAL_GDPR_RIGHTS.md §3.6
        $schedule->command('gdpr:retention-sweep')->monthlyOn(1, '04:00');

        // 📧 DRENAGGIO CODA (Mail::queue + tabella `jobs`) — ogni minuto, SENZA daemon.
        // Il cron host esegue già `schedule:run` al minuto: qui un worker EFFIMERO processa
        // i job in attesa e poi ESCE (--stop-when-empty), con tetto di tempo (--max-time=55)
        // per non sconfinare nel minuto successivo. Vantaggi vs daemon+watchdog:
        //  - nessun processo long-running da "tenere vivo" (niente watchdog/SPOF);
        //  - dopo ogni deploy gira SEMPRE il codice aggiornato (un daemon resta sul vecchio);
        //  - si auto-ripristina dopo un restart del container (riparte il minuto dopo).
        // FOREGROUND (niente runInBackground): a coda vuota esce in <1s (nessun blocco reale);
        // il mutex withoutOverlapping viene rilasciato nel finally di schedule:run anche se il
        // worker viene ucciso → niente lock "incastrato" (col background restava appeso fino
        // alla scadenza). withoutOverlapping(10) evita il pile-up di worker se l'SMTP è lento.
        $schedule->command('queue:work --stop-when-empty --tries=3 --max-time=55')
            ->everyMinute()
            ->withoutOverlapping(10);
    })
    ->create();