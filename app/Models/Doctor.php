<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'specialization',
        'qualification',
        'registration_no',
        'commission_percentage',
        'consultation_fee',
        'follow_up_fee',
        'follow_up_days',
        'is_active',
        'address',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'commission_percentage' => 'decimal:2',
            'consultation_fee' => 'decimal:2',
            'follow_up_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function labReports(): HasMany
    {
        return $this->hasMany(LabReport::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(PatientQueue::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function commissionLedgers(): HasMany
    {
        return CommissionLedger::where('entity_type', 'Doctor')
            ->where('entity_id', $this->id)
            ->get();
    }

    // Get pending commission
    public function getPendingCommissionAttribute(): float
    {
        $earned = CommissionLedger::forDoctor($this->id)->earned()->sum('amount');
        $paid = abs(CommissionLedger::forDoctor($this->id)->paid()->sum('amount'));

        return $earned - $paid;
    }

    // Get total commission earned
    public function getTotalCommissionEarnedAttribute(): float
    {
        return CommissionLedger::forDoctor($this->id)->earned()->sum('amount');
    }

    // Get total commission paid
    public function getTotalCommissionPaidAttribute(): float
    {
        return abs(CommissionLedger::forDoctor($this->id)->paid()->sum('amount'));
    }

    // Check if follow-up applies for a patient
    public function isFollowUpForPatient(int $patientId): bool
    {
        $lastInvoice = $this->invoices()
            ->where('patient_id', $patientId)
            ->where('consultation_fee', '>', 0)
            ->latest()
            ->first();

        if (! $lastInvoice) {
            return false;
        }

        return $lastInvoice->date->diffInDays(now()) <= $this->follow_up_days;
    }

    // Get applicable consultation fee for a patient
    public function getConsultationFeeForPatient(int $patientId): float
    {
        return $this->isFollowUpForPatient($patientId)
            ? $this->follow_up_fee
            : $this->consultation_fee;
    }

    // Record commission payment
    public function recordCommissionPayment(float $amount, string $method, ?string $reference = null, ?int $processedBy = null): void
    {
        CommissionLedger::create([
            'entity_type' => 'Doctor',
            'entity_id' => $this->id,
            'type' => 'Paid',
            'amount' => -$amount,
            'balance_after' => $this->pending_commission - $amount,
            'description' => 'Commission payment',
            'payment_method' => $method,
            'payment_reference' => $reference,
            'processed_by' => $processedBy,
            'processed_at' => now(),
        ]);
    }
}
