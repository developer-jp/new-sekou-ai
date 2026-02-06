<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiModelController extends Controller
{
    /**
     * Get all active AI models
     */
    public function index(): JsonResponse
    {
        $models = AiModel::active()
            ->ordered()
            ->get([
                'id',
                'name',
                'provider',
                'model_id',
                'description',
                'max_tokens',
                'context_window',
                'supports_vision',
                'supports_streaming',
            ]);

        return response()->json([
            'success' => true,
            'models' => $models,
        ]);
    }
}
