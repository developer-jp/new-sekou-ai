<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\FeaturePrompt;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeaturePromptController extends Controller
{
    /**
     * Create a new prompt for a feature.
     */
    public function store(Request $request, int $featureId): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'prompt_content' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $feature = Feature::where('user_id', $request->user()->id)->find($featureId);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => '機能が見つかりません',
            ], 404);
        }

        $prompt = FeaturePrompt::create([
            'feature_id' => $feature->id,
            'title' => $request->title,
            'prompt_content' => $request->prompt_content,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'prompt' => $prompt,
        ], 201);
    }

    /**
     * Update a prompt.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'prompt_content' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $prompt = FeaturePrompt::whereHas('feature', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->find($id);

        if (!$prompt) {
            return response()->json([
                'success' => false,
                'message' => 'プロンプトが見つかりません',
            ], 404);
        }

        $prompt->update([
            'title' => $request->title,
            'prompt_content' => $request->prompt_content,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'prompt' => $prompt,
        ]);
    }

    /**
     * Delete a prompt.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $prompt = FeaturePrompt::whereHas('feature', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })->find($id);

        if (!$prompt) {
            return response()->json([
                'success' => false,
                'message' => 'プロンプトが見つかりません',
            ], 404);
        }

        $prompt->delete();

        return response()->json([
            'success' => true,
            'message' => 'プロンプトを削除しました',
        ]);
    }
}
