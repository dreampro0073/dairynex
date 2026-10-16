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
        return \App\Support\Tenant::id();
    }

    public function index()
    {
        $clientId = $this->clientId();

        $products = Product::query()
            ->where('client_id', $clientId)
            ->with(['category' => fn ($query) => $query->where('client_id', $clientId)->select('id', 'name')])
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
                'max:9999999.99',
                'decimal:0,2',
            ],

            'status' => [
                'required',
                'integer',
                Rule::in([0, 1, 2]),
            ],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($clientId, $validated) {
            $product = Product::create(['client_id' => $clientId, ...$validated]);
            app(\App\Services\RateService::class)->record($product, today()->toDateString(), $product->base_rate);
        });

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
            'rates' => \Illuminate\Support\Facades\DB::table('product_rates')->where('client_id', $clientId)->where('product_id', $product->id)->orderByDesc('effective_from')->limit(50)->get(),
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
                'max:9999999.99',
                'decimal:0,2',
            ],

            'status' => [
                'required',
                'integer',
                Rule::in([0, 1, 2]),
            ],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($product, $validated) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $changed = (string) $product->base_rate !== number_format((float) $validated['base_rate'], 2, '.', '');
            $product->update($validated);
            if ($changed) { app(\App\Services\RateService::class)->record($product, today()->toDateString(), $product->base_rate); }
        });

        return redirect()
            ->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function rate(Request $request, Product $product)
    {
        abort_unless($product->client_id === $this->clientId(), 403);
        $data = $request->validate(['rate' => ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
            'effective_from' => ['required', 'date_format:Y-m-d', 'after:today']]);
        \Illuminate\Support\Facades\DB::transaction(function () use ($product, $data) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            app(\App\Services\RateService::class)->record($product, $data['effective_from'], $data['rate']);
        });
        return back()->with('success', 'Future product rate saved. Existing deliveries keep their recorded rate.');
    }

    public function destroy(Product $product)
    {
        $clientId = $this->clientId();

        abort_if($product->client_id != $clientId, 403);

        if (\Illuminate\Support\Facades\DB::table('delivery_items')->where('product_id', $product->id)->exists()) {
            return back()->withErrors(['delete' => 'This record has business history and cannot be deleted. Deactivate it instead.']);
        }
        if (\Illuminate\Support\Facades\DB::table('subscription_items')->where('product_id', $product->id)->exists()) {
            return back()->withErrors(['delete' => 'This record has business history and cannot be deleted. Deactivate it instead.']);
        }
        if (\Illuminate\Support\Facades\DB::table('invoice_items')->where('product_id', $product->id)->exists()) {
            return back()->withErrors(['delete' => 'This record has business history and cannot be deleted. Deactivate it instead.']);
        }
        if (\Illuminate\Support\Facades\DB::table('inventory_transactions')->where('product_id', $product->id)->exists()) {
            return back()->withErrors(['delete' => 'This record has business history and cannot be deleted. Deactivate it instead.']);
        }
        if (\Illuminate\Support\Facades\DB::table('product_rates')->where('product_id', $product->id)->exists()) {
            return back()->withErrors(['delete' => 'This record has business history and cannot be deleted. Deactivate it instead.']);
        }

        $product->delete();

        return back();
    }
}