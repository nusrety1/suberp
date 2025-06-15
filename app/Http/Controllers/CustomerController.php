<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerCreateRequest;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function create(CustomerCreateRequest $request)
    {
        $isCreate = Customer::query()->create([
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'description' => $request->description,
        ]);

        return $isCreate ? to_route('customers') : false;
    }
}
