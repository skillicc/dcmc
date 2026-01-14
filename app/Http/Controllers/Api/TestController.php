<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Test::with(['category', 'parameters']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $tests = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json($tests);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'category_id' => 'required|exists:test_categories,id',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|string|max:100',
            'sample_type' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'normal_range' => 'nullable|string',
            'parameters' => 'nullable|array',
            'parameters.*.name' => 'required|string',
            'parameters.*.unit' => 'nullable|string',
            'parameters.*.normal_range_text' => 'nullable|string',
            'parameters.*.normal_range_min' => 'nullable|numeric',
            'parameters.*.normal_range_max' => 'nullable|numeric',
            'parameters.*.is_active' => 'boolean',
        ]);

        $parameters = $validated['parameters'] ?? [];
        unset($validated['parameters']);

        $test = Test::create($validated);

        // Create test parameters
        if (! empty($parameters)) {
            foreach ($parameters as $index => $param) {
                TestParameter::create([
                    'test_id' => $test->id,
                    'name' => $param['name'],
                    'unit' => $param['unit'] ?? null,
                    'normal_range_text' => $param['normal_range_text'] ?? null,
                    'normal_range_min' => $param['normal_range_min'] ?? null,
                    'normal_range_max' => $param['normal_range_max'] ?? null,
                    'sort_order' => $index,
                    'is_active' => $param['is_active'] ?? true,
                ]);
            }
        }

        return response()->json([
            'message' => 'Test created successfully',
            'data' => $test->load(['category', 'parameters']),
        ], 201);
    }

    public function show(Test $test): JsonResponse
    {
        return response()->json(['data' => $test->load(['category', 'parameters'])]);
    }

    public function update(Request $request, Test $test): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'category_id' => 'required|exists:test_categories,id',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|string|max:100',
            'sample_type' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'normal_range' => 'nullable|string',
            'parameters' => 'nullable|array',
            'parameters.*.name' => 'required|string',
            'parameters.*.unit' => 'nullable|string',
            'parameters.*.normal_range_text' => 'nullable|string',
            'parameters.*.normal_range_min' => 'nullable|numeric',
            'parameters.*.normal_range_max' => 'nullable|numeric',
            'parameters.*.is_active' => 'boolean',
        ]);

        $parameters = $validated['parameters'] ?? [];
        unset($validated['parameters']);

        $test->update($validated);

        // Update test parameters
        $test->parameters()->delete();
        if (! empty($parameters)) {
            foreach ($parameters as $index => $param) {
                TestParameter::create([
                    'test_id' => $test->id,
                    'name' => $param['name'],
                    'unit' => $param['unit'] ?? null,
                    'normal_range_text' => $param['normal_range_text'] ?? null,
                    'normal_range_min' => $param['normal_range_min'] ?? null,
                    'normal_range_max' => $param['normal_range_max'] ?? null,
                    'sort_order' => $index,
                    'is_active' => $param['is_active'] ?? true,
                ]);
            }
        }

        return response()->json([
            'message' => 'Test updated successfully',
            'data' => $test->load(['category', 'parameters']),
        ]);
    }

    public function destroy(Test $test): JsonResponse
    {
        $test->delete();

        return response()->json(['message' => 'Test deleted successfully']);
    }
}
