<?php

namespace App\Http\Requests\Visa;

use Illuminate\Foundation\Http\FormRequest;

class VisaVerificationStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'request_id' => 'nullable',
            'country_id' => 'required|numeric|exists:countries,id',
            'passport_number' => 'nullable|alpha_num|passport|size:9',
            'visa_no' => 'nullable|string|min:2|max:50',
            'visa_ref_no' => 'nullable|string|min:2|max:50',
            'date_of_birth' => 'nullable|date',
            // 'required_properties' => 'nullable|array',
        ];
    }
}
