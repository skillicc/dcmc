<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientQueueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PatientQueue::with(['patient', 'doctor']);

        if ($request->date) {
            $query->whereDate('date', $request->date);
        } else {
            $query->whereDate('date', now());
        }

        if ($request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $queues = $query->orderBy('serial_no')->get();

        return response()->json(['data' => $queues]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['date'] = $validated['date'] ?? now()->toDateString();
        $validated['check_in_time'] = now()->format('H:i:s');

        $queue = PatientQueue::create($validated);

        return response()->json([
            'message' => 'Patient added to queue successfully',
            'data' => $queue->load(['patient', 'doctor']),
        ], 201);
    }

    public function show(PatientQueue $patientQueue): JsonResponse
    {
        return response()->json(['data' => $patientQueue->load(['patient', 'doctor', 'vital'])]);
    }

    public function updateStatus(Request $request, PatientQueue $patientQueue): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Waiting,In Progress,Completed,Cancelled',
        ]);

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'In Progress') {
            $updateData['called_time'] = now()->format('H:i:s');
        } elseif ($validated['status'] === 'Completed') {
            $updateData['completed_time'] = now()->format('H:i:s');
        }

        $patientQueue->update($updateData);

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $patientQueue->load(['patient', 'doctor']),
        ]);
    }

    public function callNext(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $nextPatient = PatientQueue::where('doctor_id', $validated['doctor_id'])
            ->whereDate('date', now())
            ->where('status', 'Waiting')
            ->orderBy('serial_no')
            ->first();

        if (! $nextPatient) {
            return response()->json(['message' => 'No patients waiting in queue'], 404);
        }

        $nextPatient->update([
            'status' => 'In Progress',
            'called_time' => now()->format('H:i:s'),
        ]);

        return response()->json([
            'message' => 'Next patient called',
            'data' => $nextPatient->load(['patient', 'doctor']),
        ]);
    }

    public function todayStats(Request $request): JsonResponse
    {
        $doctorId = $request->doctor_id;
        $date = $request->date ?? now()->toDateString();

        $query = PatientQueue::whereDate('date', $date);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'waiting' => (clone $query)->where('status', 'Waiting')->count(),
            'in_progress' => (clone $query)->where('status', 'In Progress')->count(),
            'completed' => (clone $query)->where('status', 'Completed')->count(),
            'cancelled' => (clone $query)->where('status', 'Cancelled')->count(),
            'current' => (clone $query)->where('status', 'In Progress')->with(['patient', 'doctor'])->first(),
        ];

        return response()->json(['data' => $stats]);
    }

    public function destroy(PatientQueue $patientQueue): JsonResponse
    {
        $patientQueue->delete();

        return response()->json(['message' => 'Queue entry deleted successfully']);
    }
}
