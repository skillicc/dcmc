<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\LabReport;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = now()->toDateString();

        $stats = [
            [
                'title' => 'Total Patients',
                'value' => Patient::count(),
                'icon' => 'mdi-account-group',
                'color' => 'primary',
            ],
            [
                'title' => "Today's Appointments",
                'value' => Appointment::whereDate('date', $today)->count(),
                'icon' => 'mdi-calendar-check',
                'color' => 'success',
            ],
            [
                'title' => 'Pending Reports',
                'value' => LabReport::where('status', '!=', 'Completed')->count(),
                'icon' => 'mdi-file-clock',
                'color' => 'warning',
            ],
            [
                'title' => "Today's Revenue",
                'value' => '৳'.number_format(Invoice::whereDate('date', $today)->sum('paid'), 0),
                'icon' => 'mdi-currency-bdt',
                'color' => 'info',
            ],
        ];

        $todaySummary = [
            ['label' => 'New Patients', 'value' => Patient::whereDate('created_at', $today)->count(), 'icon' => 'mdi-account-plus', 'color' => 'primary'],
            ['label' => 'Completed Appointments', 'value' => Appointment::whereDate('date', $today)->where('status', 'Completed')->count(), 'icon' => 'mdi-check-circle', 'color' => 'success'],
            ['label' => 'Pending Appointments', 'value' => Appointment::whereDate('date', $today)->where('status', 'Pending')->count(), 'icon' => 'mdi-clock', 'color' => 'warning'],
            ['label' => 'Lab Reports Done', 'value' => LabReport::whereDate('updated_at', $today)->where('status', 'Completed')->count(), 'icon' => 'mdi-file-check', 'color' => 'info'],
            ['label' => 'Invoices Generated', 'value' => Invoice::whereDate('date', $today)->count(), 'icon' => 'mdi-receipt', 'color' => 'secondary'],
        ];

        $recentPatients = Patient::latest()->take(5)->get(['id', 'name', 'phone', 'created_at'])->map(function ($patient) {
            return [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'date' => $patient->created_at->format('Y-m-d'),
            ];
        });

        $todayAppointments = Appointment::with(['patient:id,name', 'doctor:id,name'])
            ->whereDate('date', $today)
            ->take(5)
            ->get()
            ->map(function ($appt) {
                return [
                    'id' => $appt->id,
                    'patient' => $appt->patient->name,
                    'doctor' => $appt->doctor->name,
                    'time' => $appt->time?->format('H:i'),
                    'status' => $appt->status,
                    'statusColor' => match ($appt->status) {
                        'Pending' => 'warning',
                        'Confirmed' => 'info',
                        'Completed' => 'success',
                        'Cancelled' => 'error',
                        default => 'grey',
                    },
                ];
            });

        $pendingReports = LabReport::with(['patient:id,name', 'test:id,name'])
            ->where('status', '!=', 'Completed')
            ->take(5)
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->report_id,
                    'patient' => $report->patient->name,
                    'test' => $report->test->name,
                    'date' => $report->sample_date->format('Y-m-d'),
                    'status' => $report->status,
                    'statusColor' => match ($report->status) {
                        'Pending' => 'warning',
                        'Sample Collected' => 'info',
                        'Processing' => 'primary',
                        default => 'grey',
                    },
                ];
            });

        // Daily Earning Summary
        $dailyEarnings = $this->getDailyEarningSummary();

        // Patient Statistics
        $patientStats = $this->getPatientStatistics();

        return response()->json([
            'stats' => $stats,
            'todaySummary' => $todaySummary,
            'recentPatients' => $recentPatients,
            'todayAppointments' => $todayAppointments,
            'pendingReports' => $pendingReports,
            'dailyEarnings' => $dailyEarnings,
            'patientStats' => $patientStats,
        ]);
    }

    private function getDailyEarningSummary(): array
    {
        $today = now()->toDateString();

        // Today's collections by payment method
        $todayCollections = Payment::whereDate('date', $today)
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();

        // Today's invoices summary
        $todayInvoices = Invoice::whereDate('date', $today)
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(total) as total_billed,
                SUM(paid) as total_collected,
                SUM(due) as total_due,
                SUM(discount) as total_discount
            ')
            ->first();

        // Week summary
        $weekStart = now()->startOfWeek()->toDateString();
        $weekCollections = Payment::whereDate('date', '>=', $weekStart)
            ->selectRaw('DATE(date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Month summary
        $monthStart = now()->startOfMonth()->toDateString();
        $monthTotal = Payment::whereDate('date', '>=', $monthStart)->sum('amount');

        // Hourly collections today
        $hourlyCollections = Payment::whereDate('date', $today)
            ->selectRaw('HOUR(date) as hour, SUM(amount) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return [
            'today' => [
                'by_method' => $todayCollections,
                'total_collected' => $todayCollections->sum('total'),
                'total_transactions' => $todayCollections->sum('count'),
                'invoices' => $todayInvoices,
            ],
            'week' => [
                'daily' => $weekCollections,
                'total' => $weekCollections->sum('total'),
            ],
            'month_total' => $monthTotal,
            'hourly' => $hourlyCollections,
        ];
    }

    private function getPatientStatistics(): array
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $todayPatients = Patient::whereDate('created_at', $today)->count();
        $weekPatients = Patient::whereDate('created_at', '>=', $weekStart)->count();
        $monthPatients = Patient::whereDate('created_at', '>=', $monthStart)->count();
        $totalPatients = Patient::count();

        return [
            'today' => $todayPatients,
            'week' => $weekPatients,
            'month' => $monthPatients,
            'total' => $totalPatients,
            'todayPercentage' => $totalPatients > 0 ? min(round(($todayPatients / $totalPatients) * 100 * 10), 100) : 0,
        ];
    }
}
