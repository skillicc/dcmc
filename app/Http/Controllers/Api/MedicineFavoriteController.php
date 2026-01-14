<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicineFavorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicineFavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = MedicineFavorite::query();

        if ($request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->search) {
            $query->where('medicine_name', 'like', "%{$request->search}%");
        }

        $favorites = $query->orderBy('sort_order')->orderBy('medicine_name')->get();

        return response()->json(['data' => $favorites]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'medicine_name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'frequency' => 'nullable|string|max:50',
            'duration' => 'nullable|string|max:50',
            'instructions' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $favorite = MedicineFavorite::create($validated);

        return response()->json([
            'message' => 'Favorite added successfully',
            'data' => $favorite,
        ], 201);
    }

    public function update(Request $request, MedicineFavorite $medicineFavorite): JsonResponse
    {
        $validated = $request->validate([
            'medicine_name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'frequency' => 'nullable|string|max:50',
            'duration' => 'nullable|string|max:50',
            'instructions' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $medicineFavorite->update($validated);

        return response()->json([
            'message' => 'Favorite updated successfully',
            'data' => $medicineFavorite,
        ]);
    }

    public function destroy(MedicineFavorite $medicineFavorite): JsonResponse
    {
        $medicineFavorite->delete();

        return response()->json(['message' => 'Favorite removed successfully']);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'medicines' => 'required|array',
            'medicines.*.medicine_name' => 'required|string|max:255',
            'medicines.*.generic_name' => 'nullable|string|max:255',
            'medicines.*.dosage' => 'nullable|string|max:100',
            'medicines.*.frequency' => 'nullable|string|max:50',
            'medicines.*.duration' => 'nullable|string|max:50',
        ]);

        foreach ($validated['medicines'] as $index => $medicine) {
            MedicineFavorite::updateOrCreate(
                [
                    'doctor_id' => $validated['doctor_id'],
                    'medicine_name' => $medicine['medicine_name'],
                ],
                array_merge($medicine, ['sort_order' => $index])
            );
        }

        return response()->json(['message' => 'Favorites saved successfully']);
    }
}
