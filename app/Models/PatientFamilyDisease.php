<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFamilyDisease extends Model
{
    protected $fillable = [
        'patient_id',
        'disease_name',
        'relationship',
        'relative_name',
        'age_at_diagnosis',
        'status',
        'age_at_death',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'age_at_diagnosis' => 'integer',
            'age_at_death' => 'integer',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    // Family relationships
    public static function relationships(): array
    {
        return [
            'Father',
            'Mother',
            'Brother',
            'Sister',
            'Paternal Grandfather',
            'Paternal Grandmother',
            'Maternal Grandfather',
            'Maternal Grandmother',
            'Son',
            'Daughter',
            'Uncle',
            'Aunt',
            'Cousin',
            'Other',
        ];
    }

    // Common hereditary diseases
    public static function commonDiseases(): array
    {
        return [
            'Diabetes Mellitus',
            'Hypertension',
            'Coronary Artery Disease',
            'Stroke',
            'Cancer - Breast',
            'Cancer - Colon',
            'Cancer - Lung',
            'Cancer - Prostate',
            'Asthma',
            'COPD',
            'Chronic Kidney Disease',
            'Thalassemia',
            'Sickle Cell Disease',
            'Hemophilia',
            'Alzheimer\'s Disease',
            'Parkinson\'s Disease',
            'Epilepsy',
            'Multiple Sclerosis',
            'Rheumatoid Arthritis',
            'Lupus',
            'Thyroid Disease',
            'Mental Illness',
            'Alcoholism',
            'Obesity',
        ];
    }

    // Scopes
    public function scopeImmediate($query)
    {
        return $query->whereIn('relationship', ['Father', 'Mother', 'Brother', 'Sister', 'Son', 'Daughter']);
    }

    public function scopeGrandparents($query)
    {
        return $query->whereIn('relationship', [
            'Paternal Grandfather',
            'Paternal Grandmother',
            'Maternal Grandfather',
            'Maternal Grandmother',
        ]);
    }

    public function scopeDeceased($query)
    {
        return $query->where('status', 'Deceased');
    }

    public function getRelationshipGroupAttribute(): string
    {
        return match ($this->relationship) {
            'Father', 'Mother' => 'Parents',
            'Brother', 'Sister' => 'Siblings',
            'Son', 'Daughter' => 'Children',
            'Paternal Grandfather', 'Paternal Grandmother', 'Maternal Grandfather', 'Maternal Grandmother' => 'Grandparents',
            'Uncle', 'Aunt', 'Cousin' => 'Extended Family',
            default => 'Other',
        };
    }
}
