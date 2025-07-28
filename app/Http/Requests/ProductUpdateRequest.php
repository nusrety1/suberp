<?php

namespace App\Http\Requests;

class ProductUpdateRequest extends ProductCreateRequest
{
    public function rules(): array
    {
        $rules = [
            'id' => ['required', 'integer', 'exists:products,id'],
        ];

        return [...parent::rules(), ...$rules];
    }
}
