<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplyCreateRequest;
use App\Http\Requests\SupplyPaymentRequest;
use App\Models\Customer;
use App\Models\Supply;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SuppliesController extends Controller
{
    public function list()
    {
        $supplies = Supply::with('customer')->orderBy('created_at', 'desc')->paginate(30);

        return Inertia::render('Supplies', ['supplies' => $supplies]);
    }

    public function create(SupplyCreateRequest $request)
    {
        $slug = Str::slug($request->input('name'));

        $isCreate = Supply::query()->create([
            'name' => $request->input('name'),
            'slug' => $slug,
            'amount' => $request->input('amount'),
            'paid_amount' => $request->input('paid_amount'),
            'quantity' => $request->input('quantity'),
            'unit' => $request->input('unit'),
            'description' => $request->input('description'),
            'customer_id' => $request->input('customer_id'),
        ]);

        return $isCreate ? to_route('supplies') : false;
    }

    public function addSupply()
    {
        $customers = Customer::select(['id', 'name', 'surname', 'full_name'])->get();

        return Inertia::render('AddSupply', ['customers' => $customers]);
    }

    public function detailSupply(int $id)
    {
        $supply = Supply::query()->findOrFail($id);

        return Inertia::render('SupplyPayment');
    }

    public function supplyPayment(SupplyPaymentRequest $request)
    {
        $supply = Supply::query()
            ->where('id', $request->input('id'))
            ->firstOrFail();

        $newPaidAmount = $supply->paid_amount ?
            $supply->paid_amount + $request->input('amount') :
            $request->input('amount');

        $supply->update(['paid_amount' => $newPaidAmount]);

        return back();
    }

    public function getCustomerTotalDebt(int $customerId)
    {
        $supplies = Supply::where('customer_id', $customerId)->get();

        $totalAmount = $supplies->sum('amount');
        $totalPaid = $supplies->sum('paid_amount');

        return response()->json([
            'total_debt' => $totalAmount - $totalPaid,
        ]);
    }
}
