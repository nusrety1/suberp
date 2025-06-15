<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerCreateRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    public function list(Request $request)
    {
        $customers = Customer::query()->paginate(30);

        return Inertia::render('Customers', ['customers' => $customers]);
    }
}
