<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyListBasicRequest;
use App\Http\Requests\CustomerCreateRequest;
use App\Models\Customer;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function create(CustomerCreateRequest $request)
    {
        $isCreate = Customer::query()->create([
            'name' => $request->name,
            'surname' => $request->surname,
            'full_name' => $request->name.$request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'description' => $request->description,
        ]);

        return $isCreate ? to_route('customers') : false;
    }

    public function list()
    {
        $customers = Customer::query()->paginate(30);

        return Inertia::render('Customers', ['customers' => $customers]);
    }

    public function listBasic(CompanyListBasicRequest $request)
    {
        $customers = Customer::query()->select(['id', 'full_name'])->get();

        return response()->json([
            'data' => $customers,
        ]);
    }
}
