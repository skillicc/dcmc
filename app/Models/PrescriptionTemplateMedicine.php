<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionTemplateMedicine extends Model
{
    protected $fillable = [
        'prescription_template_id',
        'name',
        'dosage',
        'frequency',
        'duration',
        'instructions',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function prescriptionTemplate(): BelongsTo
    {
        return $this->belongsTo(PrescriptionTemplate::class);
    }
}
