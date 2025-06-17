<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductCreateRequest;
use App\Models\Product;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function create(ProductCreateRequest $request)
    {
        $slug = Str::slug($request->name);

        $oldPrice = $this->checkOldPrice($slug);

        $isCreate = Product::query()->create([
            'name' => $request->name,
            'slug' => $slug,
            'price' => $request->price,
            'old_price' => $oldPrice,
            'description' => $request->description,
        ]);

        return $isCreate ? to_route('products') : false;
    }

    public function list()
    {
        $products = Product::query()->paginate(30);

        return Inertia::render('Products', ['products' => $products]);
    }

    private function checkOldPrice(string $slug)
    {
        $oldProduct = Product::query()
            ->where('slug', $slug)
            ->pluck('price')
            ->toArray();

        return $oldProduct['price'] ?? null;
    }
}
