<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionLedger extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'invoice_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'payment_method',
        'payment_reference',
        'processed_by',
        'processed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Get the entity (Doctor or Referral)
    public function getEntityAttribute()
    {
        if ($this->entity_type === 'Doctor') {
            return Doctor::find($this->entity_id);
        }

        return Referral::find($this->entity_id);
    }

    // Scopes
    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('entity_type', 'Doctor')
            ->where('entity_id', $doctorId);
    }

    public function scopeForReferral($query, int $referralId)
    {
        return $query->where('entity_type', 'Referral')
            ->where('entity_id', $referralId);
    }

    public function scopeEarned($query)
    {
        return $query->where('type', 'Earned');
    }

    public function scopePaid($query)
    {
        return $query->where('type', 'Paid');
    }

    public function scopeAdjustments($query)
    {
        return $query->where('type', 'Adjustment');
    }

    // Get type color
    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'Earned' => 'success',
            'Paid' => 'info',
            'Adjustment' => 'warning',
            default => 'grey',
        };
    }

    // Payment methods
    public static function paymentMethods(): array
    {
        return [
            'Cash',
            'Bank Transfer',
            'Cheque',
            'Mobile Banking',
            'Adjustment',
        ];
    }
}
