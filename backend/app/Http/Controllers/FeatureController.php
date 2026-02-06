<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FeatureController extends Controller
{
    /**
     * Get all features for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $features = Feature::where('user_id', $request->user()->id)
            ->withCount('prompts')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'features' => $features,
        ]);
    }

    /**
     * Create a new feature.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $feature = Feature::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
        ]);

        return response()->json([
            'success' => true,
            'feature' => $feature,
        ], 201);
    }

    /**
     * Get a specific feature with its prompts.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $feature = Feature::where('user_id', $request->user()->id)
            ->with('prompts')
            ->find($id);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => '機能が見つかりません',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'feature' => $feature,
        ]);
    }

    /**
     * Update a feature.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $feature = Feature::where('user_id', $request->user()->id)->find($id);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => '機能が見つかりません',
            ], 404);
        }

        $feature->update([
            'title' => $request->title,
        ]);

        return response()->json([
            'success' => true,
            'feature' => $feature,
        ]);
    }

    /**
     * Delete a feature.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $feature = Feature::where('user_id', $request->user()->id)->find($id);

        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => '機能が見つかりません',
            ], 404);
        }

        $feature->delete();

        return response()->json([
            'success' => true,
            'message' => '機能を削除しました',
        ]);
    }
}
