<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id',
            ],

            'quote_request_id' => [
                'nullable',
                'integer',
                'exists:quote_requests,id',
            ],

            'total_amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'currency' => [
                'required',
                'string',
                Rule::in(['YER', 'USD', 'SAR']),
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}