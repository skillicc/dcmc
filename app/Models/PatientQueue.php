<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PatientQueue extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_no',
        'patient_id',
        'doctor_id',
        'date',
        'serial_no',
        'status',
        'check_in_time',
        'called_time',
        'completed_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_time' => 'datetime:H:i',
            'called_time' => 'datetime:H:i',
            'completed_time' => 'datetime:H:i',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($queue) {
            $date = $queue->date ?? now()->toDateString();
            $lastSerial = static::where('doctor_id', $queue->doctor_id)
                ->whereDate('date', $date)
                ->max('serial_no') ?? 0;

            $queue->serial_no = $lastSerial + 1;
            $queue->token_no = 'T' . date('ymd', strtotime($date)) . '-' . str_pad($queue->serial_no, 3, '0', STR_PAD_LEFT);
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

    public function vital(): HasOne
    {
        return $this->hasOne(Vital::class);
    }
}
