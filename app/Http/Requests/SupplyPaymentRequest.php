<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplyPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:supplies,id'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
