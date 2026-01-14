<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = TestCategory::withCount('tests')->latest()->get();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = TestCategory::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, TestCategory $testCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $testCategory->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $testCategory,
        ]);
    }

    public function destroy(TestCategory $testCategory): JsonResponse
    {
        $testCategory->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
