<?php

namespace App\Http\Controllers;

use App\Http\Requests\PartialPaymentObtainRequest;
use App\Http\Requests\PurchaseCreateRequest;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use Inertia\Inertia;

class PurchaseController extends Controller
{
    public function addPurchase()
    {
        return Inertia::render('AddPurchase');
    }

    public function create(PurchaseCreateRequest $request)
    {
        $purchase = Purchase::query()->create([
            'customer_id' => $request->input('customer_id'),
            'repayment_date' => $request->input('repayment_data'),
            'bargain_price' => $request->input('bargain_price'),
            'payment_method' => $request->input('payment_method'),
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
            ->orderBy('repayment_date', 'desc')
            ->paginate(30);

        return Inertia::render('Purchases', ['purchases' => $purchases]);
    }

    public function details(int $id)
    {
        $purchase = Purchase::query()
            ->with('customer')
            ->where('id', $id)
            ->get()
            ->toArray();

        $purchaseProducts = PurchaseProduct::query()
            ->with('product')
            ->where('purchase_id', $id)
            ->get();

        return response()->json([
            'purchase' => $purchase,
            'products' => $purchaseProducts,
            'total_receivable_amount' => $this->calcTotalReceivableAmount($purchase[0]),
        ]);
    }

    public function partialPaymentObtain(PartialPaymentObtainRequest $request)
    {
        Purchase::query()
            ->where('id', $request->input('purchase_id'))
            ->update([
                'paid_amount' => $request->input('paid_amount'),
            ]);

        return [
            'success' => true,
        ];
    }

    protected function calcTotalReceivableAmount(array $purchase)
    {
        $purchaseId = $purchase['id'];

        $purchaseProducts = PurchaseProduct::query()
            ->with(['product', 'purchase'])
            ->where('purchase_id', $purchaseId)
            ->get()
            ->toArray();

        $subTotal = array_reduce($purchaseProducts, function ($carry, $item) {
            return $carry + ($item['quantity'] * $item['product']['price']);
        }, 0);

        return $subTotal - $purchase['bargain_price'];
    }
}
