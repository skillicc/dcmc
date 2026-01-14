<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:Doctor,Agent,Patient,Staff,Other'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'discount_type' => ['sometimes', 'required', 'in:Fixed,Percentage'],
            'discount_value' => ['sometimes', 'required', 'numeric', 'min:0'],
            'commission_type' => ['sometimes', 'required', 'in:Fixed,Percentage'],
            'commission_value' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
