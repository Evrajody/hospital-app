<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportJob extends Model
{
    use HasUuids;

    public const STATUT_PENDING = 'pending';
    public const STATUT_PROCESSING = 'processing';
    public const STATUT_COMPLETED = 'completed';
    public const STATUT_FAILED = 'failed';

    protected $fillable = [
        'user_id', 'report', 'format', 'label', 'params',
        'status', 'file_path', 'file_name', 'error', 'completed_at',
    ];

    protected $casts = [
        'params' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUT_COMPLETED && $this->file_path;
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'report' => $this->report,
            'format' => $this->format,
            'label' => $this->label,
            'status' => $this->status,
            'file_name' => $this->file_name,
            'error' => $this->error,
            'download_url' => $this->isReady() ? url("/rapports/exports/{$this->id}/download") : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
        ];
    }
}
