<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    private function clientId(): int
    {
        return 1; // apne actual client id se replace karo
    }

    public function index()
    {
        $clientId = $this->clientId();

        $products = Product::query()
            ->where('client_id', $clientId)
            ->with('category:id,name')
            ->latest('id')
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'category' => $product->category?->name,
                'category_id' => $product->category_id,
                'sku' => $product->sku,
                'unit_type' => $product->unit_type,
                'base_rate' => $product->base_rate,
                'status' => $product->status,
            ]);

        return Inertia::render('Products/Index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        $clientId = $this->clientId();

        $categories = ProductCategory::query()
            ->where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Products/Form', [
            'product' => null,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $clientId = $this->clientId();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],

            'category_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')
                    ->where(fn ($query) =>
                        $query->where('client_id', $clientId)
                    ),
            ],

            'sku' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) =>
                        $query->where('client_id', $clientId)
                    ),
            ],

            'unit_type' => [
                'required',
                'integer',
                Rule::in([1, 2, 3, 4, 5, 6]),
            ],

            'base_rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                'integer',
                Rule::in([0, 1, 2]),
            ],
        ]);

        Product::create([
            'client_id' => $clientId,
            ...$validated,
        ]);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $clientId = $this->clientId();

        abort_if($product->client_id != $clientId, 403);

        $categories = ProductCategory::query()
            ->where('client_id', $clientId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Products/Form', [
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $clientId = $this->clientId();

        abort_if($product->client_id != $clientId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],

            'category_id' => [
                'nullable',
                Rule::exists('product_categories', 'id')
                    ->where(fn ($query) =>
                        $query->where('client_id', $clientId)
                    ),
            ],

            'sku' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) =>
                        $query->where('client_id', $clientId)
                    )
                    ->ignore($product->id),
            ],

            'unit_type' => [
                'required',
                'integer',
                Rule::in([1, 2, 3, 4, 5, 6]),
            ],

            'base_rate' => [
                'required',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                'integer',
                Rule::in([0, 1, 2]),
            ],
        ]);

        $product->update($validated);

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $clientId = $this->clientId();

        abort_if($product->client_id != $clientId, 403);

        $product->delete();

        return back();
    }
}