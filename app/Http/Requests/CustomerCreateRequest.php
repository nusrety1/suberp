<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerCreateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'surname' => ['required', 'string'],
            'email' => ['nullable', 'bail', 'email:filter,strict,rfc,spoof', 'max:255'],
            'phone' => ['nullable', 'sometimes', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
