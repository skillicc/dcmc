<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientChronicDisease extends Model
{
    protected $fillable = [
        'patient_id',
        'disease_name',
        'icd_code',
        'diagnosed_date',
        'severity',
        'status',
        'current_treatment',
        'notes',
        'show_alert',
        'diagnosed_by',
    ];

    protected function casts(): array
    {
        return [
            'diagnosed_date' => 'date',
            'show_alert' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function diagnosedBy(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'diagnosed_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeWithAlerts($query)
    {
        return $query->where('show_alert', true);
    }

    // Common chronic diseases
    public static function commonDiseases(): array
    {
        return [
            'Diabetes Mellitus Type 1',
            'Diabetes Mellitus Type 2',
            'Hypertension',
            'Coronary Artery Disease',
            'Heart Failure',
            'Chronic Kidney Disease',
            'COPD',
            'Asthma',
            'Rheumatoid Arthritis',
            'Hypothyroidism',
            'Hyperthyroidism',
            'Epilepsy',
            'Depression',
            'Anxiety Disorder',
            'Osteoporosis',
            'Chronic Liver Disease',
            'HIV/AIDS',
            'Hepatitis B',
            'Hepatitis C',
            'Cancer',
        ];
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'Critical' => 'error',
            'Severe' => 'error',
            'Moderate' => 'warning',
            default => 'info',
        };
    }
}
