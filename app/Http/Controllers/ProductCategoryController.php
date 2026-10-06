<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        // Filhaal example:
        // Isko baad me authenticated user's client_id se replace karenge.
        // $clientId = auth()->user()->client_id;
        $clientId = 1;

        $categories = ProductCategory::query()
            ->where('client_id', $clientId)
            ->withCount('products')
            ->latest('id')
            ->get()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'status' => $category->status,
                'products_count' => $category->products_count,
            ]);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        // $clientId = auth()->user()->client_id;
        $clientId = 1;

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_categories', 'name')
                    ->where(fn ($query) => $query->where('client_id', $clientId)),
            ],
            'status' => [
                'required',
                'integer',
                Rule::in([0, 1, 2]),
            ],
        ]);

        ProductCategory::create([
            'client_id' => $clientId,
            'name' => $validated['name'],
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Category added successfully.');
    }

    public function update(Request $request, ProductCategory $category)
    {
        // $clientId = auth()->user()->client_id;
        $clientId = 1;

        abort_unless($category->client_id === $clientId, 403);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_categories', 'name')
                    ->where(fn ($query) => $query->where('client_id', $clientId))
                    ->ignore($category->id),
            ],
            'status' => [
                'required',
                'integer',
                Rule::in([0, 1, 2]),
            ],
        ]);

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(ProductCategory $category)
    {
        // $clientId = auth()->user()->client_id;
        $clientId = 1;

        abort_unless($category->client_id === $clientId, 403);

        if ($category->products()->exists()) {
            return back()->withErrors([
                'delete' => 'This category has products and cannot be deleted.',
            ]);
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}