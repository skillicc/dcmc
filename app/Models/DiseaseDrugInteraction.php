<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiseaseDrugInteraction extends Model
{
    protected $fillable = [
        'disease_name',
        'drug_name',
        'severity',
        'description',
        'alternative',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'Critical');
    }

    public function scopeForDisease($query, string $disease)
    {
        return $query->where('disease_name', 'like', "%{$disease}%");
    }

    public function scopeForDrug($query, string $drug)
    {
        return $query->where('drug_name', 'like', "%{$drug}%");
    }

    // Check if a drug is contraindicated for a disease
    public static function checkContraindication(string $disease, string $drug): ?self
    {
        return static::active()
            ->forDisease($disease)
            ->forDrug($drug)
            ->first();
    }

    // Check multiple diseases against a drug
    public static function checkDiseases(array $diseases, string $drug): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->where(function ($q) use ($diseases) {
                foreach ($diseases as $disease) {
                    $q->orWhere('disease_name', 'like', "%{$disease}%");
                }
            })
            ->forDrug($drug)
            ->get();
    }

    // Severity levels
    public static function severityLevels(): array
    {
        return [
            'Critical' => 'Drug must not be used with this condition',
            'High' => 'Significant risk, avoid if possible',
            'Moderate' => 'Use with caution, monitoring required',
            'Low' => 'Minor risk, be aware',
        ];
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'Critical' => 'error',
            'High' => 'error',
            'Moderate' => 'warning',
            'Low' => 'info',
            default => 'grey',
        };
    }
}
