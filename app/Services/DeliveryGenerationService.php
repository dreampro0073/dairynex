<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Decimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DeliveryGenerationService
{
    public function generate(User $actor, string $date): int
    {
        abort_unless($actor->canSignIn() && $actor->role() === Role::ClientAdmin, 403);
        $day = CarbonImmutable::parse($date)->startOfDay();
        return DB::transaction(function () use ($actor, $day) {
            // Serialize tenant delivery generation, including retries, on MySQL.
            Client::lockForUpdate()->findOrFail($actor->client_id);
            $created = 0;
            $subscriptions = Subscription::where('client_id', $actor->client_id)->where('status', 1)
                ->where('auto_generate_deliveries', 1)->whereDate('start_date', '<=', $day)
                ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $day))->orderBy('id')->get();
            foreach ($subscriptions as $subscription) {
                $customer = Customer::where('client_id', $actor->client_id)->where('status', 1)->find($subscription->customer_id);
                if (!$customer || DB::table('subscription_pauses')->where('client_id', $actor->client_id)->where('subscription_id', $subscription->id)
                    ->where('status', 1)->whereDate('pause_from', '<=', $day)->whereDate('pause_to', '>=', $day)->exists()) {
                    continue;
                }
                $slots = [];
                foreach ($subscription->items()->where('client_id', $actor->client_id)->where('default_quantity', '>', 0)->get() as $item) {
                    $product = Product::where('client_id', $actor->client_id)->where('status', 1)->find($item->product_id);
                    if (!$product) { continue; }
                    $schedules = DB::table('subscription_schedules')->where('client_id', $actor->client_id)->where('subscription_item_id', $item->id)->get();
                    if ($schedules->isEmpty()) {
                        // Existing customer-form subscriptions had no schedule rows:
                        // their declared supply preference means daily default quantity.
                        $schedules = collect([(object) ['day_of_week' => $day->dayOfWeekIso, 'delivery_slot_id' => $customer->supply_preference,
                            'planned_quantity' => $item->default_quantity, 'active_from' => $subscription->start_date, 'active_to' => null]]);
                    }
                    $change = DB::table('subscription_change_requests')->where('client_id', $actor->client_id)
                        ->where('subscription_id', $subscription->id)->where('product_id', $item->product_id)->where('status', 2)
                        ->whereDate('effective_from', '<=', $day)->orderByDesc('effective_from')->orderByDesc('id')->first();
                    foreach ($schedules as $schedule) {
                        if ((int) $schedule->day_of_week !== $day->dayOfWeekIso || $schedule->active_from > $day->toDateString()
                            || ($schedule->active_to && $schedule->active_to < $day->toDateString())) { continue; }
                        $quantity = Decimal::scaled($change?->requested_quantity ?? $schedule->planned_quantity, 3, 'quantity');
                        if ($quantity <= 0) { continue; }
                        $rate = DB::table('product_rates')->where('client_id', $actor->client_id)->where('product_id', $item->product_id)
                            ->whereDate('effective_from', '<=', $day)->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $day))
                            ->orderByDesc('effective_from')->orderByDesc('id')->value('rate') ?? $item->rate;
                        $slotIds = (int) $schedule->delivery_slot_id === 3 ? [1, 2] : [(int) $schedule->delivery_slot_id];
                        foreach ($slotIds as $slot) {
                            if (isset($slots[$slot][$item->id])) {
                                throw ValidationException::withMessages(['schedule' => 'Overlapping schedules must be resolved before delivery generation.']);
                            }
                            $slots[$slot][$item->id] = ['product_id' => $item->product_id, 'subscription_item_id' => $item->id,
                                'planned_quantity' => Decimal::format($quantity, 3), 'rate' => $rate];
                        }
                    }
                }
                foreach ($slots as $slot => $items) {
                    $key = implode(':', [$actor->client_id, $subscription->id, $day->toDateString(), $slot]);
                    if (Delivery::where('generation_key', $key)->exists() || Delivery::where('client_id', $actor->client_id)
                        ->where('subscription_id', $subscription->id)->whereDate('delivery_date', $day)->where('delivery_slot_id', $slot)->exists()) { continue; }
                    $routes = DB::table('delivery_route_customers as assignments')->join('delivery_routes as routes', 'routes.id', '=', 'assignments.route_id')
                        ->where('assignments.client_id', $actor->client_id)->where('assignments.customer_id', $customer->id)
                        ->where('routes.client_id', $actor->client_id)->where('routes.status', 1)
                        ->where(fn ($q) => $q->whereNull('routes.route_date')->orWhereDate('routes.route_date', $day))->select('routes.*')->get();
                    if ($routes->count() !== 1) {
                        throw ValidationException::withMessages(['route' => 'Each scheduled customer must have exactly one active route.']);
                    }
                    $route = $routes->first();
                    $driver = User::where('client_id', $actor->client_id)->where('user_type_id', Role::DeliveryBoy->value)->where('status', 1)->find($route->delivery_boy_id);
                    if (!$driver) { throw ValidationException::withMessages(['route' => 'The route must have an active Delivery Boy.']); }
                    $delivery = Delivery::create(['client_id' => $actor->client_id, 'customer_id' => $customer->id, 'subscription_id' => $subscription->id,
                        'delivery_date' => $day->toDateString(), 'delivery_slot_id' => $slot, 'route_id' => $route->id, 'delivery_boy_id' => $driver->id, 'source_type' => 1, 'status_id' => 1]);
                    $delivery->forceFill(['generation_key' => $key, 'delivery_pin_hash' => $delivery->pin_required ? Hash::make((string) random_int(100000, 999999)) : null])->save();
                    foreach ($items as $item) {
                        DB::table('delivery_items')->insert(['client_id' => $actor->client_id, 'delivery_id' => $delivery->id, ...$item,
                            'actual_quantity' => 0, 'amount' => 0, 'created_at' => now(), 'updated_at' => now()]);
                    }
                    $created++;
                }
            }
            DB::table('audit_logs')->insert(['client_id' => $actor->client_id, 'user_id' => $actor->id,
                'action' => 'deliveries_generated', 'table_name' => 'deliveries', 'new_values' => json_encode(['date' => $day->toDateString(), 'created' => $created]), 'created_at' => now()]);
            return $created;
        }, 3);
    }
}
