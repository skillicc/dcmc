<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiseaseDrugInteraction;
use App\Models\DrugInteraction;
use App\Models\Patient;
use App\Models\PatientChronicDisease;
use App\Models\PatientClinicalData;
use App\Models\PatientCurrentMedication;
use App\Models\PatientFamilyDisease;
use App\Models\PatientImmunization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientEmrController extends Controller
{
    public function history(Patient $patient): JsonResponse
    {
        $patient->load([
            'appointments' => function ($query) {
                $query->with('doctor')->latest()->take(20);
            },
            'prescriptions' => function ($query) {
                $query->with('doctor')->latest()->take(20);
            },
            'labReports' => function ($query) {
                $query->with(['test', 'doctor'])->latest()->take(20);
            },
            'invoices' => function ($query) {
                $query->with('doctor')->latest()->take(20);
            },
            'vitals' => function ($query) {
                $query->latest()->take(10);
            },
        ]);

        return response()->json([
            'data' => [
                'patient' => $patient,
                'summary' => [
                    'total_appointments' => $patient->appointments->count(),
                    'total_prescriptions' => $patient->prescriptions->count(),
                    'total_lab_reports' => $patient->labReports->count(),
                    'total_invoices' => $patient->invoices->count(),
                    'total_visits' => $patient->appointments->where('status', 'Completed')->count(),
                ],
            ],
        ]);
    }

    public function appointments(Patient $patient): JsonResponse
    {
        $appointments = $patient->appointments()
            ->with('doctor')
            ->latest()
            ->paginate(10);

        return response()->json($appointments);
    }

    public function prescriptions(Patient $patient): JsonResponse
    {
        $prescriptions = $patient->prescriptions()
            ->with('doctor')
            ->latest()
            ->paginate(10);

        return response()->json($prescriptions);
    }

    public function labReports(Patient $patient): JsonResponse
    {
        $reports = $patient->labReports()
            ->with(['test', 'doctor'])
            ->latest()
            ->paginate(10);

        return response()->json($reports);
    }

    public function invoices(Patient $patient): JsonResponse
    {
        $invoices = $patient->invoices()
            ->with('doctor')
            ->latest()
            ->paginate(10);

        return response()->json($invoices);
    }

    public function vitals(Patient $patient): JsonResponse
    {
        $vitals = $patient->vitals()
            ->with('recordedBy')
            ->latest()
            ->paginate(10);

        return response()->json($vitals);
    }

    public function timeline(Patient $patient): JsonResponse
    {
        $events = collect();

        // Add appointments
        $patient->appointments->each(function ($appt) use ($events) {
            $events->push([
                'type' => 'appointment',
                'date' => $appt->date,
                'title' => 'Appointment with ' . ($appt->doctor->name ?? 'Unknown'),
                'status' => $appt->status,
                'data' => $appt,
            ]);
        });

        // Add prescriptions
        $patient->prescriptions->each(function ($rx) use ($events) {
            $events->push([
                'type' => 'prescription',
                'date' => $rx->date,
                'title' => 'Prescription by ' . ($rx->doctor->name ?? 'Unknown'),
                'status' => null,
                'data' => $rx,
            ]);
        });

        // Add lab reports
        $patient->labReports->each(function ($report) use ($events) {
            $events->push([
                'type' => 'lab_report',
                'date' => $report->sample_date,
                'title' => ($report->test->name ?? 'Lab Test'),
                'status' => $report->status,
                'data' => $report,
            ]);
        });

        // Sort by date descending
        $sorted = $events->sortByDesc('date')->values();

        return response()->json(['data' => $sorted]);
    }

    // ==================== CLINICAL DATA ====================
    public function clinicalData(Patient $patient): JsonResponse
    {
        $data = $patient->clinicalData()
            ->with(['doctor', 'createdBy'])
            ->latest()
            ->paginate(15);

        return response()->json($data);
    }

    public function storeClinicalData(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'nullable|exists:doctors,id',
            'record_date' => 'required|date',
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'data' => 'nullable|array',
            'severity' => 'nullable|in:Mild,Moderate,Severe,Critical',
            'status' => 'nullable|in:Active,Resolved,Chronic,Monitoring',
            'notes' => 'nullable|string',
        ]);

        $validated['patient_id'] = $patient->id;
        $validated['created_by'] = auth()->id();

        $clinicalData = PatientClinicalData::create($validated);

        return response()->json([
            'message' => 'Clinical data added successfully',
            'data' => $clinicalData->load(['doctor', 'createdBy']),
        ], 201);
    }

    public function updateClinicalData(Request $request, Patient $patient, PatientClinicalData $clinicalData): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'nullable|exists:doctors,id',
            'record_date' => 'required|date',
            'category' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'data' => 'nullable|array',
            'severity' => 'nullable|in:Mild,Moderate,Severe,Critical',
            'status' => 'nullable|in:Active,Resolved,Chronic,Monitoring',
            'notes' => 'nullable|string',
        ]);

        $clinicalData->update($validated);

        return response()->json([
            'message' => 'Clinical data updated successfully',
            'data' => $clinicalData->fresh(['doctor', 'createdBy']),
        ]);
    }

    public function destroyClinicalData(Patient $patient, PatientClinicalData $clinicalData): JsonResponse
    {
        $clinicalData->delete();

        return response()->json(['message' => 'Clinical data deleted successfully']);
    }

    public function clinicalDataCategories(): JsonResponse
    {
        return response()->json(['data' => PatientClinicalData::categories()]);
    }

    // ==================== CHRONIC DISEASES ====================
    public function chronicDiseases(Patient $patient): JsonResponse
    {
        $diseases = $patient->chronicDiseases()
            ->with('diagnosedBy')
            ->latest()
            ->get();

        return response()->json(['data' => $diseases]);
    }

    public function storeChronicDisease(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'disease_name' => 'required|string|max:255',
            'icd_code' => 'nullable|string|max:50',
            'diagnosed_date' => 'nullable|date',
            'severity' => 'nullable|in:Mild,Moderate,Severe,Critical',
            'status' => 'nullable|in:Active,Controlled,In Remission,Resolved',
            'current_treatment' => 'nullable|string',
            'notes' => 'nullable|string',
            'show_alert' => 'boolean',
        ]);

        $validated['patient_id'] = $patient->id;
        $validated['diagnosed_by'] = auth()->user()->doctor_id ?? null;

        $disease = PatientChronicDisease::create($validated);

        return response()->json([
            'message' => 'Chronic disease added successfully',
            'data' => $disease->load('diagnosedBy'),
        ], 201);
    }

    public function updateChronicDisease(Request $request, Patient $patient, PatientChronicDisease $chronicDisease): JsonResponse
    {
        $validated = $request->validate([
            'disease_name' => 'required|string|max:255',
            'icd_code' => 'nullable|string|max:50',
            'diagnosed_date' => 'nullable|date',
            'severity' => 'nullable|in:Mild,Moderate,Severe,Critical',
            'status' => 'nullable|in:Active,Controlled,In Remission,Resolved',
            'current_treatment' => 'nullable|string',
            'notes' => 'nullable|string',
            'show_alert' => 'boolean',
        ]);

        $chronicDisease->update($validated);

        return response()->json([
            'message' => 'Chronic disease updated successfully',
            'data' => $chronicDisease->fresh('diagnosedBy'),
        ]);
    }

    public function destroyChronicDisease(Patient $patient, PatientChronicDisease $chronicDisease): JsonResponse
    {
        $chronicDisease->delete();

        return response()->json(['message' => 'Chronic disease deleted successfully']);
    }

    public function commonChronicDiseases(): JsonResponse
    {
        return response()->json(['data' => PatientChronicDisease::commonDiseases()]);
    }

    // ==================== IMMUNIZATIONS ====================
    public function immunizations(Patient $patient): JsonResponse
    {
        $immunizations = $patient->immunizations()
            ->with('administeredBy')
            ->latest()
            ->get();

        return response()->json(['data' => $immunizations]);
    }

    public function storeImmunization(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'vaccine_name' => 'required|string|max:255',
            'vaccine_type' => 'nullable|string|max:100',
            'dose_number' => 'nullable|integer|min:1',
            'total_doses' => 'nullable|integer|min:1',
            'administration_date' => 'required|date',
            'next_dose_date' => 'nullable|date',
            'lot_number' => 'nullable|string|max:50',
            'manufacturer' => 'nullable|string|max:100',
            'site' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:50',
            'status' => 'nullable|in:Completed,In Progress,Not Started',
            'notes' => 'nullable|string',
            'side_effects' => 'nullable|string',
        ]);

        $validated['patient_id'] = $patient->id;
        $validated['administered_by'] = auth()->id();

        $immunization = PatientImmunization::create($validated);

        return response()->json([
            'message' => 'Immunization record added successfully',
            'data' => $immunization->load('administeredBy'),
        ], 201);
    }

    public function updateImmunization(Request $request, Patient $patient, PatientImmunization $immunization): JsonResponse
    {
        $validated = $request->validate([
            'vaccine_name' => 'required|string|max:255',
            'vaccine_type' => 'nullable|string|max:100',
            'dose_number' => 'nullable|integer|min:1',
            'total_doses' => 'nullable|integer|min:1',
            'administration_date' => 'required|date',
            'next_dose_date' => 'nullable|date',
            'lot_number' => 'nullable|string|max:50',
            'manufacturer' => 'nullable|string|max:100',
            'site' => 'nullable|string|max:50',
            'route' => 'nullable|string|max:50',
            'status' => 'nullable|in:Completed,In Progress,Not Started',
            'notes' => 'nullable|string',
            'side_effects' => 'nullable|string',
        ]);

        $immunization->update($validated);

        return response()->json([
            'message' => 'Immunization record updated successfully',
            'data' => $immunization->fresh('administeredBy'),
        ]);
    }

    public function destroyImmunization(Patient $patient, PatientImmunization $immunization): JsonResponse
    {
        $immunization->delete();

        return response()->json(['message' => 'Immunization record deleted successfully']);
    }

    public function commonVaccines(): JsonResponse
    {
        return response()->json([
            'data' => [
                'vaccines' => PatientImmunization::commonVaccines(),
                'routes' => PatientImmunization::routes(),
                'sites' => PatientImmunization::sites(),
            ],
        ]);
    }

    // ==================== FAMILY DISEASES ====================
    public function familyDiseases(Patient $patient): JsonResponse
    {
        $diseases = $patient->familyDiseases()->latest()->get();

        return response()->json(['data' => $diseases]);
    }

    public function storeFamilyDisease(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'disease_name' => 'required|string|max:255',
            'icd_code' => 'nullable|string|max:50',
            'relationship' => 'required|string|max:100',
            'relative_name' => 'nullable|string|max:255',
            'onset_age' => 'nullable|integer|min:0|max:150',
            'is_alive' => 'boolean',
            'cause_of_death' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['patient_id'] = $patient->id;

        $disease = PatientFamilyDisease::create($validated);

        return response()->json([
            'message' => 'Family disease history added successfully',
            'data' => $disease,
        ], 201);
    }

    public function updateFamilyDisease(Request $request, Patient $patient, PatientFamilyDisease $familyDisease): JsonResponse
    {
        $validated = $request->validate([
            'disease_name' => 'required|string|max:255',
            'icd_code' => 'nullable|string|max:50',
            'relationship' => 'required|string|max:100',
            'relative_name' => 'nullable|string|max:255',
            'onset_age' => 'nullable|integer|min:0|max:150',
            'is_alive' => 'boolean',
            'cause_of_death' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $familyDisease->update($validated);

        return response()->json([
            'message' => 'Family disease history updated successfully',
            'data' => $familyDisease->fresh(),
        ]);
    }

    public function destroyFamilyDisease(Patient $patient, PatientFamilyDisease $familyDisease): JsonResponse
    {
        $familyDisease->delete();

        return response()->json(['message' => 'Family disease history deleted successfully']);
    }

    public function familyDiseaseOptions(): JsonResponse
    {
        return response()->json([
            'data' => [
                'diseases' => PatientFamilyDisease::commonDiseases(),
                'relationships' => PatientFamilyDisease::relationships(),
            ],
        ]);
    }

    // ==================== CURRENT MEDICATIONS ====================
    public function currentMedications(Patient $patient): JsonResponse
    {
        $medications = $patient->currentMedications()
            ->with('prescribedBy')
            ->latest()
            ->get();

        return response()->json(['data' => $medications]);
    }

    public function storeCurrentMedication(Request $request, Patient $patient): JsonResponse
    {
        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'frequency' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'prescribed_by' => 'nullable|exists:doctors,id',
            'prescribing_doctor' => 'nullable|string|max:255',
            'reason' => 'nullable|string',
            'status' => 'nullable|in:Active,Discontinued,Completed,On Hold',
            'is_self_prescribed' => 'boolean',
            'pharmacy' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['patient_id'] = $patient->id;

        $medication = PatientCurrentMedication::create($validated);

        return response()->json([
            'message' => 'Current medication added successfully',
            'data' => $medication->load('prescribedBy'),
        ], 201);
    }

    public function updateCurrentMedication(Request $request, Patient $patient, PatientCurrentMedication $medication): JsonResponse
    {
        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'frequency' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:50',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'prescribed_by' => 'nullable|exists:doctors,id',
            'prescribing_doctor' => 'nullable|string|max:255',
            'reason' => 'nullable|string',
            'status' => 'nullable|in:Active,Discontinued,Completed,On Hold',
            'is_self_prescribed' => 'boolean',
            'pharmacy' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $medication->update($validated);

        return response()->json([
            'message' => 'Current medication updated successfully',
            'data' => $medication->fresh('prescribedBy'),
        ]);
    }

    public function destroyCurrentMedication(Patient $patient, PatientCurrentMedication $medication): JsonResponse
    {
        $medication->delete();

        return response()->json(['message' => 'Current medication deleted successfully']);
    }

    public function medicationOptions(): JsonResponse
    {
        return response()->json([
            'data' => [
                'frequencies' => PatientCurrentMedication::frequencies(),
                'routes' => PatientCurrentMedication::routes(),
            ],
        ]);
    }

    // ==================== DRUG INTERACTIONS ====================
    public function checkDrugInteractions(Request $request, Patient $patient): JsonResponse
    {
        $request->validate([
            'drug_name' => 'required|string',
        ]);

        $interactions = $patient->checkDrugInteractions($request->drug_name);

        return response()->json([
            'data' => $interactions,
            'has_interactions' => ! empty($interactions),
        ]);
    }

    // Drug Interactions Master Data
    public function drugInteractions(Request $request): JsonResponse
    {
        $query = DrugInteraction::query();

        if ($request->search) {
            $query->involvingDrug($request->search);
        }

        if ($request->severity) {
            $query->where('severity', $request->severity);
        }

        $interactions = $query->active()->paginate($request->per_page ?? 15);

        return response()->json($interactions);
    }

    public function storeDrugInteraction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'drug_1_name' => 'required|string|max:255',
            'drug_1_generic' => 'nullable|string|max:255',
            'drug_2_name' => 'required|string|max:255',
            'drug_2_generic' => 'nullable|string|max:255',
            'severity' => 'required|in:Minor,Moderate,Major,Contraindicated',
            'interaction_type' => 'nullable|string|max:100',
            'description' => 'required|string',
            'mechanism' => 'nullable|string',
            'clinical_effect' => 'nullable|string',
            'management' => 'nullable|string',
            'onset' => 'nullable|string|max:50',
            'documentation_level' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $interaction = DrugInteraction::create($validated);

        return response()->json([
            'message' => 'Drug interaction added successfully',
            'data' => $interaction,
        ], 201);
    }

    public function updateDrugInteraction(Request $request, DrugInteraction $interaction): JsonResponse
    {
        $validated = $request->validate([
            'drug_1_name' => 'required|string|max:255',
            'drug_1_generic' => 'nullable|string|max:255',
            'drug_2_name' => 'required|string|max:255',
            'drug_2_generic' => 'nullable|string|max:255',
            'severity' => 'required|in:Minor,Moderate,Major,Contraindicated',
            'interaction_type' => 'nullable|string|max:100',
            'description' => 'required|string',
            'mechanism' => 'nullable|string',
            'clinical_effect' => 'nullable|string',
            'management' => 'nullable|string',
            'onset' => 'nullable|string|max:50',
            'documentation_level' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $interaction->update($validated);

        return response()->json([
            'message' => 'Drug interaction updated successfully',
            'data' => $interaction->fresh(),
        ]);
    }

    public function destroyDrugInteraction(DrugInteraction $interaction): JsonResponse
    {
        $interaction->delete();

        return response()->json(['message' => 'Drug interaction deleted successfully']);
    }

    // Disease-Drug Interactions Master Data
    public function diseaseDrugInteractions(Request $request): JsonResponse
    {
        $query = DiseaseDrugInteraction::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->forDisease($request->search)
                    ->orWhere(function ($q) use ($request) {
                        $q->forDrug($request->search);
                    });
            });
        }

        if ($request->severity) {
            $query->where('severity', $request->severity);
        }

        $interactions = $query->active()->paginate($request->per_page ?? 15);

        return response()->json($interactions);
    }

    public function storeDiseaseDrugInteraction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'disease_name' => 'required|string|max:255',
            'icd_code' => 'nullable|string|max:50',
            'drug_name' => 'required|string|max:255',
            'drug_generic' => 'nullable|string|max:255',
            'severity' => 'required|in:Low,Moderate,High,Critical',
            'contraindication_type' => 'required|in:Absolute,Relative,Conditional',
            'description' => 'required|string',
            'clinical_effect' => 'nullable|string',
            'alternative_drugs' => 'nullable|array',
            'management' => 'nullable|string',
            'precautions' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $interaction = DiseaseDrugInteraction::create($validated);

        return response()->json([
            'message' => 'Disease-drug interaction added successfully',
            'data' => $interaction,
        ], 201);
    }

    public function updateDiseaseDrugInteraction(Request $request, DiseaseDrugInteraction $interaction): JsonResponse
    {
        $validated = $request->validate([
            'disease_name' => 'required|string|max:255',
            'icd_code' => 'nullable|string|max:50',
            'drug_name' => 'required|string|max:255',
            'drug_generic' => 'nullable|string|max:255',
            'severity' => 'required|in:Low,Moderate,High,Critical',
            'contraindication_type' => 'required|in:Absolute,Relative,Conditional',
            'description' => 'required|string',
            'clinical_effect' => 'nullable|string',
            'alternative_drugs' => 'nullable|array',
            'management' => 'nullable|string',
            'precautions' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $interaction->update($validated);

        return response()->json([
            'message' => 'Disease-drug interaction updated successfully',
            'data' => $interaction->fresh(),
        ]);
    }

    public function destroyDiseaseDrugInteraction(DiseaseDrugInteraction $interaction): JsonResponse
    {
        $interaction->delete();

        return response()->json(['message' => 'Disease-drug interaction deleted successfully']);
    }

    // ==================== CLINICAL PROFILE SUMMARY ====================
    public function clinicalProfile(Patient $patient): JsonResponse
    {
        $patient->load([
            'activeChronicDiseases',
            'activeMedications',
            'immunizations' => function ($q) {
                $q->latest()->limit(10);
            },
            'familyDiseases',
            'clinicalData' => function ($q) {
                $q->latest()->limit(10);
            },
            'vitals' => function ($q) {
                $q->latest()->limit(5);
            },
        ]);

        return response()->json([
            'data' => [
                'patient' => $patient,
                'summary' => [
                    'chronic_diseases' => $patient->activeChronicDiseases->count(),
                    'active_medications' => $patient->activeMedications->count(),
                    'immunizations' => $patient->immunizations->count(),
                    'family_diseases' => $patient->familyDiseases->count(),
                    'clinical_records' => $patient->clinicalData->count(),
                ],
                'alerts' => $patient->chronicDiseaseAlerts()->get(),
            ],
        ]);
    }

    // ==================== GRAPHICAL DATA ====================
    public function vitalTrends(Patient $patient, Request $request): JsonResponse
    {
        $days = $request->days ?? 30;

        $vitals = $patient->vitals()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($vital) {
                return $vital->created_at->format('Y-m-d');
            })
            ->map(function ($dayVitals) {
                return [
                    'blood_pressure_systolic' => $dayVitals->avg('blood_pressure_systolic'),
                    'blood_pressure_diastolic' => $dayVitals->avg('blood_pressure_diastolic'),
                    'pulse' => $dayVitals->avg('pulse'),
                    'temperature' => $dayVitals->avg('temperature'),
                    'respiratory_rate' => $dayVitals->avg('respiratory_rate'),
                    'oxygen_saturation' => $dayVitals->avg('oxygen_saturation'),
                    'weight' => $dayVitals->avg('weight'),
                    'blood_sugar' => $dayVitals->avg('blood_sugar'),
                ];
            });

        return response()->json(['data' => $vitals]);
    }

    public function labTrends(Patient $patient, Request $request): JsonResponse
    {
        $testId = $request->test_id;
        $days = $request->days ?? 90;

        $query = $patient->labReports()
            ->where('approval_status', 'Approved')
            ->where('created_at', '>=', now()->subDays($days));

        if ($testId) {
            $query->where('test_id', $testId);
        }

        $reports = $query->with('test')
            ->orderBy('sample_date')
            ->get()
            ->groupBy('test_id')
            ->map(function ($testReports) {
                return $testReports->map(function ($report) {
                    return [
                        'date' => $report->sample_date->format('Y-m-d'),
                        'parameters' => $report->parameters,
                        'criticality' => $report->criticality,
                    ];
                });
            });

        return response()->json(['data' => $reports]);
    }
}
