<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'unique:products'],
            'price' => ['required', 'numeric'],
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}
