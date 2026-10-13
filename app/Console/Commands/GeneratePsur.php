<?php

namespace App\Console\Commands;

use App\Mail\EnamelNotification;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class GeneratePsur extends Command
{
    protected $signature = 'mdr:generate-psur
                            {from : Data inizio periodo (YYYY-MM-DD)}
                            {to   : Data fine periodo (YYYY-MM-DD)}
                            {--dry-run : Solo output console, non scrive file né invia email}';

    protected $description = 'Genera il Post-Market Surveillance Update Report (PSUR) Art. 86 MDR per il periodo specificato.';

    public function handle(): int
    {
        try {
            $from = Carbon::parse($this->argument('from'))->startOfDay();
            $to   = Carbon::parse($this->argument('to'))->endOfDay();
        } catch (\Throwable) {
            $this->error('Formato data non valido. Usare YYYY-MM-DD.');
            return self::FAILURE;
        }

        if ($from->gt($to)) {
            $this->error('La data di inizio deve precedere la data di fine.');
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $this->info("Generazione PSUR: {$from->format('d/m/Y')} — {$to->format('d/m/Y')}");

        $orders = Order::whereBetween('archived_at', [$from, $to])
            ->whereNotNull('archived_at')
            ->with('studio')
            ->get();

        $byRiskClass        = $orders->groupBy('risk_class');
        $byDeviceType       = $orders->groupBy('device_type');
        $withIncidents      = $orders->filter(fn($o) => $o->incident_reported_at !== null);
        $unresolvedIncidents = $withIncidents->filter(fn($o) => $o->incident_resolved_at === null);
        $pmsReviewed        = $orders->filter(fn($o) => $o->pms_status === 'reviewed');
        $pmsOverdue         = $orders->filter(fn($o) => $o->pms_next_review_at?->isPast());

        $freq = $byRiskClass->has('IIb')
            ? 'Annuale (presenza dispositivi Classe IIb — MDR Art. 86 par. 2)'
            : 'Biennale (solo dispositivi Classe IIa — MDR Art. 86 par. 1)';

        $viewData = [
            'from'                => $from,
            'to'                  => $to,
            'generato_il'         => now(),
            'lab'                 => config('lab.manufacturer.name'),
            'prrc_name'           => config('lab.prrc.name'),
            'prrc_role'           => config('lab.prrc.role'),
            'freq'                => $freq,
            'total'               => $orders->count(),
            'byRiskClass'         => $byRiskClass,
            'byDeviceType'        => $byDeviceType,
            'withIncidents'       => $withIncidents,
            'unresolvedIncidents' => $unresolvedIncidents,
            'pmsReviewed'         => $pmsReviewed,
            'pmsOverdue'          => $pmsOverdue,
        ];

        $filename = "PSUR_{$from->format('Ymd')}_{$to->format('Ymd')}.pdf";

        if ($dryRun) {
            $this->info("PSUR pronto: {$filename}");
            $this->info("Dispositivi: {$orders->count()} | Incidenti: {$withIncidents->count()} | Frequenza: {$freq}");
            $this->warn("(dry-run: file non scritto, email non inviata)");
            return self::SUCCESS;
        }

        try {
            $pdf  = Pdf::loadView('pdfs.psur', $viewData)->output();
            $disk = Storage::disk('unraid_arch_l');
            file_put_contents($disk->path($filename), $pdf);
            $this->info("Report salvato: ARCH_L/{$filename}");
            Log::info('MDR-INTEGRITY: PSUR generato', [
                'file'      => $filename,
                'orders'    => $orders->count(),
                'incidents' => $withIncidents->count(),
            ]);
        } catch (\Throwable $e) {
            $this->error("Errore generazione PDF: " . $e->getMessage());
            Log::error('MDR-INTEGRITY: Errore scrittura PSUR', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        $this->notifyPrrc($filename, $from, $to, $orders->count(), $withIncidents->count(), $unresolvedIncidents->count());

        return self::SUCCESS;
    }

    private function notifyPrrc(string $filename, Carbon $from, Carbon $to, int $total, int $incidents, int $unresolved): void
    {
        $prrcEmail = config('lab.prrc.email') ?: config('lab.manufacturer.email');
        if (!$prrcEmail) {
            Log::warning('MDR-INTEGRITY: Email PRRC non configurata — notifica PSUR saltata');
            return;
        }

        $alertIncidents = $unresolved > 0
            ? " (<strong style='color:#b91c1c'>{$unresolved} aperti</strong>)"
            : '';

        try {
            Mail::to($prrcEmail)->queue(new EnamelNotification([
                'subject' => "📄 PSUR Art. 86 MDR generato — {$from->format('m/Y')}",
                'title'   => 'PSUR disponibile per firma',
                'audience' => 'internal',
                'message' => implode('<br>', [
                    "Il Report PSUR per il periodo <strong>{$from->format('d/m/Y')} — {$to->format('d/m/Y')}</strong> è disponibile.",
                    '',
                    "File: <code>{$filename}</code> (disco ARCH_L)",
                    "Dispositivi archiviati: <strong>{$total}</strong>",
                    "Incidenti segnalati: <strong>{$incidents}</strong>{$alertIncidents}",
                    '',
                    'Stampare, firmare e archiviare nel fascicolo MDR entro 30 giorni.',
                ]),
            ]));
        } catch (\Throwable $e) {
            Log::error('MDR-INTEGRITY: Notifica PSUR fallita: ' . $e->getMessage());
        }
    }
}
