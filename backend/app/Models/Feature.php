<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    protected $fillable = [
        'user_id',
        'title',
    ];

    /**
     * Get the user that owns the feature.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the prompts for the feature.
     */
    public function prompts(): HasMany
    {
        return $this->hasMany(FeaturePrompt::class);
    }
}
