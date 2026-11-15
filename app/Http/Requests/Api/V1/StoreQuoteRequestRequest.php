<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer' => ['required', 'array'],

            'customer.name' => [
                'required',
                'string',
                'max:255',
            ],

            'customer.phone' => [
                'required',
                'string',
                'max:255',
            ],

            'customer.email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'customer.customer_type' => [
                'nullable',
                Rule::in([
                    'visitor',
                    'customer',
                    'wholesale',
                ]),
            ],

            'customer.address' => [
                'nullable',
                'string',
            ],

            'customer.notes' => [
                'nullable',
                'string',
            ],

            'requested_items' => [
                'required',
                'array',
                'min:1',
            ],

            'requested_items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'requested_items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'notes' => [
                'nullable',
                'string',
            ],
        ];
    }
}