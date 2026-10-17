<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Tenant-safe subscription mutations shared by web and API entry points. */
class SubscriptionService
{
    public function create(User $actor, array $data): Subscription
    {
        abort_unless($actor->canSignIn() && $actor->role() === Role::ClientAdmin, 403);
        $clientId = $actor->client_id;

        return DB::transaction(function () use ($data, $clientId) {
            Client::lockForUpdate()->findOrFail($clientId);
            abort_unless(Customer::where('client_id', $clientId)->where('status', 1)->whereKey($data['customer_id'])->exists(), 404);
            $subscription = Subscription::create(['client_id' => $clientId, 'customer_id' => $data['customer_id'], 'subscription_no' => 'SUB-'.Str::uuid(),
                'start_date' => $data['start_date'], 'end_date' => $data['end_date'] ?? null, 'status' => 1, 'auto_generate_deliveries' => 1]);
            foreach ($data['items'] as $line) {
                $product = Product::where('client_id', $clientId)->where('status', 1)->findOrFail($line['product_id']);
                $item = $subscription->items()->create(['client_id' => $clientId, 'product_id' => $product->id, 'default_quantity' => $line['quantity'], 'rate' => $product->base_rate]);
                foreach ($data['days'] as $day) {
                    DB::table('subscription_schedules')->insert(['client_id' => $clientId, 'subscription_item_id' => $item->id, 'day_of_week' => $day,
                        'delivery_slot_id' => $data['slot'], 'planned_quantity' => $line['quantity'], 'active_from' => $data['start_date'], 'active_to' => $data['end_date'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
                }
            }

            return $subscription;
        }, 3);
    }

    public function pause(User $actor, int $id, array $data): void
    {
        abort_unless($actor->canSignIn() && in_array($actor->role(), [Role::ClientAdmin, Role::Customer], true), 403);
        DB::transaction(function () use ($actor, $id, $data) {
            Client::lockForUpdate()->findOrFail($actor->client_id);
            $subscription = Subscription::where('client_id', $actor->client_id)->findOrFail($id);
            if ($actor->role() === Role::Customer) {
                abort_unless(Customer::where('client_id', $actor->client_id)->where('user_id', $actor->id)->whereKey($subscription->customer_id)->exists(), 403);
            }
            DB::table('deliveries')->where('client_id', $actor->client_id)->where('subscription_id', $id)
                ->whereBetween('delivery_date', [$data['pause_from'], $data['pause_to']])->whereIn('status_id', [1, 2])
                ->update(['status_id' => 5, 'notes' => 'Cancelled for scheduled subscription pause.', 'updated_at' => now()]);
            DB::table('subscription_pauses')->insert(['client_id' => $actor->client_id, 'subscription_id' => $id, ...$data, 'status' => 1, 'created_at' => now()]);
        }, 3);
    }
}
