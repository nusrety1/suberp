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

    public function update(ProductCreateRequest $request)
    {
        $slug = Str::slug($request->input('name'));
        $oldPrice = $this->checkOldPrice($slug);

        $data = [
            'name' => $request->input('name'),
            'slug' => $slug,
            'price' => $request->input('price'),
            'old_price' => $oldPrice,
            'description' => $request->input('description'),
        ];

        $isUpdated = Product::query()
            ->where('id', $request->input('id'))
            ->update($data);

        return $isUpdated ? to_route('products') : false;
    }

    public function list()
    {
        $products = Product::query()->paginate(30);

        return Inertia::render('Products', ['products' => $products]);
    }

    public function details(int $id)
    {
        $product = Product::query()
            ->findOrFail($id)
            ->toArray();

        return Inertia::render('AddProduct', [
            'product' => $product,
            'is_edit' => true,
        ]);
    }

    private function checkOldPrice(string $slug)
    {
        $oldProduct = Product::query()
            ->where('slug', $slug)
            ->pluck('price')
            ->toArray();

        return $oldProduct['price'] ?? null;
    }

    public function listBasic()
    {
        $products = Product::query()->select(['id', 'name', 'price'])->get();

        return response()->json([
            'data' => $products,
        ]);
    }
}
