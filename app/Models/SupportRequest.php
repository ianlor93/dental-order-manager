<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportRequest extends Model
{
    protected $table = 'oc_enamel_support_requests';

    protected $fillable = [
        'studio_id',
        'user_uid',
        'subject',
        'body',
        'priority',
        'status',
        'staff_notes',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'aperta';
    }

    public function isClosed(): bool
    {
        return $this->status === 'chiusa';
    }
}
