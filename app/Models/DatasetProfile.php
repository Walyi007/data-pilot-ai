<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatasetProfile extends Model
{
    protected $fillable = [
        'dataset_id',
        'metrics_json',
        'quality_score',
        'generated_at',
    ];

    protected $casts = [
        'metrics_json' => 'array',
        'quality_score' => 'float',
        'generated_at' => 'datetime',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }
}