<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DeliveryRoute;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Decimal;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $clientId = Tenant::id();

        $movements = DB::table('customer_ledger')
            ->where('client_id', $clientId)
            ->selectRaw('customer_id, SUM(debit-credit) as movement')
            ->groupBy('customer_id')
            ->pluck('movement', 'customer_id');

        $customers = Customer::query()
            ->where('client_id', $clientId)
            ->with([
                'defaultAddress' => fn ($query) => $query->where('client_id', $clientId),
                'routes' => fn ($query) => $query->where('delivery_routes.client_id', $clientId)
                    ->select('delivery_routes.id', 'name'),
                'subscriptions' => fn ($query) => $query->where('client_id', $clientId),
                'subscriptions.items' => fn ($query) => $query->where('client_id', $clientId),
                'subscriptions.items.product' => fn ($query) => $query->where('client_id', $clientId)
                    ->select('id', 'name'),
            ])
            ->latest('id')
            ->get()
            ->map(function ($customer) use ($movements) {
                $route = $customer->routes->first();

                $subscription = $customer->subscriptions
                    ->where('status', 1)
                    ->first();

                $todayQty = '-';

                if ($subscription) {
                    $parts = [];

                    foreach ($subscription->items as $item) {
                        $qty = (float) $item->default_quantity;

                        $parts[] =
                            $qty.' '.($item->product?->name ?? '');
                    }

                    $todayQty = count($parts)
                        ? implode(' + ', $parts)
                        : '-';
                }

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'delivery_pin_required' => $customer->delivery_pin_required,
                    'mobile' => $customer->mobile,

                    'route_id' => $route?->id,
                    'route' => $route?->name ?? '-',

                    'customer_type' => $customer->customer_type,
                    'status' => $customer->status,

                    'today_qty' => $todayQty,

                    'outstanding' => (float) $customer->opening_balance + (float) ($movements[$customer->id] ?? 0),
                ];
            });

        $total = $customers->count();

        $active = $customers
            ->where('status', 1)
            ->count();

        $paused = $customers
            ->where('status', 2)
            ->count();

        $overdue = $customers
            ->where('outstanding', '>', 0)
            ->count();

        $routeCount = $customers
            ->pluck('route_id')
            ->filter()
            ->unique()
            ->count();

        $activePercentage = $total > 0
            ? round(($active / $total) * 100)
            : 0;

        return Inertia::render('Customers/Index', [
            'customers' => $customers,

            'stats' => [
                'total' => $total,
                'active' => $active,
                'paused' => $paused,
                'overdue' => $overdue,
                'routes' => $routeCount,
                'active_percentage' => $activePercentage,
            ],
        ]);
    }

    public function create()
    {
        $clientId = Tenant::id();

        return Inertia::render('Customers/Form', [
            'customer' => null,
            'pinRequiredDefault' => (bool) config('dairynex.delivery_pin_required', true),

            'routes' => DeliveryRoute::query()
                ->where('client_id', $clientId)
                ->where('status', 1)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]),

            'products' => Product::query()
                ->where('client_id', $clientId)
                ->where('status', 1)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'base_rate',
                    'unit_type',
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $clientId = Tenant::id();

        $validated = $this->validateCustomer(
            $request,
            $clientId
        );

        $customer = DB::transaction(function () use (
            $validated,
            $clientId
        ) {
            Client::lockForUpdate()->findOrFail($clientId);
            $customer = Customer::create([
                'client_id' => $clientId,

                'customer_code' => $this->generateCustomerCode(
                    $clientId
                ),

                'name' => $validated['name'],
                'delivery_pin_required' => $validated['delivery_pin_required'] ?? (bool) config('dairynex.delivery_pin_required', true),
                'mobile' => $validated['mobile'] ?? null,
                'email' => $validated['email'] ?? null,

                'date_of_birth' => $validated['date_of_birth'] ?? null,

                'gender' => $validated['gender'] ?? null,

                'opening_balance' => $validated['opening_balance'] ?? 0,

                'credit_limit' => $validated['credit_limit'] ?? 0,

                'customer_type' => $validated['customer_type'],

                'status' => $validated['status'] ?? 1,

                'supply_preference' => $validated['supply_preference'],
            ]);

            DB::table('audit_logs')->insert([
                'client_id' => $clientId,
                'user_id' => auth()->id(),
                'action' => 'customer_saved',
                'table_name' => 'customers',
                'record_id' => $customer->id,
                'new_values' => json_encode(['delivery_pin_required' => $customer->delivery_pin_required]),
                'created_at' => now(),
            ]);

            $this->saveAddress(
                $customer,
                $validated,
                $clientId
            );

            $this->saveRoute(
                $customer,
                $validated,
                $clientId
            );

            if (
                ($validated['action'] ?? null)
                === 'save_subscription'
            ) {
                $this->createSubscription(
                    $customer,
                    $validated,
                    $clientId
                );
            }

            return $customer;
        });

        return redirect()
            ->route('customers.index')
            ->with(
                'success',
                'Customer added successfully.'
            );
    }

    public function edit(Customer $customer)
    {
        $clientId = Tenant::id();

        abort_unless(
            $customer->client_id === $clientId,
            403
        );

        $customer->load([
            'defaultAddress' => fn ($query) => $query->where('client_id', $clientId),
            'routes' => fn ($query) => $query->where('delivery_routes.client_id', $clientId),
            'subscriptions' => function ($query) use ($clientId) {
                $query->where('client_id', $clientId)->where('status', 1)
                    ->latest('id');
            },
            'subscriptions.items' => fn ($query) => $query->where('client_id', $clientId),
        ]);

        $subscription = $customer
            ->subscriptions
            ->first();

        return Inertia::render('Customers/Form', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'delivery_pin_required' => $customer->delivery_pin_required,
                'mobile' => $customer->mobile,
                'email' => $customer->email,

                'date_of_birth' => $customer->date_of_birth,

                'gender' => $customer->gender,

                'customer_type' => $customer->customer_type,

                'opening_balance' => $customer->opening_balance,

                'credit_limit' => $customer->credit_limit,

                'status' => $customer->status,

                'supply_preference' => $customer->supply_preference,

                'address_line1' => $customer->defaultAddress
                    ?->address_line1,

                'address_line2' => $customer->defaultAddress
                    ?->address_line2,

                'landmark' => $customer->defaultAddress
                    ?->landmark,

                'city' => $customer->defaultAddress
                    ?->city,

                'state' => $customer->defaultAddress
                    ?->state,

                'pincode' => $customer->defaultAddress
                    ?->pincode,

                'latitude' => $customer->defaultAddress
                    ?->latitude,

                'longitude' => $customer->defaultAddress
                    ?->longitude,

                'route_id' => $customer->routes
                    ->first()?->id,

                'subscription_items' => $subscription
                    ?->items
                    ->map(fn ($item) => [
                        'id' => $item->id,

                        'product_id' => $item->product_id,

                        'quantity' => $item->default_quantity,

                        'rate' => $item->rate,
                    ])
                    ->values()
                    ->toArray()
                    ?? [],
            ],

            'routes' => DeliveryRoute::query()
                ->where('client_id', $clientId)
                ->where('status', 1)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ]),

            'products' => Product::query()
                ->where('client_id', $clientId)
                ->where('status', 1)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'base_rate',
                    'unit_type',
                ]),
        ]);
    }

    public function update(
        Request $request,
        Customer $customer
    ) {
        $clientId = Tenant::id();

        abort_unless(
            $customer->client_id === $clientId,
            403
        );

        $validated = $this->validateCustomer(
            $request,
            $clientId,
            $customer->id
        );

        $validated['delivery_pin_required'] ??= $customer->delivery_pin_required;
        $validated['status'] ??= $customer->status;

        DB::transaction(function () use (
            $customer,
            $validated,
            $clientId
        ) {
            Client::lockForUpdate()->findOrFail($clientId);
            $customer = Customer::where('client_id', $clientId)->lockForUpdate()->findOrFail($customer->id);

            if (
                Decimal::scaled($validated['opening_balance'] ?? 0, 2) !== Decimal::scaled($customer->opening_balance ?? 0, 2)
                && DB::table('customer_ledger')
                    ->where('client_id', $clientId)
                    ->where('customer_id', $customer->id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'Opening balance cannot be changed after ledger activity. Use an authorized adjustment.',
                ]);
            }

            $customer->update([
                'status' => $validated['status'],
                'name' => $validated['name'],
                'delivery_pin_required' => $validated['delivery_pin_required'] ?? (bool) config('dairynex.delivery_pin_required', true),
                'mobile' => $validated['mobile'] ?? null,
                'email' => $validated['email'] ?? null,

                'date_of_birth' => $validated['date_of_birth'] ?? null,

                'gender' => $validated['gender'] ?? null,

                'opening_balance' => $validated['opening_balance'] ?? 0,

                'credit_limit' => $validated['credit_limit'] ?? 0,

                'customer_type' => $validated['customer_type'],

                'supply_preference' => $validated['supply_preference'],
            ]);

            DB::table('audit_logs')->insert([
                'client_id' => $clientId,
                'user_id' => auth()->id(),
                'action' => 'customer_saved',
                'table_name' => 'customers',
                'record_id' => $customer->id,
                'new_values' => json_encode(['delivery_pin_required' => $customer->delivery_pin_required]),
                'created_at' => now(),
            ]);

            $this->saveAddress(
                $customer,
                $validated,
                $clientId
            );

            $this->saveRoute(
                $customer,
                $validated,
                $clientId
            );

            if (
                ($validated['action'] ?? null)
                === 'save_subscription'
            ) {
                $this->createOrUpdateSubscription(
                    $customer,
                    $validated,
                    $clientId
                );
            }
        });

        return redirect()
            ->route('customers.index')
            ->with(
                'success',
                'Customer updated successfully.'
            );
    }

    public function show(Customer $customer)
    {
        abort_unless($customer->client_id === Tenant::id(), 403);

        return Inertia::render('Customers/Show', [
            'customer' => $customer->only(['id', 'name', 'mobile', 'email', 'status', 'delivery_pin_required', 'user_id']),
            'logins' => User::where('client_id', $customer->client_id)
                ->where('user_type_id', Role::Customer->value)
                ->where('status', 1)
                ->whereNotIn('id', Customer::whereNotNull('user_id')
                    ->select('user_id'))
                ->get(['id', 'name', 'mobile']),
        ]);
    }

    public function destroy(Customer $customer)
    {
        $clientId = Tenant::id();

        abort_unless(
            $customer->client_id === $clientId,
            403
        );

        DB::transaction(function () use ($customer, $clientId) {
            Client::lockForUpdate()->findOrFail($clientId);
            $customer = Customer::where('client_id', $clientId)->lockForUpdate()->findOrFail($customer->id);

            foreach (['deliveries', 'customer_ledger', 'payments', 'invoices', 'payment_plans', 'subscriptions'] as $table) {
                if (DB::table($table)->where('customer_id', $customer->id)->exists()) {
                    throw ValidationException::withMessages(['delete' => 'This customer has business history. Deactivate the customer instead.']);
                }
            }

            DB::table('delivery_route_customers')
                ->where('client_id', $clientId)
                ->where('customer_id', $customer->id)
                ->delete();

            $customer->addresses()->where('client_id', $clientId)->delete();
            $customer->delete();
        }, 3);

        return back()->with(
            'success',
            'Customer deleted successfully.'
        );
    }

    private function validateCustomer(
        Request $request,
        int $clientId,
        ?int $customerId = null
    ): array {
        return $request->validate([
            'delivery_pin_required' => ['sometimes', 'required', 'boolean'],
            'status' => ['sometimes', 'required', 'integer', Rule::in([0, 1, 2])],
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:20',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],

            'gender' => [
                'nullable',
                'integer',
                Rule::in([1, 2, 3]),
            ],

            'customer_type' => [
                'required',
                'integer',
                Rule::in([1, 2, 3]),
            ],

            'credit_limit' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'opening_balance' => [
                'nullable',
                'numeric',
            ],

            'address_line1' => [
                'required',
                'string',
                'max:255',
            ],

            'address_line2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'landmark' => [
                'nullable',
                'string',
                'max:150',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'pincode' => [
                'nullable',
                'string',
                'max:20',
            ],

            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],

            'route_id' => [
                'nullable',
                Rule::exists(
                    'delivery_routes',
                    'id'
                )->where(
                    fn ($query) => $query->where(
                        'client_id',
                        $clientId
                    )
                ),
            ],

            'supply_preference' => [
                'required',
                'integer',
                Rule::in([1, 2, 3]),
            ],

            'action' => [
                'nullable',
                Rule::in([
                    'save',
                    'save_subscription',
                ]),
            ],

            'subscription_items' => [
                'nullable',
                'array',
            ],

            'subscription_items.*.product_id' => [
                'nullable',
                'distinct',
                Rule::exists(
                    'products',
                    'id'
                )->where(
                    fn ($query) => $query->where(
                        'client_id',
                        $clientId
                    )
                ),
            ],

            'subscription_items.*.quantity' => [
                'nullable',
                'numeric',
                'min:0.001',
                'max:999999.999',
                'decimal:0,3',
            ],

            'subscription_items.*.rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999.99',
                'decimal:0,2',
            ],
        ]);
    }

    private function saveAddress(
        Customer $customer,
        array $validated,
        int $clientId
    ): void {
        CustomerAddress::updateOrCreate(
            [
                'client_id' => $clientId,
                'customer_id' => $customer->id,
                'is_default' => 1,
            ],
            [
                'client_id' => $clientId,
                'address_type' => 1,

                'address_line1' => $validated['address_line1'],

                'address_line2' => $validated['address_line2']
                    ?? null,

                'landmark' => $validated['landmark']
                    ?? null,

                'city' => $validated['city']
                    ?? null,

                'state' => $validated['state']
                    ?? null,

                'pincode' => $validated['pincode']
                    ?? null,

                'latitude' => $validated['latitude']
                    ?? null,

                'longitude' => $validated['longitude']
                    ?? null,
            ]
        );
    }

    private function saveRoute(
        Customer $customer,
        array $validated,
        int $clientId
    ): void {
        DB::table('delivery_route_customers')->where('client_id', $clientId)
            ->where(
                'customer_id',
                $customer->id
            )
            ->delete();

        if (empty($validated['route_id'])) {
            return;
        }

        $lastSequence = DB::table(
            'delivery_route_customers'
        )
            ->where(
                'client_id',
                $clientId
            )
            ->where(
                'route_id',
                $validated['route_id']
            )
            ->max('sequence_no');

        DB::table('delivery_route_customers')
            ->insert([
                'client_id' => $clientId,

                'route_id' => $validated['route_id'],

                'customer_id' => $customer->id,

                'sequence_no' => ((int) $lastSequence) + 1,

                'created_at' => now(),
            ]);
    }

    private function createSubscription(
        Customer $customer,
        array $validated,
        int $clientId
    ): void {
        $items = collect(
            $validated['subscription_items']
            ?? []
        )->filter(
            fn ($item) => ! empty($item['product_id'])
        );

        if ($items->isEmpty()) {
            return;
        }

        $subscription = Subscription::create([
            'client_id' => $clientId,
            'customer_id' => $customer->id,

            'subscription_no' => $this->generateSubscriptionNo(
                $clientId
            ),

            'billing_cycle_id' => 1,
            'start_date' => now()->toDateString(),
            'status' => 1,
            'auto_generate_deliveries' => 1,
        ]);

        foreach ($items as $item) {
            $subscription->items()->create([
                'client_id' => $clientId,

                'product_id' => $item['product_id'],

                'default_quantity' => $item['quantity'] ?? 1,

                'rate' => $item['rate'] ?? 0,
            ]);
        }
    }

    private function createOrUpdateSubscription(
        Customer $customer,
        array $validated,
        int $clientId
    ): void {
        $items = collect(
            $validated['subscription_items']
            ?? []
        )->filter(
            fn ($item) => ! empty($item['product_id'])
        );

        $subscription = $customer
            ->subscriptions()
            ->where('status', 1)
            ->latest('id')
            ->first();

        if ($items->isEmpty()) {
            if ($subscription) {
                $subscription->items()->update(['default_quantity' => 0]);
            }

            return;
        }

        if (! $subscription) {
            $subscription =
                Subscription::create([
                    'client_id' => $clientId,

                    'customer_id' => $customer->id,

                    'subscription_no' => $this
                        ->generateSubscriptionNo(
                            $clientId
                        ),

                    'billing_cycle_id' => 1,
                    'start_date' => now()->toDateString(),

                    'status' => 1,

                    'auto_generate_deliveries' => 1,
                ]);
        }

        $retained = $items->pluck('product_id')->all();
        $subscription->items()->whereNotIn('product_id', $retained)->update(['default_quantity' => 0]);

        foreach ($items as $item) {
            $subscription->items()->updateOrCreate(['product_id' => $item['product_id']], [
                'client_id' => $clientId,

                'product_id' => $item['product_id'],

                'default_quantity' => $item['quantity'] ?? 1,

                'rate' => $item['rate'] ?? 0,
            ]);
        }
    }

    private function generateCustomerCode(
        int $clientId
    ): string {
        $nextId = (
            Customer::where(
                'client_id',
                $clientId
            )->max('id') ?? 0
        ) + 1;

        return 'CUS-'.str_pad(
            $nextId,
            5,
            '0',
            STR_PAD_LEFT
        );
    }

    private function generateSubscriptionNo(
        int $clientId
    ): string {
        $nextId = (
            Subscription::where(
                'client_id',
                $clientId
            )->max('id') ?? 0
        ) + 1;

        return 'SUB-'.str_pad(
            $nextId,
            5,
            '0',
            STR_PAD_LEFT
        );
    }
}
