<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dataset extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'original_filename',
        'storage_path',
        'file_type',
        'row_count',
        'column_count',
        'status',
    ];

    protected $casts = [
        'row_count' => 'integer',
        'column_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(DatasetProfile::class);
    }
}