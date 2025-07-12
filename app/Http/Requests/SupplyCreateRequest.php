<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplyCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [],
            'slug' => [],
            'amount' => [],
            'quantity' => [],
            'unit' => [],
        ];
    }
}
