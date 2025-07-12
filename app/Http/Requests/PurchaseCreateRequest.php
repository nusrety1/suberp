<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer'],
            'repayment_data' => ['required', 'date'],
            'bargain_price' => ['nullable', 'numeric'],
            'products' => ['required', 'array'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'products.*.purchase_time_unit_price' => ['required', 'numeric'],
        ];
    }
}
