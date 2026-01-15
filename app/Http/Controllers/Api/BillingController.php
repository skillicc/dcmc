<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PayCommissionRequest;
use App\Http\Requests\StoreReferralRequest;
use App\Http\Requests\UpdateReferralRequest;
use App\Models\CommissionLedger;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Referral;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    // ================== Referrals ==================

    public function referrals(Request $request): JsonResponse
    {
        $query = Referral::query()->with('doctor');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('active_only')) {
            $query->active()->valid();
        }

        $referrals = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($referrals);
    }

    public function storeReferral(StoreReferralRequest $request): JsonResponse
    {
        $referral = Referral::create($request->validated());

        return response()->json([
            'message' => 'রেফারেল সফলভাবে তৈরি হয়েছে',
            'referral' => $referral->load('doctor'),
        ], 201);
    }

    public function showReferral(Referral $referral): JsonResponse
    {
        return response()->json($referral->load('doctor'));
    }

    public function updateReferral(UpdateReferralRequest $request, Referral $referral): JsonResponse
    {
        $referral->update($request->validated());

        return response()->json([
            'message' => 'রেফারেল সফলভাবে আপডেট হয়েছে',
            'referral' => $referral->fresh()->load('doctor'),
        ]);
    }

    public function destroyReferral(Referral $referral): JsonResponse
    {
        if ($referral->invoices()->exists()) {
            return response()->json([
                'message' => 'এই রেফারেলের সাথে ইনভয়েস সংযুক্ত আছে, মুছে ফেলা যাবে না',
            ], 422);
        }

        $referral->delete();

        return response()->json([
            'message' => 'রেফারেল সফলভাবে মুছে ফেলা হয়েছে',
        ]);
    }

    public function validateReferralCode(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);

        $referral = Referral::where('code', $request->code)
            ->active()
            ->valid()
            ->first();

        if (! $referral) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired referral code',
            ]);
        }

        return response()->json([
            'valid' => true,
            'referral' => $referral,
        ]);
    }

    public function referralTypes(): JsonResponse
    {
        return response()->json(Referral::types());
    }

    public function referralStats(Referral $referral): JsonResponse
    {
        $referral->updateStats();

        $ledgerEntries = CommissionLedger::forReferral($referral->id)
            ->with('invoice', 'processedBy')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'referral' => $referral,
            'pending_commission' => $referral->pending_commission,
            'ledger_entries' => $ledgerEntries,
        ]);
    }

    // ================== Doctor Commission ==================

    public function doctorCommissions(Request $request): JsonResponse
    {
        $query = Doctor::query()
            ->where('is_active', true)
            ->where('commission_percentage', '>', 0);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $doctors = $query->get()->map(function ($doctor) {
            return [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'phone' => $doctor->phone,
                'specialization' => $doctor->specialization,
                'commission_percentage' => $doctor->commission_percentage,
                'total_earned' => $doctor->total_commission_earned,
                'total_paid' => $doctor->total_commission_paid,
                'pending' => $doctor->pending_commission,
            ];
        });

        return response()->json($doctors);
    }

    public function doctorCommissionDetails(Doctor $doctor): JsonResponse
    {
        $ledgerEntries = CommissionLedger::forDoctor($doctor->id)
            ->with('invoice', 'processedBy')
            ->latest()
            ->paginate(20);

        $unpaidInvoices = Invoice::where('doctor_id', $doctor->id)
            ->unpaidCommission()
            ->with('patient')
            ->get();

        return response()->json([
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'commission_percentage' => $doctor->commission_percentage,
                'total_earned' => $doctor->total_commission_earned,
                'total_paid' => $doctor->total_commission_paid,
                'pending' => $doctor->pending_commission,
            ],
            'ledger_entries' => $ledgerEntries,
            'unpaid_invoices' => $unpaidInvoices,
        ]);
    }

    // ================== Commission Payments ==================

    public function payCommission(PayCommissionRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($data['entity_type'] === 'Doctor') {
            $entity = Doctor::findOrFail($data['entity_id']);

            if ($data['amount'] > $entity->pending_commission) {
                return response()->json([
                    'message' => 'পেমেন্ট পরিমাণ পেন্ডিং কমিশনের চেয়ে বেশি',
                ], 422);
            }

            $entity->recordCommissionPayment(
                $data['amount'],
                $data['payment_method'],
                $data['payment_reference'] ?? null,
                auth()->id()
            );
        } else {
            $entity = Referral::findOrFail($data['entity_id']);

            if ($data['amount'] > $entity->pending_commission) {
                return response()->json([
                    'message' => 'পেমেন্ট পরিমাণ পেন্ডিং কমিশনের চেয়ে বেশি',
                ], 422);
            }

            $entity->recordCommissionPayment(
                $data['amount'],
                $data['payment_method'],
                $data['payment_reference'] ?? null,
                auth()->id()
            );
        }

        return response()->json([
            'message' => 'কমিশন পেমেন্ট সফলভাবে রেকর্ড হয়েছে',
        ]);
    }

    public function commissionLedger(Request $request): JsonResponse
    {
        $query = CommissionLedger::query()
            ->with('invoice', 'processedBy')
            ->latest();

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $ledger = $query->paginate($request->get('per_page', 20));

        // Add entity details
        $ledger->getCollection()->transform(function ($entry) {
            $entry->entity = $entry->entity;

            return $entry;
        });

        return response()->json($ledger);
    }

    public function paymentMethods(): JsonResponse
    {
        return response()->json(CommissionLedger::paymentMethods());
    }

    // ================== Due Collection ==================

    public function dueInvoices(Request $request): JsonResponse
    {
        $query = Invoice::query()
            ->with(['patient', 'doctor'])
            ->due();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->boolean('overdue_only')) {
            $query->overdue();
        }

        $invoices = $query->orderBy('due_date')->paginate($request->get('per_page', 15));

        return response()->json($invoices);
    }

    public function collectDue(Request $request, Invoice $invoice): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $invoice->due],
            'payment_method' => ['required', 'string'],
            'payment_reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'date' => now(),
            'received_by' => auth()->id(),
            'notes' => $request->notes,
        ]);

        $invoice->updatePaymentStatus();

        return response()->json([
            'message' => 'পেমেন্ট সফলভাবে গৃহীত হয়েছে',
            'invoice' => $invoice->fresh()->load(['patient', 'doctor', 'payments']),
        ]);
    }

    public function dueStatsByPatient(Request $request): JsonResponse
    {
        $dueByPatient = Invoice::query()
            ->selectRaw('patient_id, SUM(due) as total_due, COUNT(*) as invoice_count')
            ->due()
            ->groupBy('patient_id')
            ->with('patient')
            ->orderByDesc('total_due')
            ->limit(50)
            ->get();

        return response()->json($dueByPatient);
    }

    // ================== Transactions ==================

    public function transactions(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->with(['invoice.patient', 'receivedBy']);

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $transactions = $query->latest('date')->paginate($request->get('per_page', 20));

        return response()->json($transactions);
    }

    public function transactionsSummary(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        $summary = Payment::query()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->selectRaw('
                payment_method,
                COUNT(*) as count,
                SUM(amount) as total
            ')
            ->groupBy('payment_method')
            ->get();

        $dailyTotals = Payment::query()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->selectRaw('DATE(date) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'by_method' => $summary,
            'daily_totals' => $dailyTotals,
            'total' => $summary->sum('total'),
        ]);
    }

    // ================== Billing Reports ==================

    public function billingReport(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // Invoice summary
        $invoiceSummary = Invoice::query()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(subtotal) as total_subtotal,
                SUM(discount) as total_discount,
                SUM(referral_discount) as total_referral_discount,
                SUM(tax_amount) as total_tax,
                SUM(total) as total_amount,
                SUM(paid) as total_paid,
                SUM(due) as total_due,
                SUM(doctor_commission) as total_commission
            ')
            ->first();

        // Status breakdown
        $statusBreakdown = Invoice::query()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->selectRaw('status, COUNT(*) as count, SUM(total) as total')
            ->groupBy('status')
            ->get();

        // Daily revenue
        $dailyRevenue = Invoice::query()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->selectRaw('DATE(date) as date, SUM(total) as total, SUM(paid) as paid')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top doctors by revenue
        $topDoctors = Invoice::query()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->whereNotNull('doctor_id')
            ->selectRaw('doctor_id, COUNT(*) as invoice_count, SUM(total) as total')
            ->groupBy('doctor_id')
            ->with('doctor')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Referral performance
        $referralPerformance = Invoice::query()
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->whereNotNull('referral_id')
            ->selectRaw('referral_id, COUNT(*) as invoice_count, SUM(total) as total, SUM(referral_discount) as discount_given')
            ->groupBy('referral_id')
            ->with('referral')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return response()->json([
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'summary' => $invoiceSummary,
            'status_breakdown' => $statusBreakdown,
            'daily_revenue' => $dailyRevenue,
            'top_doctors' => $topDoctors,
            'referral_performance' => $referralPerformance,
        ]);
    }

    public function commissionReport(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());

        // Doctor commissions
        $doctorCommissions = CommissionLedger::query()
            ->where('entity_type', 'Doctor')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw('
                entity_id,
                SUM(CASE WHEN type = "Earned" THEN amount ELSE 0 END) as earned,
                SUM(CASE WHEN type = "Paid" THEN ABS(amount) ELSE 0 END) as paid
            ')
            ->groupBy('entity_id')
            ->get()
            ->map(function ($item) {
                $item->doctor = Doctor::find($item->entity_id);

                return $item;
            });

        // Referral commissions
        $referralCommissions = CommissionLedger::query()
            ->where('entity_type', 'Referral')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw('
                entity_id,
                SUM(CASE WHEN type = "Earned" THEN amount ELSE 0 END) as earned,
                SUM(CASE WHEN type = "Paid" THEN ABS(amount) ELSE 0 END) as paid
            ')
            ->groupBy('entity_id')
            ->get()
            ->map(function ($item) {
                $item->referral = Referral::find($item->entity_id);

                return $item;
            });

        // Totals
        $totals = CommissionLedger::query()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->selectRaw('
                entity_type,
                SUM(CASE WHEN type = "Earned" THEN amount ELSE 0 END) as earned,
                SUM(CASE WHEN type = "Paid" THEN ABS(amount) ELSE 0 END) as paid
            ')
            ->groupBy('entity_type')
            ->get();

        return response()->json([
            'date_range' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'doctor_commissions' => $doctorCommissions,
            'referral_commissions' => $referralCommissions,
            'totals' => $totals,
        ]);
    }

    // ================== Consultation Fee ==================

    public function getConsultationFee(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'patient_id' => 'required|exists:patients,id',
        ]);

        $doctor = Doctor::find($request->doctor_id);
        $fee = $doctor->getConsultationFeeForPatient($request->patient_id);
        $isFollowUp = $doctor->isFollowUpForPatient($request->patient_id);

        return response()->json([
            'consultation_fee' => $fee,
            'is_follow_up' => $isFollowUp,
            'regular_fee' => $doctor->consultation_fee,
            'follow_up_fee' => $doctor->follow_up_fee,
            'follow_up_days' => $doctor->follow_up_days,
        ]);
    }

    public function updateDoctorFees(Request $request, Doctor $doctor): JsonResponse
    {
        $request->validate([
            'consultation_fee' => ['required', 'numeric', 'min:0'],
            'follow_up_fee' => ['required', 'numeric', 'min:0'],
            'follow_up_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $doctor->update($request->only(['consultation_fee', 'follow_up_fee', 'follow_up_days']));

        return response()->json([
            'message' => 'ডাক্তারের ফি সফলভাবে আপডেট হয়েছে',
            'doctor' => $doctor->fresh(),
        ]);
    }
}
