<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Subscription::where('client_id', $user->client_id);

        if ($user->role() === Role::Customer) {
            $query->whereIn('customer_id', Customer::where('client_id', $user->client_id)
                ->where('user_id', $user->id)
                ->select('id'));
        } else {
            abort_unless($user->role() === Role::ClientAdmin, 403);
        }

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $query->with([
                'customer' => fn ($q) => $q->where('client_id', $user->client_id)
                    ->select('id', 'name'),
                'items' => fn ($q) => $q->where('client_id', $user->client_id),
                'pauses' => fn ($q) => $q->where('client_id', $user->client_id)
                    ->orderByDesc('id'),
                'items.product' => fn ($q) => $q->where('client_id', $user->client_id)
                    ->select('id', 'name'),
            ])
                ->latest('id')->paginate(20)->through(function ($subscription) {
                    return [
                        ...$subscription->only(['id', 'subscription_no', 'customer_id', 'start_date', 'end_date', 'status']),
                        'customer_name' => $subscription->customer?->name,
                        'items' => $subscription->items->map(fn ($item) => [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'name' => $item->product?->name,
                            'quantity' => $item->default_quantity,
                            'rate' => $item->rate,
                        ]),
                        'pauses' => $subscription->pauses->take(5)->map->only(['pause_from', 'pause_to', 'reason'])
                            ->values(),
                    ];
                }),
            'customers' => $user->role() === Role::ClientAdmin ? Customer::where('client_id', $user->client_id)
                ->where('status', 1)
                ->get(['id', 'name']) : [],
            'products' => $user->role() === Role::ClientAdmin ? Product::where('client_id', $user->client_id)
                ->where('status', 1)
                ->get(['id', 'name', 'base_rate']) : [],
            'today' => today()->toDateString(),
        ]);
    }

    public function store(Request $request, SubscriptionService $subscriptions)
    {
        $client = Tenant::id();
        $data = $request->validate([
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')
                    ->where(fn ($q) => $q->where('client_id', $client)
                        ->where('status', 1)),
            ],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'days' => ['required', 'array', 'min:1', 'max:7'],
            'days.*' => ['required', 'integer', 'distinct', 'between:1,7'],
            'slot' => ['required', 'integer', Rule::in([1, 2, 3])],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')
                    ->where(fn ($q) => $q->where('client_id', $client)
                        ->where('status', 1)),
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:999999.999', 'decimal:0,3'],
        ]);

        $subscriptions->create($request->user(), $data);

        return back()->with('success', 'Subscription and delivery schedule created.');
    }

    public function pause(Request $request, int $id, SubscriptionService $subscriptions)
    {
        $user = $request->user();
        abort_unless(in_array($user->role(), [Role::ClientAdmin, Role::Customer]), 403);
        $data = $request->validate([
            'pause_from' => ['required', 'date_format:Y-m-d', 'after:today'],
            'pause_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:pause_from'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $subscriptions->pause($user, $id, $data);

        return back()
            ->with(
                'success',
                'Pause scheduled. Supply resumes automatically after the end date. Pending deliveries in the pause period are cancelled without charge.'
            );
    }
}
