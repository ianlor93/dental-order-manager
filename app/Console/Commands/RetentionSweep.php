<?php

namespace App\Console\Commands;

use App\Mail\EnamelNotification;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RetentionSweep extends Command
{
    protected $signature = 'gdpr:retention-sweep
                            {--apply : Rimuove effettivamente il lock MDR dagli ordini con retention scaduta}
                            {--dry-run : Solo output console, nessuna modifica né email}';

    protected $description = 'Identifica ordini MDR con retention scaduta e notifica il DPO con report mensile. (RT-D)';

    public function handle(): int
    {
        $apply  = $this->option('apply');
        $dryRun = $this->option('dry-run');
        $now    = Carbon::now();

        Log::info('SCHEDULER: Inizio retention-sweep', [
            'apply'   => $apply,
            'dry_run' => $dryRun,
            'date'    => $now->toDateString(),
        ]);

        $expired = Order::where('is_locked', true)
            ->where('legal_retention_until', '<=', $now)
            ->whereNull('deleted_at')
            ->with('studio')
            ->get();

        $approaching = Order::where('is_locked', true)
            ->whereBetween('legal_retention_until', [$now->copy()->addDay(), $now->copy()->addDays(90)])
            ->whereNull('deleted_at')
            ->with('studio')
            ->get();

        $this->info("Retention scaduta:               {$expired->count()} ordini");
        $this->info("In scadenza entro 90 giorni:     {$approaching->count()} ordini");

        if (!$dryRun && $apply && $expired->isNotEmpty()) {
            foreach ($expired as $order) {
                $order->update(['is_locked' => false]);
                $order->logSystemEvent(
                    'GDPR: Retention MDR scaduta — blocco rimosso. Ordine disponibile per cancellazione ex Art. 17 GDPR.'
                );
            }
            $this->info("Lock rimosso per {$expired->count()} ordini.");
            Log::info('SCHEDULER: retention-sweep — lock rimosso', ['count' => $expired->count()]);
        }

        if (!$dryRun) {
            $this->sendReport($expired, $approaching, $apply);
        }

        Log::info('SCHEDULER: retention-sweep completato', [
            'expired'    => $expired->count(),
            'approaching' => $approaching->count(),
            'apply'      => $apply,
        ]);

        return self::SUCCESS;
    }

    private function sendReport($expired, $approaching, bool $apply): void
    {
        $dpoEmail = config('lab.dpo.email') ?: config('lab.manufacturer.email');
        if (!$dpoEmail) {
            Log::warning('SCHEDULER: Email DPO non configurata — report retention saltato');
            return;
        }

        $month = now()->format('m/Y');
        $lines = [];

        if ($expired->isNotEmpty()) {
            $azione = $apply ? '✅ LOCK RIMOSSO' : '⚠️ DA REVISIONARE (eseguire con --apply per rimuovere il lock)';
            $lines[] = "<strong>ORDINI CON RETENTION SCADUTA [{$azione}]: {$expired->count()}</strong>";
            $lines[] = '';
            foreach ($expired as $order) {
                $scadenza = $order->legal_retention_until?->format('d/m/Y') ?? '—';
                $studio   = $order->studio?->name ?? '—';
                $lines[] = "• <code>{$order->code}</code> — Scadenza: {$scadenza} — Studio: {$studio}";
            }
        } else {
            $lines[] = 'Nessun ordine con retention MDR scaduta.';
        }

        if ($approaching->isNotEmpty()) {
            $lines[] = '';
            $lines[] = "<strong>IN SCADENZA ENTRO 90 GIORNI: {$approaching->count()}</strong>";
            foreach ($approaching as $order) {
                $scadenza = $order->legal_retention_until?->format('d/m/Y') ?? '—';
                $studio   = $order->studio?->name ?? '—';
                $lines[] = "• <code>{$order->code}</code> — Scadenza: {$scadenza} — Studio: {$studio}";
            }
        }

        try {
            Mail::to($dpoEmail)->queue(new EnamelNotification([
                'subject' => "📋 Retention Sweep MDR — {$month}",
                'title'   => 'Report Retention Mensile',
                'audience' => 'internal',
                'message' => implode('<br>', $lines),
            ]));
        } catch (\Throwable $e) {
            Log::error('SCHEDULER: Invio report retention fallito: ' . $e->getMessage());
        }
    }
}
