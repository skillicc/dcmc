<?php

namespace App\Http\Requests;

use App\Models\CommissionLedger;
use Illuminate\Foundation\Http\FormRequest;

class PayCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'in:Doctor,Referral'],
            'entity_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:' . implode(',', CommissionLedger::paymentMethods())],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'entity_type.required' => 'Entity type আবশ্যক',
            'amount.required' => 'পরিমাণ আবশ্যক',
            'amount.min' => 'পরিমাণ কমপক্ষে ০.০১ হতে হবে',
            'payment_method.required' => 'পেমেন্ট মেথড আবশ্যক',
        ];
    }
}
