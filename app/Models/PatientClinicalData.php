<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientClinicalData extends Model
{
    protected $table = 'patient_clinical_data';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'record_date',
        'category',
        'title',
        'description',
        'severity',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    public function scopeChronic($query)
    {
        return $query->where('status', 'Chronic');
    }

    // Categories
    public static function categories(): array
    {
        return [
            'Diagnosis',
            'Symptom',
            'Observation',
            'Lab Finding',
            'Imaging Finding',
            'Procedure',
            'Assessment',
            'Plan',
            'Progress Note',
            'Other',
        ];
    }
}
