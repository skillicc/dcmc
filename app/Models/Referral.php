<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'doctor_id',
        'phone',
        'email',
        'discount_type',
        'discount_value',
        'commission_type',
        'commission_value',
        'total_referrals',
        'total_revenue',
        'total_commission_earned',
        'total_commission_paid',
        'is_active',
        'valid_from',
        'valid_until',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'total_revenue' => 'decimal:2',
            'total_commission_earned' => 'decimal:2',
            'total_commission_paid' => 'decimal:2',
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($referral) {
            if (empty($referral->code)) {
                $referral->code = 'REF' . strtoupper(substr(md5(uniqid()), 0, 6));
            }
        });
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function commissionLedgers(): HasMany
    {
        return CommissionLedger::where('entity_type', 'Referral')
            ->where('entity_id', $this->id)
            ->get();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_from')
                ->orWhere('valid_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('valid_until')
                ->orWhere('valid_until', '>=', now());
        });
    }

    // Calculate discount for an amount
    public function calculateDiscount(float $amount): float
    {
        if ($this->discount_type === 'Fixed') {
            return min($this->discount_value, $amount);
        }

        return round($amount * ($this->discount_value / 100), 2);
    }

    // Calculate commission for an amount
    public function calculateCommission(float $amount): float
    {
        if ($this->commission_type === 'Fixed') {
            return $this->commission_value;
        }

        return round($amount * ($this->commission_value / 100), 2);
    }

    // Get pending commission
    public function getPendingCommissionAttribute(): float
    {
        return $this->total_commission_earned - $this->total_commission_paid;
    }

    // Update stats
    public function updateStats(): void
    {
        $this->total_referrals = $this->invoices()->count();
        $this->total_revenue = $this->invoices()->sum('total');
        $this->save();
    }

    // Record commission earned
    public function recordCommissionEarned(Invoice $invoice, float $amount): void
    {
        $this->total_commission_earned += $amount;
        $this->save();

        CommissionLedger::create([
            'entity_type' => 'Referral',
            'entity_id' => $this->id,
            'invoice_id' => $invoice->id,
            'type' => 'Earned',
            'amount' => $amount,
            'balance_after' => $this->pending_commission,
            'description' => "Commission for Invoice #{$invoice->invoice_no}",
        ]);
    }

    // Record commission payment
    public function recordCommissionPayment(float $amount, string $method, ?string $reference = null, ?int $processedBy = null): void
    {
        $this->total_commission_paid += $amount;
        $this->save();

        CommissionLedger::create([
            'entity_type' => 'Referral',
            'entity_id' => $this->id,
            'type' => 'Paid',
            'amount' => -$amount,
            'balance_after' => $this->pending_commission,
            'description' => 'Commission payment',
            'payment_method' => $method,
            'payment_reference' => $reference,
            'processed_by' => $processedBy,
            'processed_at' => now(),
        ]);
    }

    // Referral types
    public static function types(): array
    {
        return ['Doctor', 'Agent', 'Patient', 'Staff', 'Other'];
    }
}
