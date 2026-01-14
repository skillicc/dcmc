<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrugInteraction extends Model
{
    protected $fillable = [
        'drug1_name',
        'drug2_name',
        'severity',
        'description',
        'mechanism',
        'management',
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

    public function scopeMajor($query)
    {
        return $query->where('severity', 'Major');
    }

    public function scopeModerate($query)
    {
        return $query->where('severity', 'Moderate');
    }

    public function scopeMinor($query)
    {
        return $query->where('severity', 'Minor');
    }

    // Search for interactions involving a drug
    public function scopeInvolvingDrug($query, string $drugName)
    {
        return $query->where(function ($q) use ($drugName) {
            $q->where('drug1_name', 'like', "%{$drugName}%")
                ->orWhere('drug2_name', 'like', "%{$drugName}%");
        });
    }

    // Find interactions between two drugs
    public static function findInteraction(string $drug1, string $drug2): ?self
    {
        return static::where(function ($q) use ($drug1, $drug2) {
            $q->where(function ($q) use ($drug1, $drug2) {
                $q->where('drug1_name', 'like', "%{$drug1}%")
                    ->where('drug2_name', 'like', "%{$drug2}%");
            })->orWhere(function ($q) use ($drug1, $drug2) {
                $q->where('drug1_name', 'like', "%{$drug2}%")
                    ->where('drug2_name', 'like', "%{$drug1}%");
            });
        })->active()->first();
    }

    // Severity levels
    public static function severityLevels(): array
    {
        return [
            'Major' => 'Potentially life-threatening or capable of causing permanent damage',
            'Moderate' => 'May result in exacerbation of patient condition',
            'Minor' => 'Minimal clinical effects, may be bothersome',
            'Contraindicated' => 'Never use together',
        ];
    }

    // Interaction types
    public static function interactionTypes(): array
    {
        return [
            'Pharmacokinetic',
            'Pharmacodynamic',
            'Unknown',
        ];
    }

    // Documentation levels
    public static function documentationLevels(): array
    {
        return [
            'Established',
            'Probable',
            'Suspected',
            'Possible',
            'Unknown',
        ];
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'Contraindicated' => 'error',
            'Major' => 'error',
            'Moderate' => 'warning',
            'Minor' => 'info',
            default => 'grey',
        };
    }
}
