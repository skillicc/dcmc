<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'patient_id',
        'test_id',
        'doctor_id',
        'specimen_type',
        'specimen_id',
        'sample_date',
        'specimen_collected_at',
        'collected_by',
        'received_at_lab',
        'received_by',
        'result_entered_at',
        'result_entered_by',
        'approval_status',
        'approved_at',
        'approved_by',
        'approval_remarks',
        'delivery_date',
        'status',
        'remarks',
        'criticality',
        'is_critical_notified',
        'sms_sent',
        'sms_sent_at',
        'barcode',
    ];

    protected function casts(): array
    {
        return [
            'sample_date' => 'date',
            'delivery_date' => 'date',
            'specimen_collected_at' => 'datetime',
            'received_at_lab' => 'datetime',
            'result_entered_at' => 'datetime',
            'approved_at' => 'datetime',
            'sms_sent_at' => 'datetime',
            'is_critical_notified' => 'boolean',
            'sms_sent' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($report) {
            $report->report_id = 'LR' . str_pad((static::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
            $report->barcode = 'BC' . date('Ymd') . str_pad((static::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);
            $report->specimen_id = 'SP' . date('Ymd') . str_pad((static::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function resultEnteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_entered_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(LabReportParameter::class)->orderBy('sort_order');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeSampleCollected($query)
    {
        return $query->where('status', 'Sample Collected');
    }

    public function scopeReceivedAtLab($query)
    {
        return $query->where('status', 'Received at Lab');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'Processing');
    }

    public function scopeAwaitingApproval($query)
    {
        return $query->where('approval_status', 'Pending')
            ->whereNotNull('result_entered_at');
    }

    public function scopeCritical($query)
    {
        return $query->where('criticality', 'Critical');
    }

    public function scopeAbnormal($query)
    {
        return $query->whereIn('criticality', ['Abnormal', 'Critical']);
    }

    public function scopeReadyForDelivery($query)
    {
        return $query->where('approval_status', 'Approved')
            ->where('status', '!=', 'Delivered');
    }

    // Helper methods
    public function collectSpecimen(int $userId, ?string $specimenType = null): void
    {
        $this->update([
            'status' => 'Sample Collected',
            'specimen_collected_at' => now(),
            'collected_by' => $userId,
            'specimen_type' => $specimenType ?? $this->specimen_type,
        ]);
    }

    public function receiveAtLab(int $userId): void
    {
        $this->update([
            'status' => 'Received at Lab',
            'received_at_lab' => now(),
            'received_by' => $userId,
        ]);
    }

    public function enterResult(array $parametersData, int $userId, ?string $criticality = 'Normal'): void
    {
        $this->update([
            'status' => 'Processing',
            'result_entered_at' => now(),
            'result_entered_by' => $userId,
            'criticality' => $criticality,
        ]);

        // Save parameters
        $this->parameters()->delete();
        foreach ($parametersData as $index => $param) {
            $this->parameters()->create([
                'name' => $param['name'] ?? 'Unknown',
                'value' => $param['value'] ?? null,
                'unit' => $param['unit'] ?? null,
                'normal_range' => $param['normal_range'] ?? null,
                'status' => $param['status'] ?? 'Normal',
                'sort_order' => $index,
            ]);
        }
    }

    public function approve(int $userId, ?string $remarks = null): void
    {
        $this->update([
            'approval_status' => 'Approved',
            'approved_at' => now(),
            'approved_by' => $userId,
            'approval_remarks' => $remarks,
            'status' => 'Completed',
        ]);
    }

    public function reject(int $userId, string $remarks): void
    {
        $this->update([
            'approval_status' => 'Rejected',
            'approved_at' => now(),
            'approved_by' => $userId,
            'approval_remarks' => $remarks,
        ]);
    }

    public function markDelivered(): void
    {
        $this->update([
            'status' => 'Delivered',
            'delivery_date' => now(),
        ]);
    }

    public function markSmsSent(): void
    {
        $this->update([
            'sms_sent' => true,
            'sms_sent_at' => now(),
        ]);
    }

    public function getCriticalityColorAttribute(): string
    {
        return match ($this->criticality) {
            'Critical' => 'error',
            'Abnormal' => 'warning',
            default => 'success',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'Pending' => 'grey',
            'Sample Collected' => 'info',
            'Received at Lab' => 'primary',
            'Processing' => 'warning',
            'Completed' => 'success',
            'Delivered' => 'success',
            default => 'grey',
        };
    }
}
