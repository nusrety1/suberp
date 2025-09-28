<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PartialPaymentObtainRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'purchase_id' => ['required', 'numeric'],
            'product_id' => ['required', 'numeric'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'product_quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
