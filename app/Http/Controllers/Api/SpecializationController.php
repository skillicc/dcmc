<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpecializationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Specialization::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:specializations',
        ]);

        $specialization = Specialization::create($validated);

        return response()->json([
            'message' => 'Specialization created successfully',
            'data' => $specialization,
        ], 201);
    }

    public function update(Request $request, Specialization $specialization): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:specializations,name,'.$specialization->id,
        ]);

        $specialization->update($validated);

        return response()->json([
            'message' => 'Specialization updated successfully',
            'data' => $specialization,
        ]);
    }

    public function destroy(Specialization $specialization): JsonResponse
    {
        $specialization->delete();

        return response()->json(['message' => 'Specialization deleted successfully']);
    }
}
