<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientImmunization extends Model
{
    protected $fillable = [
        'patient_id',
        'vaccine_name',
        'vaccine_type',
        'dose_number',
        'administered_date',
        'next_dose_date',
        'batch_number',
        'manufacturer',
        'site_of_injection',
        'adverse_reactions',
        'administered_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'administered_date' => 'date',
            'next_dose_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'In Progress');
    }

    public function scopeUpcoming($query)
    {
        return $query->whereNotNull('next_dose_date')
            ->where('next_dose_date', '>=', now())
            ->where('status', 'In Progress');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('next_dose_date')
            ->where('next_dose_date', '<', now())
            ->where('status', 'In Progress');
    }

    // Common vaccines
    public static function commonVaccines(): array
    {
        return [
            'BCG',
            'Hepatitis B',
            'OPV (Oral Polio Vaccine)',
            'IPV (Inactivated Polio Vaccine)',
            'DPT (Diphtheria, Pertussis, Tetanus)',
            'Pentavalent',
            'PCV (Pneumococcal Conjugate Vaccine)',
            'Rotavirus',
            'Measles',
            'MR (Measles-Rubella)',
            'MMR (Measles, Mumps, Rubella)',
            'Typhoid',
            'Japanese Encephalitis',
            'Influenza',
            'Hepatitis A',
            'Varicella (Chickenpox)',
            'HPV (Human Papillomavirus)',
            'Meningococcal',
            'Rabies',
            'COVID-19',
            'Tdap',
            'Tetanus Toxoid',
        ];
    }

    // Administration routes
    public static function routes(): array
    {
        return [
            'Intramuscular (IM)',
            'Subcutaneous (SC)',
            'Intradermal (ID)',
            'Oral',
            'Intranasal',
        ];
    }

    // Administration sites
    public static function sites(): array
    {
        return [
            'Left Deltoid',
            'Right Deltoid',
            'Left Thigh',
            'Right Thigh',
            'Left Arm',
            'Right Arm',
            'Oral',
        ];
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->next_dose_date && $this->next_dose_date < now() && $this->status === 'In Progress';
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->is_overdue) {
            return 'error';
        }

        return match ($this->status) {
            'Completed' => 'success',
            'In Progress' => 'warning',
            'Not Started' => 'grey',
            default => 'info',
        };
    }
}
