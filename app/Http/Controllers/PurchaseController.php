<?php

namespace App\Http\Controllers;

use App\Http\Requests\PartialPaymentObtainRequest;
use App\Http\Requests\PurchaseCreateRequest;
use App\Models\Payment;
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
            'repayment_date' => $request->input('repayment_date'),
            'bargain_price' => $request->input('bargain_price'),
            'payment_method' => $request->input('payment_method'),
            'description' => $request->input('description'),
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
            ->orderBy('created_at', 'desc')
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

        $payments = Payment::query()
            ->with('product')
            ->where('purchase_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'purchase' => $purchase,
            'products' => $purchaseProducts,
            'payments' => $payments,
            'total_receivable_amount' => $this->calcTotalReceivableAmount($purchase[0]),
        ]);
    }

    public function partialPaymentObtain(PartialPaymentObtainRequest $request)
    {
        // Önce satıştaki toplam ürün miktarını kontrol et
        $purchase = Purchase::findOrFail($request->input('purchase_id'));
        $purchaseProduct = PurchaseProduct::where('purchase_id', $request->input('purchase_id'))
            ->where('product_id', $request->input('product_id'))
            ->first();

        if (! $purchaseProduct) {
            return response()->json(['error' => 'Ürün bu satışta bulunamadı'], 404);
        }

        // Bu ürün için yapılan toplam ödeme miktarını hesapla
        $totalPaidQuantity = Payment::where('purchase_id', $request->input('purchase_id'))
            ->where('product_id', $request->input('product_id'))
            ->sum('product_quantity');

        $remainingQuantity = $purchaseProduct->quantity - $totalPaidQuantity;

        // Eğer ödeme miktarı kalan miktardan fazlaysa hata ver
        if ($request->input('product_quantity') > $remainingQuantity) {
            return response()->json([
                'error' => "Bu ürün için en fazla {$remainingQuantity} adet ödeme yapabilirsiniz",
            ], 400);
        }

        // Ödeme kaydını oluştur
        Payment::create([
            'purchase_id' => $request->input('purchase_id'),
            'product_id' => $request->input('product_id'),
            'paid_amount' => $request->input('payment_amount'),
            'description' => $request->input('description'),
            'product_quantity' => $request->input('product_quantity'),
        ]);

        // Satıştaki toplam ödenen miktarı güncelle
        $totalPaidAmount = Payment::where('purchase_id', $request->input('purchase_id'))
            ->sum('paid_amount');

        $purchase->update(['paid_amount' => $totalPaidAmount]);

        return response()->json(['success' => true]);
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
