<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'patient_id',
        'doctor_id',
        'date',
        'vitals_bp',
        'vitals_pulse',
        'vitals_temp',
        'vitals_weight',
        'vitals_height',
        'vitals_spo2',
        'vitals_rbs',
        'vitals_respiratory_rate',
        'chief_complaints',
        'diagnosis',
        'advice',
        'follow_up_date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'follow_up_date' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($prescription) {
            $prescription->prescription_id = 'RX' . str_pad((static::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function medicines(): HasMany
    {
        return $this->hasMany(PrescriptionMedicine::class)->orderBy('sort_order');
    }

    public function testsAdvised(): HasMany
    {
        return $this->hasMany(PrescriptionTest::class);
    }
}
