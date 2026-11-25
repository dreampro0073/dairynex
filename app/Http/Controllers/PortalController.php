<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Models\Customer;
use App\Services\LedgerService;
use App\Support\Decimal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PortalController extends Controller
{
    public function platform()
    {
        return redirect()->route('platform.dashboard');
    }

    public function customer(Request $request)
    {
        $user = $request->user();
        $client = $user->client_id;
        $customer = Customer::where('client_id', $client)
            ->where('user_id', $user->id)
            ->with('defaultAddress')
            ->first();

        if (! $customer) {
            return Inertia::render('Portals/Overview', [
                'title' => 'Customer Account',
                'metrics' => [],
                'records' => null,
                'notice' => 'This login is not linked to a customer record yet. Contact your dairy administrator to link your account.',
            ]);
        }

        $tab = $request->query('tab', 'home');
        abort_unless(in_array($tab, ['home', 'bill', 'purchases', 'subscription', 'profile'], true), 404);
        $deliveries = DB::table('deliveries')
            ->where('client_id', $client)
            ->where('customer_id', $customer->id);

        $month = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $recent = (clone $deliveries)
            ->whereDate('delivery_date', '>=', $month->toDateString())
            ->whereDate('delivery_date', '<=', $monthEnd->toDateString())
            ->orderByDesc('delivery_date')
            ->limit(6)
            ->get();

        $todayDelivery = (clone $deliveries)
            ->whereDate('delivery_date', today())
            ->orderBy('delivery_slot_id')
            ->get(['id', 'delivery_date', 'delivery_slot_id', 'status_id', 'total_amount']);

        $deliveryItems = DB::table('delivery_items')->join('products', function ($join) {
            $join->on('products.id', '=', 'delivery_items.product_id')
                ->on('products.client_id', '=', 'delivery_items.client_id');
        })
            ->where('delivery_items.client_id', $client)
            ->whereIn('delivery_items.delivery_id', $recent->pluck('id')->merge($todayDelivery->pluck('id'))->unique())
            ->get([
                'delivery_items.delivery_id',
                'delivery_items.actual_quantity',
                'products.name',
                'products.unit_type',
            ])
            ->groupBy('delivery_id');

        $unitLabels = [1 => 'L', 2 => 'ml', 3 => 'kg', 4 => 'g', 5 => 'pc', 6 => 'pkt'];
        $deliveredQuantity = fn ($items) => $items
            ->filter(fn ($item) => (float) $item->actual_quantity > 0)
            ->map(fn ($item) => $item->name.' '.number_format((float) $item->actual_quantity, 3).' '.($unitLabels[$item->unit_type] ?? ''))
            ->implode(', ');

        $counterSales = DB::table('walk_in_sales')
            ->leftJoin('ref_payment_methods as methods', 'methods.id', '=', 'walk_in_sales.payment_method_id')
            ->where('client_id', $client)
            ->where('walk_in_sales.customer_id', $customer->id)
            ->where('walk_in_sales.status', 1)
            ->whereDate('walk_in_sales.sold_at', '>=', $month->toDateString())
            ->whereDate('walk_in_sales.sold_at', '<=', $monthEnd->toDateString())
            ->orderByDesc('walk_in_sales.sold_at')
            ->limit(6)
            ->get([
                'walk_in_sales.id', 'walk_in_sales.sold_at', 'walk_in_sales.total',
                'walk_in_sales.due_amount', 'walk_in_sales.receipt_no',
                DB::raw("COALESCE(methods.name, 'Credit') as payment_method"),
            ]);

        $recentHistory = $recent->map(function ($delivery) use ($deliveryItems, $deliveredQuantity) {
            $isDelivered = in_array((int) $delivery->status_id, [3, 8]);

            return [
                'id' => 'delivery-'.$delivery->id,
                'record_type' => 'delivery',
                'date' => $delivery->delivery_date,
                'status' => DeliveryStatus::tryFrom((int) $delivery->status_id)?->label() ?? 'Pending',
                'amount' => $delivery->total_amount,
                'detail' => $isDelivered
                    ? ($deliveredQuantity($deliveryItems->get($delivery->id, collect())) ?: 'No milk delivered')
                    : null,
            ];
        })->concat($counterSales->map(fn ($sale) => [
            'id' => 'counter-sale-'.$sale->id,
            'record_type' => 'counter_sale',
            'date' => $sale->sold_at,
            'status' => 'Counter Sale · '.(strtolower($sale->payment_method) === 'upi'
                ? 'UPI'
                : ucfirst($sale->payment_method)),
            'amount' => $sale->total,
            'detail' => $sale->due_amount > 0
                ? substr((string) $sale->sold_at, 0, 10).' · Due '.Decimal::format($sale->due_amount).' · '.$sale->receipt_no
                : substr((string) $sale->sold_at, 0, 10).' · Paid · '.$sale->receipt_no,
        ]))->sortByDesc('date')->take(6)->values();

        $monthlyQty = DB::table('delivery_items')
            ->where('client_id', $client)
            ->whereIn('delivery_id', (clone $deliveries)
                ->whereIn('status_id', [3, 8])
                ->whereDate('delivery_date', '>=', $month->toDateString())
                ->whereDate('delivery_date', '<=', $monthEnd->toDateString())
                ->select('id'))
            ->sum('actual_quantity');

        $invoices = DB::table('invoices')
            ->where('client_id', $client)
            ->where('customer_id', $customer->id)
            ->orderByDesc('invoice_date')
            ->limit(12)
            ->get();

        $purchases = DB::table('walk_in_sales as sales')
            ->leftJoin('ref_payment_methods as methods', 'methods.id', '=', 'sales.payment_method_id')
            ->where('sales.client_id', $client)
            ->where('sales.customer_id', $customer->id)
            ->where('sales.status', 1)
            ->orderByDesc('sales.sold_at')
            ->limit(50)
            ->get([
                'sales.id', 'sales.receipt_no', 'sales.sold_at', 'sales.total',
                'sales.paid_amount', 'sales.due_amount', 'sales.payment_method_id',
                DB::raw("COALESCE(methods.name, 'Credit / Outstanding') as payment_method"),
            ]);

        $purchaseIds = $purchases->pluck('id');
        $purchaseItems = DB::table('walk_in_sale_items')
            ->where('client_id', $client)
            ->whereIn('sale_id', $purchaseIds)
            ->orderBy('id')
            ->get(['sale_id', 'product_name', 'quantity', 'rate', 'amount'])
            ->groupBy('sale_id');
        $purchases->each(fn ($sale) => $sale->items = $purchaseItems->get($sale->id, collect())->values());

        $subscription = DB::table('subscriptions')
            ->where('client_id', $client)
            ->where('customer_id', $customer->id)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();

        $items = $subscription ? DB::table('subscription_items')->join('products', function ($j) {
            $j->on('products.id', '=', 'subscription_items.product_id')
                ->on('products.client_id', '=', 'subscription_items.client_id');
        })
            ->where('subscription_items.client_id', $client)
            ->where('subscription_items.subscription_id', $subscription->id)
            ->get(['products.name', 'subscription_items.default_quantity', 'subscription_items.rate']) : collect();

        $todayDelivery->each(function ($delivery) use ($deliveryItems) {
            $delivery->items = ($deliveryItems->get($delivery->id, collect()))
                ->map(fn ($item) => [
                    'name' => $item->name,
                    'actual_quantity' => $item->actual_quantity,
                    'unit_type' => $item->unit_type,
                ])
                ->values();
        });

        return Inertia::render('Portals/Customer', [
            'tab' => $tab,
            'customer' => $customer,
            'clientName' => $user->client?->name ?? 'DairyNex',
            'stats' => [
                'monthly_quantity' => number_format((float) $monthlyQty, 3),
                'monthly_bill' => number_format($invoices->where('invoice_date', '>=', $month->toDateString())
                    ->sum('total'), 2, '.', ''),
                'outstanding' => Decimal::format(app(LedgerService::class)->balance($customer)),
            ],
            'deliveries' => $recentHistory,
            'todayDelivery' => $todayDelivery,
            'invoices' => $invoices,
            'purchases' => $purchases,
            'subscription' => $subscription,
            'items' => $items,
            'payments' => DB::table('payments')
                ->where('client_id', $client)
                ->where('customer_id', $customer->id)
                ->where('status', 1)
                ->orderByDesc('payment_date')
                ->limit(12)
                ->get(),
            'address' => $customer->defaultAddress,
        ]);
    }

    public function delivery(Request $request)
    {
        $user = $request->user();
        $client = $user->client_id;
        $tab = $request->query('tab', 'deliveries');
        abort_unless(in_array($tab, ['deliveries', 'route', 'collections', 'profile'], true), 404);
        $today = DB::table('deliveries')
            ->where('deliveries.client_id', $client)
            ->where('deliveries.delivery_boy_id', $user->id)
            ->whereDate('deliveries.delivery_date', today());

        $rows = (clone $today)->join('customers', function ($j) {
            $j->on('customers.id', '=', 'deliveries.customer_id')
                ->on('customers.client_id', '=', 'deliveries.client_id');
        })->leftJoin('customer_addresses', function ($j) {
            $j->on('customer_addresses.customer_id', '=', 'customers.id')
                ->on('customer_addresses.client_id', '=', 'customers.client_id')
                ->where('customer_addresses.is_default', 1);
        })
            ->leftJoin('delivery_routes', function ($j) {
                $j->on('delivery_routes.id', '=', 'deliveries.route_id')
                    ->on('delivery_routes.client_id', '=', 'deliveries.client_id');
            })->orderBy('delivery_routes.name')->orderBy('deliveries.id')
            ->get([
                'deliveries.id',
                'deliveries.status_id',
                'deliveries.delivery_slot_id',
                'deliveries.total_amount',
                'customers.name as customer_name',
                'customers.mobile',
                'customer_addresses.address_line1',
                'customer_addresses.city',
                'delivery_routes.name as route_name',
            ]);

        $items = DB::table('delivery_items')->join('products', function ($j) {
            $j->on('products.id', '=', 'delivery_items.product_id')
                ->on('products.client_id', '=', 'delivery_items.client_id');
        })
            ->where('delivery_items.client_id', $client)
            ->whereIn('delivery_items.delivery_id', $rows->pluck('id'))
            ->get(['delivery_items.delivery_id', 'delivery_items.planned_quantity', 'products.name'])
            ->groupBy('delivery_id');

        $rows = $rows->map(fn ($row) => [
            'id' => $row->id,
            'customer' => $row->customer_name,
            'mobile' => $row->mobile,
            'address' => trim(($row->address_line1 ?? '').' '.($row->city ?? '')),
            'route' => $row->route_name ?? 'Unassigned',
            'slot' => [1 => 'Morning', 2 => 'Evening', 3 => 'Morning & evening'][$row->delivery_slot_id] ?? 'Scheduled',
            'status' => DeliveryStatus::tryFrom((int) $row->status_id)?->label() ?? 'Pending',
            'status_id' => (int) $row->status_id,
            'amount' => $row->total_amount,
            'products' => ($items[$row->id] ?? collect())
                ->map(fn ($i) => $i->name.' · '.$i->planned_quantity)
                ->values(),
        ]);

        $routeCustomers = DB::table('delivery_routes')->join('delivery_route_customers', function ($j) {
            $j->on('delivery_route_customers.route_id', '=', 'delivery_routes.id')
                ->on('delivery_route_customers.client_id', '=', 'delivery_routes.client_id');
        })->join('customers', function ($j) {
            $j->on('customers.id', '=', 'delivery_route_customers.customer_id')
                ->on('customers.client_id', '=', 'delivery_routes.client_id');
        })
            ->leftJoin('deliveries', function ($j) {
                $j->on('deliveries.customer_id', '=', 'customers.id')
                    ->on('deliveries.route_id', '=', 'delivery_routes.id')
                    ->on('deliveries.client_id', '=', 'delivery_routes.client_id')
                    ->whereDate('deliveries.delivery_date', today());
            })
            ->where('delivery_routes.client_id', $client)
            ->where('delivery_routes.delivery_boy_id', $user->id)
            ->where('delivery_routes.status', 1)
            ->orderBy('delivery_route_customers.sequence_no')
            ->get([
                'delivery_routes.name as route',
                'delivery_route_customers.sequence_no',
                'customers.name',
                'deliveries.id as delivery_id',
                'deliveries.status_id',
            ]);

        $collected = DB::table('payments')
            ->where('client_id', $client)
            ->where('collected_by_user_id', $user->id)
            ->where('status', 1)
            ->whereDate('payment_date', today())
            ->get();

        return Inertia::render('Portals/Delivery', [
            'tab' => $tab,
            'deliveries' => $rows,
            'routeCustomers' => $routeCustomers,
            'stats' => [
                'assigned' => $rows->count(),
                'completed' => $rows->whereIn('status_id', [3, 8])
                    ->count(),
                'pending' => $rows->where('status_id', 1)
                    ->count(),
                'skipped' => $rows->where('status_id', 4)
                    ->count(),
            ],
            'collections' => [
                'cash' => $collected->where('payment_method_id', 1)
                    ->sum('amount'),
                'upi' => $collected->where('payment_method_id', 2)
                    ->sum('amount'),
                'total' => $collected->sum('amount'),
                'records' => $collected->map(fn ($p) => [
                    'amount' => $p->amount,
                    'method' => DB::table('ref_payment_methods')
                        ->where('id', $p->payment_method_id)
                        ->value('name'),
                    'time' => $p->payment_date,
                    'number' => $p->payment_no,
                ]),
            ],
        ]);
    }
}
