<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'medicine_name',
        'generic_name',
        'dosage',
        'frequency',
        'duration',
        'instructions',
        'sort_order',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
