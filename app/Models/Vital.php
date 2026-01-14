<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vital extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'patient_queue_id',
        'date',
        'blood_pressure',
        'pulse',
        'temperature',
        'respiratory_rate',
        'oxygen_saturation',
        'weight',
        'height',
        'bmi',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'temperature' => 'decimal:1',
            'oxygen_saturation' => 'decimal:1',
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'bmi' => 'decimal:1',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($vital) {
            if ($vital->weight && $vital->height) {
                $heightInMeters = $vital->height / 100;
                $vital->bmi = round($vital->weight / ($heightInMeters * $heightInMeters), 1);
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(PatientQueue::class, 'patient_queue_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
