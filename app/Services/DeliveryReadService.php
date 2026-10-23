<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\Role;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DeliveryReadService
{
    public function scopedQuery(User $user): Builder
    {
        abort_unless(in_array($user->role(), [Role::ClientAdmin, Role::DeliveryBoy, Role::Customer], true), 403);

        return Delivery::query()->join('customers', function ($join) {
            $join->on('customers.id', '=', 'deliveries.customer_id')
                ->on('customers.client_id', '=', 'deliveries.client_id');
        })->where('deliveries.client_id', $user->client_id)
            ->when($user->role() === Role::DeliveryBoy, fn ($query) => $query->where('deliveries.delivery_boy_id', $user->id))
            ->when($user->role() === Role::Customer, fn ($query) => $query->where('customers.user_id', $user->id));
    }

    public function find(User $user, int $id): array
    {
        $delivery = $this->scopedQuery($user)->where('deliveries.id', $id)
            ->select('deliveries.*', 'customers.name as customer_name')->firstOrFail();

        $items = DB::table('delivery_items as items')->join('products', function ($join) {
            $join->on('products.id', '=', 'items.product_id')
                ->on('products.client_id', '=', 'items.client_id');
        })->where('items.delivery_id', $delivery->id)->where('items.client_id', $user->client_id)
            ->get([
                'items.id',
                'products.name',
                'items.planned_quantity',
                'items.actual_quantity',
                'items.rate',
                'items.amount',
            ]);

        return [
            ...$delivery->only([
                'id',
                'delivery_date',
                'customer_name',
                'total_amount',
                'pin_required',
                'delivery_pin_verified_at',
                'completed_by_user_id',
            ]),
            'status' => DeliveryStatus::tryFrom($delivery->status_id)?->label() ?? 'Unknown',
            'pending' => in_array($delivery->status_id, [1, 2]),
            'items' => $items,
        ];
    }
}
