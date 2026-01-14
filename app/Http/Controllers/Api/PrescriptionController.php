<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\PrescriptionMedicine;
use App\Models\PrescriptionTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Prescription::with(['patient', 'doctor', 'medicines', 'testsAdvised']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('prescription_id', 'like', "%{$request->search}%")
                    ->orWhereHas('patient', function ($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%")
                            ->orWhere('phone', 'like', "%{$request->search}%");
                    });
            });
        }

        if ($request->date) {
            $query->whereDate('date', $request->date);
        }

        $prescriptions = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json($prescriptions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date',
            'vitals' => 'nullable|array',
            'vitals.bp' => 'nullable|string',
            'vitals.pulse' => 'nullable|string',
            'vitals.temp' => 'nullable|string',
            'vitals.weight' => 'nullable|string',
            'vitals.height' => 'nullable|string',
            'vitals.spo2' => 'nullable|string',
            'vitals.rbs' => 'nullable|string',
            'vitals.respiratory_rate' => 'nullable|string',
            'chief_complaints' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'medicines' => 'nullable|array',
            'medicines.*.name' => 'required|string',
            'medicines.*.dosage' => 'nullable|string',
            'medicines.*.frequency' => 'nullable|string',
            'medicines.*.duration' => 'nullable|string',
            'medicines.*.instructions' => 'nullable|string',
            'tests_advised' => 'nullable|array',
            'tests_advised.*.test_id' => 'nullable|exists:tests,id',
            'tests_advised.*.test_name' => 'required|string',
            'tests_advised.*.notes' => 'nullable|string',
            'advice' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
        ]);

        // Map vitals to individual columns
        $vitals = $validated['vitals'] ?? [];
        $prescriptionData = [
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'date' => $validated['date'],
            'vitals_bp' => $vitals['bp'] ?? null,
            'vitals_pulse' => $vitals['pulse'] ?? null,
            'vitals_temp' => $vitals['temp'] ?? null,
            'vitals_weight' => $vitals['weight'] ?? null,
            'vitals_height' => $vitals['height'] ?? null,
            'vitals_spo2' => $vitals['spo2'] ?? null,
            'vitals_rbs' => $vitals['rbs'] ?? null,
            'vitals_respiratory_rate' => $vitals['respiratory_rate'] ?? null,
            'chief_complaints' => $validated['chief_complaints'] ?? null,
            'diagnosis' => $validated['diagnosis'] ?? null,
            'advice' => $validated['advice'] ?? null,
            'follow_up_date' => $validated['follow_up_date'] ?? null,
        ];

        $prescription = Prescription::create($prescriptionData);

        // Create prescription medicines
        if (! empty($validated['medicines'])) {
            foreach ($validated['medicines'] as $index => $medicine) {
                PrescriptionMedicine::create([
                    'prescription_id' => $prescription->id,
                    'name' => $medicine['name'],
                    'dosage' => $medicine['dosage'] ?? null,
                    'frequency' => $medicine['frequency'] ?? null,
                    'duration' => $medicine['duration'] ?? null,
                    'instructions' => $medicine['instructions'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        }

        // Create prescription tests
        if (! empty($validated['tests_advised'])) {
            foreach ($validated['tests_advised'] as $test) {
                PrescriptionTest::create([
                    'prescription_id' => $prescription->id,
                    'test_id' => $test['test_id'] ?? null,
                    'test_name' => $test['test_name'],
                    'notes' => $test['notes'] ?? null,
                ]);
            }
        }

        return response()->json([
            'message' => 'Prescription created successfully',
            'data' => $prescription->load(['patient', 'doctor', 'medicines', 'testsAdvised']),
        ], 201);
    }

    public function show(Prescription $prescription): JsonResponse
    {
        return response()->json(['data' => $prescription->load(['patient', 'doctor', 'medicines', 'testsAdvised'])]);
    }

    public function update(Request $request, Prescription $prescription): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date',
            'vitals' => 'nullable|array',
            'chief_complaints' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'medicines' => 'nullable|array',
            'medicines.*.name' => 'required|string',
            'medicines.*.dosage' => 'nullable|string',
            'medicines.*.frequency' => 'nullable|string',
            'medicines.*.duration' => 'nullable|string',
            'medicines.*.instructions' => 'nullable|string',
            'tests_advised' => 'nullable|array',
            'tests_advised.*.test_id' => 'nullable|exists:tests,id',
            'tests_advised.*.test_name' => 'required|string',
            'tests_advised.*.notes' => 'nullable|string',
            'advice' => 'nullable|string',
            'follow_up_date' => 'nullable|date',
        ]);

        $vitals = $validated['vitals'] ?? [];
        $prescription->update([
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'date' => $validated['date'],
            'vitals_bp' => $vitals['bp'] ?? null,
            'vitals_pulse' => $vitals['pulse'] ?? null,
            'vitals_temp' => $vitals['temp'] ?? null,
            'vitals_weight' => $vitals['weight'] ?? null,
            'vitals_height' => $vitals['height'] ?? null,
            'vitals_spo2' => $vitals['spo2'] ?? null,
            'vitals_rbs' => $vitals['rbs'] ?? null,
            'vitals_respiratory_rate' => $vitals['respiratory_rate'] ?? null,
            'chief_complaints' => $validated['chief_complaints'] ?? null,
            'diagnosis' => $validated['diagnosis'] ?? null,
            'advice' => $validated['advice'] ?? null,
            'follow_up_date' => $validated['follow_up_date'] ?? null,
        ]);

        // Update medicines
        $prescription->medicines()->delete();
        if (! empty($validated['medicines'])) {
            foreach ($validated['medicines'] as $index => $medicine) {
                PrescriptionMedicine::create([
                    'prescription_id' => $prescription->id,
                    'name' => $medicine['name'],
                    'dosage' => $medicine['dosage'] ?? null,
                    'frequency' => $medicine['frequency'] ?? null,
                    'duration' => $medicine['duration'] ?? null,
                    'instructions' => $medicine['instructions'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        }

        // Update tests
        $prescription->testsAdvised()->delete();
        if (! empty($validated['tests_advised'])) {
            foreach ($validated['tests_advised'] as $test) {
                PrescriptionTest::create([
                    'prescription_id' => $prescription->id,
                    'test_id' => $test['test_id'] ?? null,
                    'test_name' => $test['test_name'],
                    'notes' => $test['notes'] ?? null,
                ]);
            }
        }

        return response()->json([
            'message' => 'Prescription updated successfully',
            'data' => $prescription->load(['patient', 'doctor', 'medicines', 'testsAdvised']),
        ]);
    }

    public function destroy(Prescription $prescription): JsonResponse
    {
        $prescription->delete();

        return response()->json(['message' => 'Prescription deleted successfully']);
    }
}
