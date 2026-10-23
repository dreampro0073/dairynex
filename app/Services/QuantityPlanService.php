<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class QuantityPlanService
{
    // Caller holds the dairy row lock, also used by generation and approval.
    public function refreshFuture(int $client, int $subscription, int $product): void
    {
        $lines = DB::table('delivery_items as items')->join('deliveries', function ($join) {
            $join->on('deliveries.id', '=', 'items.delivery_id')
                ->on('deliveries.client_id', '=', 'items.client_id');
        })
            ->where('items.client_id', $client)
            ->where('items.product_id', $product)
            ->where('deliveries.subscription_id', $subscription)
            ->whereIn('deliveries.status_id', [1, 2])
            ->whereDate('deliveries.delivery_date', '>', today())
            ->get(['items.id', 'deliveries.delivery_date']);

        foreach ($lines as $line) {
            $quantity = DB::table('subscription_change_requests')
                ->where('client_id', $client)
                ->where('subscription_id', $subscription)
                ->where('product_id', $product)
                ->where('status', 2)
                ->whereDate('effective_from', '<=', $line->delivery_date)
                ->orderByDesc('effective_from')->orderByDesc('id')->value('requested_quantity');

            if ($quantity !== null) {
                DB::table('delivery_items')
                    ->where('client_id', $client)
                    ->where('id', $line->id)
                    ->update(['planned_quantity' => $quantity, 'updated_at' => now()]);
            }
        }
    }
}
