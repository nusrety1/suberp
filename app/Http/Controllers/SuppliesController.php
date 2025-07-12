<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplyCreateRequest;
use App\Models\Supply;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SuppliesController extends Controller
{
    public function list()
    {
        $supplies = Supply::query()->paginate(30);

        return Inertia::render('Supplies', ['supplies' => $supplies]);
    }

    public function create(SupplyCreateRequest $request)
    {
        $slug = Str::slug($request->input('name'));

        $isCreate = Supply::query()->create([
            'name' => $request->input('name'),
            'slug' => $slug,
            'amount' => $request->input('amount'),
            'quantity' => $request->input('quantity'),
            'unit' => $request->input('unit'),
        ]);

        return $isCreate ? to_route('supplies') : false;
    }
}
