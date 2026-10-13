<?php

namespace App\Jobs;

use App\Models\{Order, OrderFile};
use App\Services\VirusScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{Log, File, Storage};
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class SupplementOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 600;

    public function __construct(public Order $order, public string $relativeTempPath, public string $originalName) {}

    public function handle(): void
    {
        $fullTempPath = storage_path('app/private/' . $this->relativeTempPath);

        try {
            if (!File::exists($fullTempPath)) return;

            $scanner = new VirusScanner();
            $scanResult = $scanner->scan($this->relativeTempPath);
            
            if ($scanResult === 'infected') {
                File::delete($fullTempPath);
                $this->order->logSystemEvent("🚨 SECURITY: Allegato {$this->originalName} rimosso perché infetto.");
                return;
            }

            $dir = $this->order->directory_name ?? strtoupper($this->order->code . "_" . Str::slug($this->order->patient_ref));
            
            $diskLaboratorio = Storage::disk('unraid_bac_l');
            $isAtBench = $diskLaboratorio->exists($dir);
            $targetDisk = $isAtBench ? $diskLaboratorio : Storage::disk('unraid');
            $targetDiskName = $isAtBench ? 'unraid_bac_l' : 'unraid';

            if (!$targetDisk->exists("{$dir}/assets_originali")) {
                $targetDisk->makeDirectory("{$dir}/assets_originali");
            }

            $sourceSize = File::size($fullTempPath);
            $stream = fopen($fullTempPath, 'r+');
            $targetPath = "{$dir}/assets_originali/{$this->originalName}";
            $targetDisk->writeStream($targetPath, $stream);
            if (is_resource($stream)) fclose($stream);

            if ($targetDisk->exists($targetPath)) {
                OrderFile::create([
                    'order_id'      => $this->order->id,
                    'file_path'     => "orders/{$this->order->code}/{$this->originalName}",
                    'original_name' => "[ADD] " . $this->originalName,
                    'size_bytes'    => $sourceSize,
                    'mime_type'     => File::mimeType($fullTempPath) ?? 'application/octet-stream',
                    'hash_sha256'   => hash_file('sha256', $fullTempPath),
                    'scan_status'   => $scanResult,
                    'scanned_at'    => now()
                ]);
                
                File::delete($fullTempPath);
                $this->order->logSystemEvent("Asset aggiuntivo integrato: {$this->originalName}");

                $this->order->refresh()->load(['studio', 'address', 'files']);

                $pdfData = [
                    'order' => $this->order,
                    'clinician' => $this->order->address->dentist_name ?? ($this->order->studio->dentist_name ?? 'N/D'),
                    'studio_name' => $this->order->studio->name ?? 'N/D',
                    'studio_address' => $this->order->address->address ?? 'N/D',
                    'license' => $this->order->address->dentist_license ?? 'N/D',
                    'prov' => $this->order->address->albo_provincia ?? 'N/D'
                ];

                $pdfPath = "{$dir}/Richiesta_{$this->order->code}.pdf";
                $targetDisk->put($pdfPath, Pdf::loadView('pdfs.request_sheet', $pdfData)->output());

                // ✨ Sblocco permessi disco attivo
                $this->order->unlockPermissions($targetDiskName);
            }
        } catch (\Throwable $e) {
            Log::error("MDR-SUPPL-FATAL [{$this->order->code}]: " . $e->getMessage());
        }
    }
}