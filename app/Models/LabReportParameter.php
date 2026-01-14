<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabReportParameter extends Model
{
    protected $fillable = [
        'lab_report_id',
        'name',
        'value',
        'unit',
        'normal_range',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function labReport(): BelongsTo
    {
        return $this->belongsTo(LabReport::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'Critical' => 'error',
            'High' => 'warning',
            'Low' => 'info',
            default => 'success',
        };
    }
}
