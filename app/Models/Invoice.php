<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_no',
        'patient_id',
        'doctor_id',
        'date',
        'consultation_fee',
        'is_follow_up',
        'subtotal',
        'discount',
        'discount_type',
        'discount_value',
        'discount_reason',
        'referral_id',
        'referral_discount',
        'doctor_commission',
        'commission_paid',
        'commission_paid_at',
        'tax_amount',
        'tax_percentage',
        'total',
        'paid',
        'due',
        'last_payment_date',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'consultation_fee' => 'decimal:2',
            'is_follow_up' => 'boolean',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'referral_discount' => 'decimal:2',
            'doctor_commission' => 'decimal:2',
            'commission_paid' => 'boolean',
            'commission_paid_at' => 'datetime',
            'tax_amount' => 'decimal:2',
            'tax_percentage' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
            'due' => 'decimal:2',
            'last_payment_date' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($invoice) {
            $invoice->invoice_no = 'INV' . str_pad((static::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
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

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function commissionLedgers(): HasMany
    {
        return $this->hasMany(CommissionLedger::class);
    }

    // Scopes
    public function scopeDue($query)
    {
        return $query->where('status', '!=', 'Paid')->where('due', '>', 0);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'Paid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    public function scopeUnpaidCommission($query)
    {
        return $query->where('commission_paid', false)
            ->where('doctor_commission', '>', 0);
    }

    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->paid = $totalPaid;
        $this->due = $this->total - $totalPaid;
        $this->last_payment_date = $this->payments()->latest()->first()?->date;

        if ($this->due <= 0) {
            $this->status = 'Paid';
            $this->due = 0;
        } elseif ($totalPaid > 0) {
            $this->status = 'Partial';
        } else {
            $this->status = 'Unpaid';
        }

        $this->save();
    }

    // Calculate doctor commission
    public function calculateDoctorCommission(): float
    {
        if (! $this->doctor_id || ! $this->doctor) {
            return 0;
        }

        $commissionable = $this->subtotal + $this->consultation_fee;

        return round($commissionable * ($this->doctor->commission_percentage / 100), 2);
    }

    // Mark commission as paid
    public function markCommissionPaid(int $processedBy): void
    {
        if ($this->commission_paid || $this->doctor_commission <= 0) {
            return;
        }

        $this->commission_paid = true;
        $this->commission_paid_at = now();
        $this->save();

        // Record in ledger
        CommissionLedger::create([
            'entity_type' => 'Doctor',
            'entity_id' => $this->doctor_id,
            'invoice_id' => $this->id,
            'type' => 'Earned',
            'amount' => $this->doctor_commission,
            'description' => "Commission for Invoice #{$this->invoice_no}",
            'processed_by' => $processedBy,
            'processed_at' => now(),
        ]);
    }

    // Check if overdue
    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date < now() && $this->status !== 'Paid';
    }

    // Get status color
    public function getStatusColorAttribute(): string
    {
        if ($this->is_overdue) {
            return 'error';
        }

        return match ($this->status) {
            'Paid' => 'success',
            'Partial' => 'warning',
            'Unpaid' => 'error',
            default => 'grey',
        };
    }
}
