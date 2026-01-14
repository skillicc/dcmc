<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LabReport::with(['patient', 'test', 'doctor', 'collectedBy', 'receivedBy', 'resultEnteredBy', 'approvedBy', 'parameters']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('report_id', 'like', "%{$request->search}%")
                    ->orWhere('specimen_id', 'like', "%{$request->search}%")
                    ->orWhere('barcode', 'like', "%{$request->search}%")
                    ->orWhereHas('patient', function ($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%")
                            ->orWhere('phone', 'like', "%{$request->search}%");
                    });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->approval_status) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->criticality) {
            $query->where('criticality', $request->criticality);
        }

        if ($request->date) {
            $query->whereDate('sample_date', $request->date);
        }

        // Filter for awaiting approval
        if ($request->awaiting_approval) {
            $query->awaitingApproval();
        }

        // Filter for critical reports
        if ($request->critical_only) {
            $query->critical();
        }

        $reports = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json($reports);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'test_id' => 'required|exists:tests,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'specimen_type' => 'nullable|string',
            'sample_date' => 'required|date',
            'delivery_date' => 'nullable|date',
            'status' => 'in:Pending,Sample Collected,Received at Lab,Processing,Completed,Delivered',
            'parameters' => 'nullable|array',
            'remarks' => 'nullable|string',
        ]);

        $report = LabReport::create($validated);

        return response()->json([
            'message' => 'Lab report created successfully',
            'data' => $report->load(['patient', 'test', 'doctor', 'parameters']),
        ], 201);
    }

    public function show(LabReport $labReport): JsonResponse
    {
        return response()->json([
            'data' => $labReport->load(['patient', 'test', 'doctor', 'collectedBy', 'receivedBy', 'resultEnteredBy', 'approvedBy', 'parameters']),
        ]);
    }

    public function update(Request $request, LabReport $labReport): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'in:Pending,Sample Collected,Received at Lab,Processing,Completed,Delivered',
            'parameters' => 'nullable|array',
            'remarks' => 'nullable|string',
            'delivery_date' => 'nullable|date',
            'criticality' => 'in:Normal,Abnormal,Critical',
        ]);

        $labReport->update($validated);

        return response()->json([
            'message' => 'Lab report updated successfully',
            'data' => $labReport->load(['patient', 'test', 'doctor', 'parameters']),
        ]);
    }

    public function destroy(LabReport $labReport): JsonResponse
    {
        $labReport->delete();

        return response()->json(['message' => 'Lab report deleted successfully']);
    }

    // Specimen Collection
    public function collectSpecimen(Request $request, LabReport $labReport): JsonResponse
    {
        $request->validate([
            'specimen_type' => 'nullable|string',
        ]);

        $labReport->collectSpecimen(
            auth()->id(),
            $request->specimen_type
        );

        return response()->json([
            'message' => 'Specimen collected successfully',
            'data' => $labReport->fresh(['patient', 'test', 'collectedBy', 'parameters']),
        ]);
    }

    // Receive at Lab
    public function receiveAtLab(LabReport $labReport): JsonResponse
    {
        $labReport->receiveAtLab(auth()->id());

        return response()->json([
            'message' => 'Specimen received at lab',
            'data' => $labReport->fresh(['patient', 'test', 'receivedBy', 'parameters']),
        ]);
    }

    // Enter Result
    public function enterResult(Request $request, LabReport $labReport): JsonResponse
    {
        $validated = $request->validate([
            'parameters' => 'required|array',
            'criticality' => 'required|in:Normal,Abnormal,Critical',
            'remarks' => 'nullable|string',
        ]);

        $labReport->enterResult(
            $validated['parameters'],
            auth()->id(),
            $validated['criticality']
        );

        if ($validated['remarks'] ?? null) {
            $labReport->update(['remarks' => $validated['remarks']]);
        }

        return response()->json([
            'message' => 'Result entered successfully',
            'data' => $labReport->fresh(['patient', 'test', 'resultEnteredBy', 'parameters']),
        ]);
    }

    // Approve Result
    public function approve(Request $request, LabReport $labReport): JsonResponse
    {
        $request->validate([
            'remarks' => 'nullable|string',
        ]);

        $labReport->approve(auth()->id(), $request->remarks);

        return response()->json([
            'message' => 'Result approved successfully',
            'data' => $labReport->fresh(['patient', 'test', 'approvedBy', 'parameters']),
        ]);
    }

    // Reject Result
    public function reject(Request $request, LabReport $labReport): JsonResponse
    {
        $request->validate([
            'remarks' => 'required|string',
        ]);

        $labReport->reject(auth()->id(), $request->remarks);

        return response()->json([
            'message' => 'Result rejected',
            'data' => $labReport->fresh(['patient', 'test', 'approvedBy', 'parameters']),
        ]);
    }

    // Mark Delivered
    public function markDelivered(LabReport $labReport): JsonResponse
    {
        $labReport->markDelivered();

        return response()->json([
            'message' => 'Report marked as delivered',
            'data' => $labReport->fresh(['patient', 'test', 'parameters']),
        ]);
    }

    // Send SMS Notification
    public function sendSms(LabReport $labReport): JsonResponse
    {
        // Here you would integrate with an SMS gateway
        // For now, we just mark it as sent
        $patient = $labReport->patient;

        if (! $patient->phone) {
            return response()->json([
                'message' => 'Patient does not have a phone number',
            ], 400);
        }

        // TODO: Integrate with SMS gateway (e.g., Twilio, local gateway)
        // $message = "Your lab report ({$labReport->report_id}) for {$labReport->test->name} is ready. Please collect from the diagnostic center.";
        // SmsGateway::send($patient->phone, $message);

        $labReport->markSmsSent();

        return response()->json([
            'message' => 'SMS notification sent successfully',
            'data' => $labReport->fresh(['patient', 'test', 'parameters']),
        ]);
    }

    // Get LIS Statistics
    public function stats(): JsonResponse
    {
        $today = now()->toDateString();

        return response()->json([
            'data' => [
                'pending_collection' => LabReport::where('status', 'Pending')->count(),
                'sample_collected' => LabReport::where('status', 'Sample Collected')->count(),
                'received_at_lab' => LabReport::where('status', 'Received at Lab')->count(),
                'processing' => LabReport::where('status', 'Processing')->count(),
                'awaiting_approval' => LabReport::awaitingApproval()->count(),
                'completed_today' => LabReport::whereDate('approved_at', $today)->where('approval_status', 'Approved')->count(),
                'critical_reports' => LabReport::critical()->where('approval_status', 'Approved')->whereDate('approved_at', $today)->count(),
                'abnormal_reports' => LabReport::abnormal()->where('approval_status', 'Approved')->whereDate('approved_at', $today)->count(),
                'pending_sms' => LabReport::where('approval_status', 'Approved')->where('sms_sent', false)->count(),
            ],
        ]);
    }

    // Get Specimen Types
    public function specimenTypes(): JsonResponse
    {
        return response()->json([
            'data' => [
                'Blood',
                'Serum',
                'Plasma',
                'Urine',
                'Stool',
                'Sputum',
                'CSF',
                'Swab',
                'Tissue',
                'Fluid',
                'Other',
            ],
        ]);
    }

    // Bulk receive at lab
    public function bulkReceive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_ids' => 'required|array',
            'report_ids.*' => 'exists:lab_reports,id',
        ]);

        $reports = LabReport::whereIn('id', $validated['report_ids'])
            ->where('status', 'Sample Collected')
            ->get();

        foreach ($reports as $report) {
            $report->receiveAtLab(auth()->id());
        }

        return response()->json([
            'message' => $reports->count() . ' specimens received at lab',
        ]);
    }
}
