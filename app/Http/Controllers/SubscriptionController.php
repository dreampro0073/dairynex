<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Subscription;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Subscription::where('client_id', $user->client_id);
        if ($user->role() === Role::Customer) {
            $query->whereIn('customer_id', Customer::where('client_id', $user->client_id)->where('user_id', $user->id)->select('id'));
        } else { abort_unless($user->role() === Role::ClientAdmin, 403); }
        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $query->with(['customer' => fn ($q) => $q->where('client_id', $user->client_id)->select('id', 'name'),
                'items' => fn ($q) => $q->where('client_id', $user->client_id), 'pauses' => fn ($q) => $q->where('client_id', $user->client_id)->orderByDesc('id'), 'items.product' => fn ($q) => $q->where('client_id', $user->client_id)->select('id', 'name')])
                ->latest('id')->paginate(20)->through(function ($subscription) use ($user) {
                    return [...$subscription->only(['id', 'subscription_no', 'customer_id', 'start_date', 'end_date', 'status']),
                        'customer_name' => $subscription->customer?->name, 'items' => $subscription->items->map(fn ($item) => ['id' => $item->id, 'product_id' => $item->product_id, 'name' => $item->product?->name, 'quantity' => $item->default_quantity, 'rate' => $item->rate]),
                        'pauses' => $subscription->pauses->take(5)->map->only(['pause_from', 'pause_to', 'reason'])->values(),
                    ];
                }),
            'customers' => $user->role() === Role::ClientAdmin ? Customer::where('client_id', $user->client_id)->where('status', 1)->get(['id', 'name']) : [],
            'products' => $user->role() === Role::ClientAdmin ? Product::where('client_id', $user->client_id)->where('status', 1)->get(['id', 'name', 'base_rate']) : [],
            'today' => today()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $client = Tenant::id();
        $data = $request->validate([
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where(fn ($q) => $q->where('client_id', $client)->where('status', 1))],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'], 'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'days' => ['required', 'array', 'min:1', 'max:7'], 'days.*' => ['required', 'integer', 'distinct', 'between:1,7'],
            'slot' => ['required', 'integer', Rule::in([1, 2, 3])], 'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where(fn ($q) => $q->where('client_id', $client)->where('status', 1))],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:999999.999', 'decimal:0,3'],
        ]);
        DB::transaction(function () use ($data, $client) {
            Client::lockForUpdate()->findOrFail($client);
            abort_unless(Customer::where('client_id', $client)->where('status', 1)->where('id', $data['customer_id'])->exists(), 404);
            $subscription = Subscription::create(['client_id' => $client, 'customer_id' => $data['customer_id'], 'subscription_no' => 'SUB-'.Str::uuid(),
                'start_date' => $data['start_date'], 'end_date' => $data['end_date'] ?? null, 'status' => 1, 'auto_generate_deliveries' => 1]);
            foreach ($data['items'] as $line) {
                $product = Product::where('client_id', $client)->findOrFail($line['product_id']);
                $item = $subscription->items()->create(['client_id' => $client, 'product_id' => $product->id, 'default_quantity' => $line['quantity'], 'rate' => $product->base_rate]);
                foreach ($data['days'] as $day) {
                    DB::table('subscription_schedules')->insert(['client_id' => $client, 'subscription_item_id' => $item->id, 'day_of_week' => $day,
                        'delivery_slot_id' => $data['slot'], 'planned_quantity' => $line['quantity'], 'active_from' => $data['start_date'], 'active_to' => $data['end_date'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        });
        return back()->with('success', 'Subscription and delivery schedule created.');
    }

    public function pause(Request $request, int $id)
    {
        $user = $request->user();
        abort_unless(in_array($user->role(), [Role::ClientAdmin, Role::Customer]), 403);
        $data = $request->validate(['pause_from' => ['required', 'date_format:Y-m-d', 'after:today'], 'pause_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:pause_from'], 'reason' => ['nullable', 'string', 'max:255']]);
        DB::transaction(function () use ($user, $data, $id) {
            Client::lockForUpdate()->findOrFail($user->client_id);
            $subscription = Subscription::where('client_id', $user->client_id)->findOrFail($id);
            if ($user->role() === Role::Customer) {
                abort_unless(Customer::where('client_id', $user->client_id)->where('user_id', $user->id)->where('id', $subscription->customer_id)->exists(), 403);
            }
            DB::table('deliveries')->where('client_id', $user->client_id)->where('subscription_id', $id)
                ->whereBetween('delivery_date', [$data['pause_from'], $data['pause_to']])->whereIn('status_id', [1, 2])
                ->update(['status_id' => 5, 'notes' => 'Cancelled for scheduled subscription pause.', 'updated_at' => now()]);
            DB::table('subscription_pauses')->insert(['client_id' => $user->client_id, 'subscription_id' => $id, ...$data, 'status' => 1, 'created_at' => now()]);
        });
        return back()->with('success', 'Pause scheduled. Supply resumes automatically after the end date. Pending deliveries in the pause period are cancelled without charge.');
    }
}
