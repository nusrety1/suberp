<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseCreateRequest;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use Inertia\Inertia;

class PurchaseController extends Controller
{
    public function create(PurchaseCreateRequest $request)
    {
        $purchase = Purchase::query()->create([
            'customer_id' => $request->input('customer_id'),
            'repayment_date' => $request->input('repayment_data'),
            'bargain_price' => $request->input('bargain_price'),
        ]);

        array_map(function ($item) use ($purchase) {
            PurchaseProduct::query()->create([
                'product_id' => $item['product_id'],
                'purchase_id' => $purchase->id,
                'quantity' => $item['quantity'],
                'purchase_time_unit_price' => $item['purchase_time_unit_price'],
            ]);
        }, $request->input('products'));

        return to_route('purchases');
    }

    public function list()
    {
        $purchases = Purchase::query()
            ->with('customer')
            ->orderBy('repayment_date', 'desc')->paginate(30);

        return Inertia::render('Purchases', ['purchases' => $purchases]);
    }
}
