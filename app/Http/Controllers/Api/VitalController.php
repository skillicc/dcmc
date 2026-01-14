<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VitalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vital::with(['patient', 'queue', 'recordedBy']);

        if ($request->patient_id) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->date) {
            $query->whereDate('date', $request->date);
        }

        $vitals = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json($vitals);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'patient_queue_id' => 'nullable|exists:patient_queues,id',
            'date' => 'nullable|date',
            'blood_pressure' => 'nullable|string|max:20',
            'pulse' => 'nullable|integer|min:0|max:300',
            'temperature' => 'nullable|numeric|min:90|max:110',
            'respiratory_rate' => 'nullable|integer|min:0|max:100',
            'oxygen_saturation' => 'nullable|numeric|min:0|max:100',
            'weight' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:0|max:300',
            'notes' => 'nullable|string',
        ]);

        $validated['date'] = $validated['date'] ?? now()->toDateString();
        $validated['recorded_by'] = auth()->id();

        $vital = Vital::create($validated);

        return response()->json([
            'message' => 'Vitals recorded successfully',
            'data' => $vital->load(['patient', 'recordedBy']),
        ], 201);
    }

    public function show(Vital $vital): JsonResponse
    {
        return response()->json(['data' => $vital->load(['patient', 'queue', 'recordedBy'])]);
    }

    public function update(Request $request, Vital $vital): JsonResponse
    {
        $validated = $request->validate([
            'blood_pressure' => 'nullable|string|max:20',
            'pulse' => 'nullable|integer|min:0|max:300',
            'temperature' => 'nullable|numeric|min:90|max:110',
            'respiratory_rate' => 'nullable|integer|min:0|max:100',
            'oxygen_saturation' => 'nullable|numeric|min:0|max:100',
            'weight' => 'nullable|numeric|min:0|max:500',
            'height' => 'nullable|numeric|min:0|max:300',
            'notes' => 'nullable|string',
        ]);

        $vital->update($validated);

        return response()->json([
            'message' => 'Vitals updated successfully',
            'data' => $vital->load(['patient', 'recordedBy']),
        ]);
    }

    public function patientHistory(int $patientId): JsonResponse
    {
        $vitals = Vital::where('patient_id', $patientId)
            ->with('recordedBy')
            ->latest()
            ->take(10)
            ->get();

        return response()->json(['data' => $vitals]);
    }

    public function destroy(Vital $vital): JsonResponse
    {
        $vital->delete();

        return response()->json(['message' => 'Vital record deleted successfully']);
    }
}
