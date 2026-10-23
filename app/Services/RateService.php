<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class RateService
{
    public function record(Product $product, string $date, string $rate): void
    {
        DB::table('product_rates')
            ->updateOrInsert([
                'client_id' => $product->client_id,
                'product_id' => $product->id,
                'effective_from' => $date,
            ], ['rate' => $rate, 'created_at' => now()]);

        DB::table('audit_logs')
            ->insert([
                'client_id' => $product->client_id,
                'user_id' => auth()->id(),
                'action' => 'product_rate_set',
                'table_name' => 'products',
                'record_id' => $product->id,
                'new_values' => json_encode(['rate' => $rate, 'effective_from' => $date]),
                'created_at' => now(),
            ]);
    }
}
