<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionTemplateTest extends Model
{
    protected $fillable = [
        'prescription_template_id',
        'test_name',
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
