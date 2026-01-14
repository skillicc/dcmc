<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientCurrentMedication extends Model
{
    protected $fillable = [
        'patient_id',
        'medicine_name',
        'generic_name',
        'dosage',
        'frequency',
        'route',
        'start_date',
        'end_date',
        'prescribed_for',
        'prescribed_by',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'prescribed_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeDiscontinued($query)
    {
        return $query->where('status', 'Discontinued');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeSelfPrescribed($query)
    {
        return $query->where('is_self_prescribed', true);
    }

    // Common frequencies
    public static function frequencies(): array
    {
        return [
            'OD (Once Daily)',
            'BD (Twice Daily)',
            'TDS (Three Times Daily)',
            'QID (Four Times Daily)',
            'HS (At Bedtime)',
            'PRN (As Needed)',
            'Q4H (Every 4 Hours)',
            'Q6H (Every 6 Hours)',
            'Q8H (Every 8 Hours)',
            'Q12H (Every 12 Hours)',
            'Weekly',
            'Bi-weekly',
            'Monthly',
            'Stat (Immediately)',
            'AC (Before Meals)',
            'PC (After Meals)',
        ];
    }

    // Common routes
    public static function routes(): array
    {
        return [
            'Oral',
            'Sublingual',
            'Topical',
            'Intravenous (IV)',
            'Intramuscular (IM)',
            'Subcutaneous (SC)',
            'Inhalation',
            'Nasal',
            'Ophthalmic',
            'Otic',
            'Rectal',
            'Vaginal',
            'Transdermal',
        ];
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'Active' => 'success',
            'Discontinued' => 'error',
            'Completed' => 'info',
            'On Hold' => 'warning',
            default => 'grey',
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date < now();
    }
}
