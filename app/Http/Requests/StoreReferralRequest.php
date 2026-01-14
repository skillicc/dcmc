<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:Doctor,Agent,Patient,Staff,Other'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'discount_type' => ['required', 'in:Fixed,Percentage'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'commission_type' => ['required', 'in:Fixed,Percentage'],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'রেফারেলের নাম আবশ্যক',
            'type.required' => 'রেফারেল টাইপ আবশ্যক',
            'discount_type.required' => 'ডিসকাউন্ট টাইপ আবশ্যক',
            'commission_type.required' => 'কমিশন টাইপ আবশ্যক',
        ];
    }
}
