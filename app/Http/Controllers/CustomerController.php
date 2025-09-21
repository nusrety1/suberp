<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyListBasicRequest;
use App\Http\Requests\CustomerCreateRequest;
use App\Models\Customer;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function create(CustomerCreateRequest $request)
    {
        $fullName = $request->name.' '.$request->surname;

        $isCreate = Customer::query()->create([
            'name' => $request->name,
            'surname' => $request->surname,
            'full_name' => $fullName,
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

    public function details(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        // Satışları filtreleme ve arama
        $query = Purchase::with(['products.product'])
            ->where('customer_id', $id)
            ->orderBy('created_at', 'desc');

        // Tarih filtresi
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Arama filtresi
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        $purchases = $query->paginate(20);

        // Toplam borç hesaplama (güncel fiyatlarla)
        $totalDebt = 0;
        $totalBargainDiscount = 0;

        foreach ($purchases->items() as $purchase) {
            $purchaseTotal = 0;
            foreach ($purchase->products as $purchaseProduct) {
                $currentPrice = $purchaseProduct->product->price;
                $quantity = $purchaseProduct->quantity;
                $purchaseTotal += $currentPrice * $quantity;
            }

            // Pazarlık indirimini çıkar
            $purchaseTotal -= $purchase->bargain_price;
            $totalDebt += $purchaseTotal;
            $totalBargainDiscount += $purchase->bargain_price;
        }

        // Ödenen miktarı çıkar
        $totalPaid = $purchases->sum('paid_amount');
        $remainingDebt = $totalDebt - $totalPaid;

        return Inertia::render('CustomerDetails', [
            'customer' => $customer,
            'purchases' => $purchases,
            'totalDebt' => $totalDebt,
            'totalPaid' => $totalPaid,
            'remainingDebt' => $remainingDebt,
            'totalBargainDiscount' => $totalBargainDiscount,
            'filters' => $request->only(['date_from', 'date_to', 'search']),
        ]);
    }
}
