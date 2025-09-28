<?php

namespace App\Http\Controllers;

use App\Http\Requests\PartialPaymentObtainRequest;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(PartialPaymentObtainRequest $request)
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

    public function index(Request $request)
    {
        $payments = Payment::with(['purchase.customer', 'product'])
            ->when($request->purchase_id, function ($query, $purchaseId) {
                return $query->where('purchase_id', $purchaseId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json($payments);
    }

    public function show($id)
    {
        $payment = Payment::with(['purchase.customer', 'product'])->findOrFail($id);

        return response()->json($payment);
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $purchaseId = $payment->purchase_id;

        $payment->delete();

        // Satıştaki toplam ödenen miktarı yeniden hesapla
        $totalPaidAmount = Payment::where('purchase_id', $purchaseId)
            ->sum('paid_amount');

        Purchase::where('id', $purchaseId)
            ->update(['paid_amount' => $totalPaidAmount]);

        return response()->json(['success' => true]);
    }
}
