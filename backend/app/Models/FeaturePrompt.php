<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturePrompt extends Model
{
    protected $fillable = [
        'feature_id',
        'title',
        'prompt_content',
        'description',
    ];

    /**
     * Get the feature that owns the prompt.
     */
    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
