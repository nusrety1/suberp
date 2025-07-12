<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyListBasicRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'sometimes', 'integer', 'min:1'],
            'limit' => ['nullable', 'sometimes', 'integer', 'min:1'],
            'name' => ['nullable', 'sometimes', 'string'],
        ];
    }
}
