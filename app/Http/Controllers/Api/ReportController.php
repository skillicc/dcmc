<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function financial(Request $request): JsonResponse
    {
        $fromDate = $request->from_date ?? now()->startOfMonth()->toDateString();
        $toDate = $request->to_date ?? now()->toDateString();
        $type = $request->type ?? 'daily';

        $summary = [
            'totalRevenue' => Invoice::whereBetween('date', [$fromDate, $toDate])->sum('total'),
            'totalCollected' => Invoice::whereBetween('date', [$fromDate, $toDate])->sum('paid'),
            'totalDue' => Invoice::whereBetween('date', [$fromDate, $toDate])->sum('due'),
            'totalInvoices' => Invoice::whereBetween('date', [$fromDate, $toDate])->count(),
        ];

        $data = match ($type) {
            'daily' => $this->getDailyReport($fromDate, $toDate),
            'monthly' => $this->getMonthlyReport($fromDate, $toDate),
            'doctor' => $this->getDoctorReport($fromDate, $toDate),
            'test' => $this->getTestReport($fromDate, $toDate),
            default => $this->getDailyReport($fromDate, $toDate),
        };

        return response()->json([
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    private function getDailyReport(string $fromDate, string $toDate): array
    {
        return Invoice::select(
            DB::raw('DATE(date) as date'),
            DB::raw('COUNT(*) as invoices'),
            DB::raw('SUM(total) as total'),
            DB::raw('SUM(paid) as paid'),
            DB::raw('SUM(due) as due')
        )
            ->whereBetween('date', [$fromDate, $toDate])
            ->groupBy(DB::raw('DATE(date)'))
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }

    private function getMonthlyReport(string $fromDate, string $toDate): array
    {
        return Invoice::select(
            DB::raw("DATE_FORMAT(date, '%Y-%m') as date"),
            DB::raw('COUNT(*) as invoices'),
            DB::raw('SUM(total) as total'),
            DB::raw('SUM(paid) as paid'),
            DB::raw('SUM(due) as due')
        )
            ->whereBetween('date', [$fromDate, $toDate])
            ->groupBy(DB::raw("DATE_FORMAT(date, '%Y-%m')"))
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();
    }

    private function getDoctorReport(string $fromDate, string $toDate): array
    {
        return Invoice::select(
            'doctors.name as doctor',
            DB::raw('COUNT(DISTINCT invoices.patient_id) as patients'),
            DB::raw('SUM(invoices.total) as total'),
            DB::raw('SUM(invoices.total * doctors.commission_percentage / 100) as commission')
        )
            ->join('doctors', 'invoices.doctor_id', '=', 'doctors.id')
            ->whereBetween('invoices.date', [$fromDate, $toDate])
            ->groupBy('doctors.id', 'doctors.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    private function getTestReport(string $fromDate, string $toDate): array
    {
        return DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.date', [$fromDate, $toDate])
            ->select('invoice_items.name as test')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(invoice_items.total) as total')
            ->groupBy('invoice_items.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    // Bill Collection Report
    public function billCollection(Request $request): JsonResponse
    {
        $fromDate = $request->from_date ?? now()->toDateString();
        $toDate = $request->to_date ?? now()->toDateString();

        // Collections by date
        $dailyCollections = Payment::whereBetween(DB::raw('DATE(date)'), [$fromDate, $toDate])
            ->select(
                DB::raw('DATE(date) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy(DB::raw('DATE(date)'))
            ->orderBy('date', 'desc')
            ->get();

        // Collections by method
        $byMethod = Payment::whereBetween(DB::raw('DATE(date)'), [$fromDate, $toDate])
            ->select(
                'payment_method',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // Summary
        $summary = [
            'total_collected' => $dailyCollections->sum('total'),
            'total_transactions' => $dailyCollections->sum('transactions'),
            'average_per_day' => $dailyCollections->count() > 0 ? $dailyCollections->sum('total') / $dailyCollections->count() : 0,
        ];

        return response()->json([
            'daily' => $dailyCollections,
            'by_method' => $byMethod,
            'summary' => $summary,
        ]);
    }

    // Cashier Wise Report
    public function cashierWise(Request $request): JsonResponse
    {
        $fromDate = $request->from_date ?? now()->toDateString();
        $toDate = $request->to_date ?? now()->toDateString();

        $cashierReport = Payment::whereBetween(DB::raw('DATE(date)'), [$fromDate, $toDate])
            ->select(
                'received_by',
                DB::raw('SUM(amount) as total_collected'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('AVG(amount) as avg_transaction')
            )
            ->groupBy('received_by')
            ->with('receivedBy:id,name')
            ->orderByDesc('total_collected')
            ->get()
            ->map(function ($item) {
                return [
                    'cashier_id' => $item->received_by,
                    'cashier_name' => $item->receivedBy?->name ?? 'Unknown',
                    'total_collected' => $item->total_collected,
                    'transactions' => $item->transactions,
                    'avg_transaction' => round($item->avg_transaction, 2),
                ];
            });

        // By payment method for each cashier
        $byMethodPerCashier = Payment::whereBetween(DB::raw('DATE(date)'), [$fromDate, $toDate])
            ->select(
                'received_by',
                'payment_method',
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('received_by', 'payment_method')
            ->with('receivedBy:id,name')
            ->get()
            ->groupBy('received_by');

        return response()->json([
            'cashiers' => $cashierReport,
            'by_method_per_cashier' => $byMethodPerCashier,
            'summary' => [
                'total_cashiers' => $cashierReport->count(),
                'total_collected' => $cashierReport->sum('total_collected'),
            ],
        ]);
    }

    // Date Wise Cashier Report
    public function dateWiseCashier(Request $request): JsonResponse
    {
        $fromDate = $request->from_date ?? now()->startOfWeek()->toDateString();
        $toDate = $request->to_date ?? now()->toDateString();
        $cashierId = $request->cashier_id;

        $query = Payment::whereBetween(DB::raw('DATE(date)'), [$fromDate, $toDate]);

        if ($cashierId) {
            $query->where('received_by', $cashierId);
        }

        $report = $query->select(
            DB::raw('DATE(date) as date'),
            'received_by',
            DB::raw('SUM(amount) as total'),
            DB::raw('COUNT(*) as transactions')
        )
            ->groupBy(DB::raw('DATE(date)'), 'received_by')
            ->with('receivedBy:id,name')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'cashier_id' => $item->received_by,
                    'cashier_name' => $item->receivedBy?->name ?? 'Unknown',
                    'total' => $item->total,
                    'transactions' => $item->transactions,
                ];
            });

        // Get all cashiers for filter
        $cashiers = User::whereHas('payments', function ($q) use ($fromDate, $toDate) {
            $q->whereBetween(DB::raw('DATE(date)'), [$fromDate, $toDate]);
        })->select('id', 'name')->get();

        return response()->json([
            'report' => $report,
            'cashiers' => $cashiers,
            'summary' => [
                'total' => $report->sum('total'),
                'transactions' => $report->sum('transactions'),
            ],
        ]);
    }

    // Performance Report
    public function performance(Request $request): JsonResponse
    {
        $fromDate = $request->from_date ?? now()->startOfMonth()->toDateString();
        $toDate = $request->to_date ?? now()->toDateString();

        // Doctor performance
        $doctorPerformance = Invoice::whereBetween('date', [$fromDate, $toDate])
            ->whereNotNull('doctor_id')
            ->select(
                'doctor_id',
                DB::raw('COUNT(*) as total_invoices'),
                DB::raw('COUNT(DISTINCT patient_id) as unique_patients'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('SUM(consultation_fee) as consultation_revenue'),
                DB::raw('SUM(doctor_commission) as total_commission'),
                DB::raw('AVG(total) as avg_invoice_value')
            )
            ->groupBy('doctor_id')
            ->with('doctor:id,name,specialization')
            ->orderByDesc('total_revenue')
            ->get();

        // Test category performance - use invoice_items table
        $testPerformance = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.date', [$fromDate, $toDate])
            ->select('invoice_items.item_type as category')
            ->selectRaw('COUNT(*) as test_count')
            ->selectRaw('SUM(invoice_items.total) as total_revenue')
            ->groupBy('invoice_items.item_type')
            ->orderByDesc('total_revenue')
            ->get();

        // Daily performance trend
        $dailyTrend = Invoice::whereBetween('date', [$fromDate, $toDate])
            ->select(
                DB::raw('DATE(date) as date'),
                DB::raw('COUNT(*) as invoices'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('SUM(paid) as collected')
            )
            ->groupBy(DB::raw('DATE(date)'))
            ->orderBy('date')
            ->get();

        // Collection efficiency
        $collectionEfficiency = [
            'total_billed' => Invoice::whereBetween('date', [$fromDate, $toDate])->sum('total'),
            'total_collected' => Invoice::whereBetween('date', [$fromDate, $toDate])->sum('paid'),
            'collection_rate' => 0,
        ];
        if ($collectionEfficiency['total_billed'] > 0) {
            $collectionEfficiency['collection_rate'] = round(
                ($collectionEfficiency['total_collected'] / $collectionEfficiency['total_billed']) * 100,
                2
            );
        }

        return response()->json([
            'doctor_performance' => $doctorPerformance,
            'test_performance' => $testPerformance,
            'daily_trend' => $dailyTrend,
            'collection_efficiency' => $collectionEfficiency,
        ]);
    }

    // Patient wise Due Report
    public function patientDue(Request $request): JsonResponse
    {
        $minDue = $request->min_due ?? 0;

        $patientsDue = Invoice::select(
            'patient_id',
            DB::raw('SUM(due) as total_due'),
            DB::raw('SUM(total) as total_billed'),
            DB::raw('COUNT(*) as invoice_count'),
            DB::raw('MIN(date) as first_invoice'),
            DB::raw('MAX(date) as last_invoice'),
            DB::raw('MAX(due_date) as last_due_date')
        )
            ->where('due', '>', 0)
            ->groupBy('patient_id')
            ->having('total_due', '>=', $minDue)
            ->with('patient:id,name,phone,patient_id')
            ->orderByDesc('total_due')
            ->get()
            ->map(function ($item) {
                $isOverdue = $item->last_due_date && $item->last_due_date < now()->toDateString();

                return [
                    'patient_id' => $item->patient_id,
                    'patient_code' => $item->patient?->patient_id,
                    'patient_name' => $item->patient?->name,
                    'patient_phone' => $item->patient?->phone,
                    'total_due' => $item->total_due,
                    'total_billed' => $item->total_billed,
                    'invoice_count' => $item->invoice_count,
                    'first_invoice' => $item->first_invoice,
                    'last_invoice' => $item->last_invoice,
                    'last_due_date' => $item->last_due_date,
                    'is_overdue' => $isOverdue,
                    'days_overdue' => $isOverdue ? now()->diffInDays($item->last_due_date) : 0,
                ];
            });

        // Summary
        $summary = [
            'total_patients' => $patientsDue->count(),
            'total_due' => $patientsDue->sum('total_due'),
            'overdue_patients' => $patientsDue->where('is_overdue', true)->count(),
            'overdue_amount' => $patientsDue->where('is_overdue', true)->sum('total_due'),
        ];

        // Age-wise due breakdown
        $ageBreakdown = [
            '0-30 days' => 0,
            '31-60 days' => 0,
            '61-90 days' => 0,
            '90+ days' => 0,
        ];

        foreach ($patientsDue as $patient) {
            if ($patient['days_overdue'] <= 30) {
                $ageBreakdown['0-30 days'] += $patient['total_due'];
            } elseif ($patient['days_overdue'] <= 60) {
                $ageBreakdown['31-60 days'] += $patient['total_due'];
            } elseif ($patient['days_overdue'] <= 90) {
                $ageBreakdown['61-90 days'] += $patient['total_due'];
            } else {
                $ageBreakdown['90+ days'] += $patient['total_due'];
            }
        }

        return response()->json([
            'patients' => $patientsDue,
            'summary' => $summary,
            'age_breakdown' => $ageBreakdown,
        ]);
    }

    // Daily Sales Report
    public function dailySales(Request $request): JsonResponse
    {
        $date = $request->date ?? now()->toDateString();

        // Invoice details
        $invoices = Invoice::whereDate('date', $date)
            ->with(['patient:id,name,phone', 'doctor:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($invoice) {
                return [
                    'invoice_no' => $invoice->invoice_no,
                    'time' => $invoice->created_at->format('H:i'),
                    'patient_name' => $invoice->patient?->name,
                    'patient_phone' => $invoice->patient?->phone,
                    'doctor_name' => $invoice->doctor?->name,
                    'subtotal' => $invoice->subtotal,
                    'consultation_fee' => $invoice->consultation_fee,
                    'discount' => $invoice->discount,
                    'total' => $invoice->total,
                    'paid' => $invoice->paid,
                    'due' => $invoice->due,
                    'status' => $invoice->status,
                    'payment_method' => $invoice->payments()->latest()->first()?->payment_method,
                ];
            });

        // Summary
        $summary = Invoice::whereDate('date', $date)
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(subtotal) as total_subtotal,
                SUM(consultation_fee) as total_consultation,
                SUM(discount) as total_discount,
                SUM(total) as total_sales,
                SUM(paid) as total_collected,
                SUM(due) as total_due
            ')
            ->first();

        // Hourly breakdown
        $hourlyBreakdown = Invoice::whereDate('date', $date)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count, SUM(total) as total')
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get();

        // Payment methods
        $paymentMethods = Payment::whereDate('date', $date)
            ->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();

        // Test category breakdown - use invoice_items table
        $testCategories = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereDate('invoices.date', $date)
            ->select('invoice_items.item_type as category')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(invoice_items.total) as total')
            ->groupBy('invoice_items.item_type')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'date' => $date,
            'invoices' => $invoices,
            'summary' => $summary,
            'hourly_breakdown' => $hourlyBreakdown,
            'payment_methods' => $paymentMethods,
            'test_categories' => $testCategories,
        ]);
    }
}
