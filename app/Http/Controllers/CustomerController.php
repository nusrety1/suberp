<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyListBasicRequest;
use App\Http\Requests\CustomerCreateRequest;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Supply;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Müşteri için ürün bazlı satış verilerini getirir
     */
    private function getProductBasedSales(int $customerId, Request $request): array
    {
        // Müşterinin tüm satışlarını ve ürünlerini getir
        $query = Purchase::with(['products.product', 'products.payments'])
            ->where('customer_id', $customerId);

        // Tarih filtresi
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $purchases = $query->get();

        // Ürün bazlı satışları gruplandır
        $productBasedSales = [];
        $productPayments = [];
        $productPaidQuantities = []; // Ürün bazında ödenen toplam adet
        $productBargainDiscounts = []; // Ürün bazlı pazarlık indirimleri

        // Önce tüm satışları ve ödemeleri topla
        foreach ($purchases as $purchase) {
            foreach ($purchase->products as $purchaseProduct) {
                $productId = $purchaseProduct->product_id;
                $productName = $purchaseProduct->product->name;
                $quantity = $purchaseProduct->quantity;
                $currentPrice = $purchaseProduct->product->price;
                $purchaseTimePrice = $purchaseProduct->purchase_time_unit_price;

                // Ürün daha önce eklenmiş mi kontrol et
                if (! isset($productBasedSales[$productId])) {
                    $productBasedSales[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $productName,
                        'total_quantity' => 0,
                        'current_price' => $currentPrice,
                        'total_current_value' => 0,
                        'total_paid_amount' => 0, // Toplam ödenen miktar
                        'paid_product_quantity' => 0, // Toplam ödenen adet
                        'total_bargain_discount' => 0, // Toplam pazarlık indirimi
                        'remaining_debt' => 0, // Kalan borç
                        'purchases' => [],
                    ];
                    $productPayments[$productId] = 0; // Ürün için toplam ödemeleri takip et
                    $productPaidQuantities[$productId] = 0; // Ürün için toplam ödenen adet
                    $productBargainDiscounts[$productId] = 0; // Ürün için toplam pazarlık indirimlerini takip et
                }

                // Ürün bilgilerini güncelle
                $productBasedSales[$productId]['total_quantity'] += $quantity;
                $productBasedSales[$productId]['total_current_value'] += $currentPrice * $quantity;

                // Bu ürün için yapılan ödemeleri topla
                $paidAmount = (float) \App\Models\Payment::where('purchase_id', $purchase->id)
                    ->where('product_id', $productId)
                    ->sum('paid_amount');
                $productPayments[$productId] += $paidAmount;

                // Bu ürün için ödenen adetleri topla
                $paidQuantityForPurchase = (int) \App\Models\Payment::where('purchase_id', $purchase->id)
                    ->where('product_id', $productId)
                    ->sum('product_quantity');
                $productPaidQuantities[$productId] += $paidQuantityForPurchase;

                // Ürünün toplam değerine göre pazarlık indirimini orantılı olarak hesapla
                $totalPurchaseValue = 0;
                foreach ($purchase->products as $pp) {
                    $totalPurchaseValue += $pp->purchase_time_unit_price * $pp->quantity;
                }

                // Bu ürünün satın alma içindeki oranını hesapla
                $productRatio = ($purchaseTimePrice * $quantity) / $totalPurchaseValue;

                // Bu ürüne düşen pazarlık indirimi
                $productBargainDiscount = $purchase->bargain_price * $productRatio;
                $productBargainDiscounts[$productId] += $productBargainDiscount;

                // Satış bilgisini ekle
                $productBasedSales[$productId]['purchases'][] = [
                    'purchase_id' => $purchase->id,
                    'purchase_date' => $purchase->created_at,
                    'quantity' => $quantity,
                    'purchase_time_price' => $purchaseTimePrice,
                    'current_price' => $currentPrice,
                    'purchase_time_total' => $purchaseTimePrice * $quantity,
                    'current_total' => $currentPrice * $quantity,
                    'paid_amount' => $paidAmount, // Bu satış için ödenen tutar
                    'paid_quantity' => $paidQuantityForPurchase, // Bu satış için ödenen adet
                    'bargain_discount' => $productBargainDiscount, // Bu satış için pazarlık indirimi
                ];
            }
        }

        // Ödeme bilgilerini ekle
        foreach ($productBasedSales as $productId => &$productSale) {
            $productSale['total_paid_amount'] = $productPayments[$productId];
            $productSale['paid_product_quantity'] = $productPaidQuantities[$productId];
            $productSale['total_bargain_discount'] = $productBargainDiscounts[$productId];

            // Kalan ürün adedi hesaplama
            $remainingQuantity = max(0, $productSale['total_quantity'] - $productSale['paid_product_quantity']);

            if ($remainingQuantity <= 0) {
                $productSale['remaining_debt'] = 0;
            } else {
                $currentUnitPrice = $productSale['current_price'];

                $bargainDiscountRatio = $productSale['total_bargain_discount'] * $remainingQuantity / $productSale['total_quantity'];

                $productSale['remaining_debt'] = ($remainingQuantity * $currentUnitPrice) - $bargainDiscountRatio;
            }
        }

        // Diziden değerleri al ve sırala
        $result = array_values($productBasedSales);

        // Toplam miktara göre azalan sıralama
        usort($result, function ($a, $b) {
            return $b['total_quantity'] - $a['total_quantity'];
        });

        return $result;
    }

    public function create(CustomerCreateRequest $request)
    {
        $fullName = $request->input('name').' '.$request->input('surname');

        $isCreate = Customer::query()->create([
            'name' => $request->input('name'),
            'surname' => $request->input('surname'),
            'full_name' => $fullName,
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'address' => $request->input('address'),
            'description' => $request->input('description'),
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

        // Her satış için ödenen ürün adedi toplamını ekle
        foreach ($purchases->items() as $purchase) {
            // Bu satıştaki tüm ürünler için yapılan ödemelerdeki ürün adetlerinin toplamı
            $paidQuantity = \App\Models\Payment::where('purchase_id', $purchase->id)
                ->sum('product_quantity');

            // Frontend'de kullanmak üzere dinamik özellik ekle
            $purchase->paid_product_quantity = (int) ($paidQuantity ?? 0);
        }

        // Toplam borç hesaplama (ürün bazında kalan adet × güncel fiyat)
        $totalDebt = 0;
        $totalBargainDiscount = 0;
        $totalCurrentValue = 0; // Tüm ürünlerin güncel toplam değeri
        $totalPaidProductQuantity = 0; // Toplam ödenen ürün adedi

        foreach ($purchases->items() as $purchase) {
            // Bu satıştaki tüm ürünlerin güncel toplam değerini hesapla
            $purchaseCurrentValue = 0;
            $purchaseTotalQuantity = 0;

            foreach ($purchase->products as $purchaseProduct) {
                $currentPrice = $purchaseProduct->product->price;
                $quantity = $purchaseProduct->quantity;
                $purchaseCurrentValue += $currentPrice * $quantity;
                $purchaseTotalQuantity += $quantity;
            }

            $totalCurrentValue += $purchaseCurrentValue;
            $totalBargainDiscount += $purchase->bargain_price;

            // Bu satış için ödenen ürün adetini al
            $paidQuantityForPurchase = \App\Models\Payment::where('purchase_id', $purchase->id)
                ->sum('product_quantity');
            $totalPaidProductQuantity += $paidQuantityForPurchase;

            // Kalan ürün adedini hesapla
            $remainingQuantity = max(0, $purchaseTotalQuantity - $paidQuantityForPurchase);

            if ($remainingQuantity > 0) {
                // Kalan adet için güncel fiyatlarla borç hesapla
                $remainingRatio = $remainingQuantity / $purchaseTotalQuantity;

                // Kalan borç = (Kalan adet oranı × Güncel toplam değer) - (Kalan adet oranı × Pazarlık indirimi)
                $remainingDebtForPurchase = ($remainingRatio * $purchaseCurrentValue) - ($remainingRatio * $purchase->bargain_price);
                $totalDebt += $remainingDebtForPurchase;
            }
        }

        // Toplam ödenen miktar
        $totalPaid = $purchases->sum('paid_amount');

        // Kalan borç (artık totalDebt zaten doğru hesaplandı)
        $remainingDebt = max(0, $totalDebt);

        // Ürün bazlı satış verilerini getir
        $productBasedSales = $this->getProductBasedSales($id, $request);

        // Müşteriden alınan malzemeleri getir
        $supplies = Supply::where('customer_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('CustomerDetails', [
            'customer' => $customer,
            'purchases' => $purchases,
            'totalDebt' => $totalDebt,
            'totalPaid' => $totalPaid,
            'remainingDebt' => $remainingDebt,
            'totalBargainDiscount' => $totalBargainDiscount,
            'filters' => $request->only(['date_from', 'date_to', 'search']),
            'productBasedSales' => $productBasedSales,
            'supplies' => $supplies,
        ]);
    }

    /**
     * Müşterinin ödeme geçmişini getirir
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function paymentHistory(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        // Müşterinin satışlarını al
        $purchaseIds = Purchase::where('customer_id', $id)->pluck('id');

        // Ödemeleri getir
        $query = Payment::with(['purchase', 'product'])
            ->whereIn('purchase_id', $purchaseIds)
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
                $q->where('description', 'like', "%{$searchTerm}%")
                    ->orWhereHas('product', function ($productQuery) use ($searchTerm) {
                        $productQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $payments = $query->paginate(20);

        // Toplam ödeme miktarını hesapla
        $totalPaidAmount = $payments->sum('paid_amount');

        return response()->json([
            'payments' => $payments,
            'totalPaidAmount' => $totalPaidAmount,
            'customer' => $customer,
        ]);
    }
}
