<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DoctorSchedule::with('doctor');

        if ($request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }

        if ($request->day) {
            $query->where('day', $request->day);
        }

        $schedules = $query->get();

        return response()->json(['data' => $schedules]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'day' => 'required|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_patients' => 'nullable|integer|min:1',
            'slot_duration' => 'nullable|integer|min:5',
            'is_active' => 'boolean',
        ]);

        $schedule = DoctorSchedule::create($validated);

        return response()->json([
            'message' => 'Schedule created successfully',
            'data' => $schedule->load('doctor'),
        ], 201);
    }

    public function show(DoctorSchedule $doctorSchedule): JsonResponse
    {
        return response()->json(['data' => $doctorSchedule->load('doctor')]);
    }

    public function update(Request $request, DoctorSchedule $doctorSchedule): JsonResponse
    {
        $validated = $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_patients' => 'nullable|integer|min:1',
            'slot_duration' => 'nullable|integer|min:5',
            'is_active' => 'boolean',
        ]);

        $doctorSchedule->update($validated);

        return response()->json([
            'message' => 'Schedule updated successfully',
            'data' => $doctorSchedule->load('doctor'),
        ]);
    }

    public function destroy(DoctorSchedule $doctorSchedule): JsonResponse
    {
        $doctorSchedule->delete();

        return response()->json(['message' => 'Schedule deleted successfully']);
    }

    public function doctorSchedules(Doctor $doctor): JsonResponse
    {
        $schedules = $doctor->schedules()->orderByRaw("FIELD(day, 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday')")->get();

        return response()->json(['data' => $schedules]);
    }

    public function bulkStore(Request $request, Doctor $doctor): JsonResponse
    {
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day' => 'required|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
            'schedules.*.max_patients' => 'nullable|integer|min:1',
            'schedules.*.is_active' => 'boolean',
        ]);

        // Delete existing schedules
        $doctor->schedules()->delete();

        // Create new schedules
        foreach ($validated['schedules'] as $schedule) {
            $doctor->schedules()->create($schedule);
        }

        return response()->json([
            'message' => 'Schedules updated successfully',
            'data' => $doctor->schedules,
        ]);
    }
}
