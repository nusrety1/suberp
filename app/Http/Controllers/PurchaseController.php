<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseController extends Controller
{
    public function list()
    {
        $purchases = Purchase::query()
            ->with('customer')
            ->orderBy('repayment_date', 'desc')->paginate(30);

        return Inertia::render('Purchases', ['purchases' => $purchases]);
    }
}
