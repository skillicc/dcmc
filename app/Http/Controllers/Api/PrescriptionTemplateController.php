<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrescriptionTemplate;
use App\Models\PrescriptionTemplateMedicine;
use App\Models\PrescriptionTemplateTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PrescriptionTemplate::with(['doctor', 'medicines', 'testsAdvised']);

        if ($request->doctor_id) {
            $query->forDoctor($request->doctor_id);
        }

        if ($request->department) {
            $query->byDepartment($request->department);
        }

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $templates = $query->orderBy('name')->get();

        return response()->json(['data' => $templates]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|in:General,Eye,Dental,ENT,Cardiology,Orthopedics,Gynecology,Pediatrics,Dermatology,Neurology',
            'doctor_id' => 'nullable|exists:doctors,id',
            'chief_complaints' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'medicines' => 'nullable|array',
            'medicines.*.name' => 'required|string',
            'medicines.*.dosage' => 'nullable|string',
            'medicines.*.frequency' => 'nullable|string',
            'medicines.*.duration' => 'nullable|string',
            'medicines.*.instructions' => 'nullable|string',
            'tests_advised' => 'nullable|array',
            'tests_advised.*.test_name' => 'required|string',
            'advice' => 'nullable|string',
            'is_global' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $medicines = $validated['medicines'] ?? [];
        $testsAdvised = $validated['tests_advised'] ?? [];
        unset($validated['medicines'], $validated['tests_advised']);

        $template = PrescriptionTemplate::create($validated);

        // Create template medicines
        if (! empty($medicines)) {
            foreach ($medicines as $index => $medicine) {
                PrescriptionTemplateMedicine::create([
                    'prescription_template_id' => $template->id,
                    'name' => $medicine['name'],
                    'dosage' => $medicine['dosage'] ?? null,
                    'frequency' => $medicine['frequency'] ?? null,
                    'duration' => $medicine['duration'] ?? null,
                    'instructions' => $medicine['instructions'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        }

        // Create template tests
        if (! empty($testsAdvised)) {
            foreach ($testsAdvised as $index => $test) {
                PrescriptionTemplateTest::create([
                    'prescription_template_id' => $template->id,
                    'test_name' => $test['test_name'],
                    'sort_order' => $index,
                ]);
            }
        }

        return response()->json([
            'message' => 'Template created successfully',
            'data' => $template->load(['doctor', 'medicines', 'testsAdvised']),
        ], 201);
    }

    public function show(PrescriptionTemplate $prescriptionTemplate): JsonResponse
    {
        return response()->json(['data' => $prescriptionTemplate->load(['doctor', 'medicines', 'testsAdvised'])]);
    }

    public function update(Request $request, PrescriptionTemplate $prescriptionTemplate): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'department' => 'required|in:General,Eye,Dental,ENT,Cardiology,Orthopedics,Gynecology,Pediatrics,Dermatology,Neurology',
            'chief_complaints' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'medicines' => 'nullable|array',
            'medicines.*.name' => 'required|string',
            'medicines.*.dosage' => 'nullable|string',
            'medicines.*.frequency' => 'nullable|string',
            'medicines.*.duration' => 'nullable|string',
            'medicines.*.instructions' => 'nullable|string',
            'tests_advised' => 'nullable|array',
            'tests_advised.*.test_name' => 'required|string',
            'advice' => 'nullable|string',
            'is_global' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $medicines = $validated['medicines'] ?? [];
        $testsAdvised = $validated['tests_advised'] ?? [];
        unset($validated['medicines'], $validated['tests_advised']);

        $prescriptionTemplate->update($validated);

        // Update medicines
        $prescriptionTemplate->medicines()->delete();
        if (! empty($medicines)) {
            foreach ($medicines as $index => $medicine) {
                PrescriptionTemplateMedicine::create([
                    'prescription_template_id' => $prescriptionTemplate->id,
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
        $prescriptionTemplate->testsAdvised()->delete();
        if (! empty($testsAdvised)) {
            foreach ($testsAdvised as $index => $test) {
                PrescriptionTemplateTest::create([
                    'prescription_template_id' => $prescriptionTemplate->id,
                    'test_name' => $test['test_name'],
                    'sort_order' => $index,
                ]);
            }
        }

        return response()->json([
            'message' => 'Template updated successfully',
            'data' => $prescriptionTemplate->load(['doctor', 'medicines', 'testsAdvised']),
        ]);
    }

    public function destroy(PrescriptionTemplate $prescriptionTemplate): JsonResponse
    {
        $prescriptionTemplate->delete();

        return response()->json(['message' => 'Template deleted successfully']);
    }

    public function departments(): JsonResponse
    {
        $departments = [
            'General',
            'Eye',
            'Dental',
            'ENT',
            'Cardiology',
            'Orthopedics',
            'Gynecology',
            'Pediatrics',
            'Dermatology',
            'Neurology',
        ];

        return response()->json(['data' => $departments]);
    }
}
