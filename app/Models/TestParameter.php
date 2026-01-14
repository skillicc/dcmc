<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestParameter extends Model
{
    protected $fillable = [
        'test_id',
        'name',
        'unit',
        'normal_range_text',
        'normal_range_min',
        'normal_range_max',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'normal_range_min' => 'decimal:2',
            'normal_range_max' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
