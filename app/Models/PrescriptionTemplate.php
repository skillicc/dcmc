<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrescriptionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'department',
        'doctor_id',
        'chief_complaints',
        'diagnosis',
        'advice',
        'is_global',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_global' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopeForDoctor($query, $doctorId)
    {
        return $query->where(function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId)
                ->orWhere('is_global', true);
        })->where('is_active', true);
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function medicines(): HasMany
    {
        return $this->hasMany(PrescriptionTemplateMedicine::class)->orderBy('sort_order');
    }

    public function testsAdvised(): HasMany
    {
        return $this->hasMany(PrescriptionTemplateTest::class)->orderBy('sort_order');
    }
}
