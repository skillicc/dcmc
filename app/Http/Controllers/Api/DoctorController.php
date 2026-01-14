<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Doctor::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->specialization) {
            $query->where('specialization', $request->specialization);
        }

        $doctors = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json($doctors);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'specialization' => 'required|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $doctor = Doctor::create($validated);

        return response()->json([
            'message' => 'Doctor created successfully',
            'data' => $doctor,
        ], 201);
    }

    public function show(Doctor $doctor): JsonResponse
    {
        return response()->json(['data' => $doctor]);
    }

    public function update(Request $request, Doctor $doctor): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'specialization' => 'required|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $doctor->update($validated);

        return response()->json([
            'message' => 'Doctor updated successfully',
            'data' => $doctor,
        ]);
    }

    public function destroy(Doctor $doctor): JsonResponse
    {
        $doctor->delete();

        return response()->json(['message' => 'Doctor deleted successfully']);
    }
}
