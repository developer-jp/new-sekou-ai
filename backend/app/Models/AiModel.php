<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    use HasFactory;

    protected $table = 'ai_models';

    protected $fillable = [
        'name',
        'provider',
        'model_id',
        'description',
        'max_tokens',
        'context_window',
        'input_price',
        'output_price',
        'is_active',
        'supports_vision',
        'supports_function_calling',
        'supports_streaming',
        'capabilities',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supports_vision' => 'boolean',
        'supports_function_calling' => 'boolean',
        'supports_streaming' => 'boolean',
        'capabilities' => 'array',
        'input_price' => 'decimal:6',
        'output_price' => 'decimal:6',
    ];

    /**
     * Scope to get only active models
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
