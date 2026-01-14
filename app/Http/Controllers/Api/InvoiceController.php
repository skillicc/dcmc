<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['patient', 'doctor', 'items']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_no', 'like', "%{$request->search}%")
                    ->orWhereHas('patient', function ($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%")
                            ->orWhere('phone', 'like', "%{$request->search}%");
                    });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->from_date) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $invoices = $query->latest()->paginate($request->per_page ?? 10);

        return response()->json($invoices);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.item_type' => 'nullable|string',
            'items.*.item_id' => 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['due'] = $validated['total'] - ($validated['paid_amount'] ?? 0);
        $validated['paid'] = $validated['paid_amount'] ?? 0;

        if ($validated['due'] <= 0) {
            $validated['status'] = 'Paid';
        } elseif ($validated['paid'] > 0) {
            $validated['status'] = 'Partial';
        } else {
            $validated['status'] = 'Unpaid';
        }

        $items = $validated['items'];
        unset($validated['items'], $validated['paid_amount'], $validated['payment_method']);

        $invoice = Invoice::create($validated);

        // Create invoice items
        foreach ($items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_type' => $item['item_type'] ?? 'Test',
                'item_id' => $item['item_id'] ?? null,
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'] ?? $item['price'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'total' => $item['total'],
            ]);
        }

        if (($request->paid_amount ?? 0) > 0) {
            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $request->paid_amount,
                'payment_method' => $request->payment_method ?? 'Cash',
                'payment_reference' => $request->payment_reference,
                'received_by' => auth()->id(),
                'date' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Invoice created successfully',
            'data' => $invoice->load(['patient', 'doctor', 'items', 'payments']),
        ], 201);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return response()->json(['data' => $invoice->load(['patient', 'doctor', 'items', 'payments'])]);
    }

    public function addPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
            'received_by' => auth()->id(),
            'date' => now(),
        ]);

        $invoice->updatePaymentStatus();

        return response()->json([
            'message' => 'Payment added successfully',
            'data' => $invoice->load(['patient', 'doctor', 'payments']),
        ]);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully']);
    }
}
