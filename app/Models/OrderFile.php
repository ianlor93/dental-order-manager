<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFile extends Model
{
    use SoftDeletes;

    protected $table = 'oc_enamel_order_files';

    protected $fillable = [
        'order_id',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'hash_sha256',
        'scan_status', // 'pending', 'clean', 'infected'
        'scanned_at',
        'error_log'    // Traccia il motivo dello scarto
    ];

    protected $casts = [
        'order_id'   => 'integer',
        'size_bytes' => 'integer',
        'scanned_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = ['readable_size'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function getReadableSizeAttribute(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public function isSafe(): bool
    {
        return $this->scan_status === 'clean';
    }
}