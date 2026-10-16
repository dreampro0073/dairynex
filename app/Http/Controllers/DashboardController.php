<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $client = Tenant::id();
        $today = DB::table('deliveries')->where('client_id', $client)->whereDate('delivery_date', today());
        $total = (clone $today)->count();
        $delivered = (clone $today)->whereIn('status_id', [3, 8])->count();
        $payments = DB::table('payments')->where('client_id', $client)->where('status', 1)->whereDate('payment_date', today());
        $ledgerTotals = DB::table('customer_ledger')->where('client_id', $client)->selectRaw('customer_id, SUM(debit-credit) as movement')->groupBy('customer_id');
        $balances = Customer::where('customers.client_id', $client)->leftJoinSub($ledgerTotals, 'ledger_totals', 'ledger_totals.customer_id', '=', 'customers.id')
            ->selectRaw('customers.id, customers.opening_balance + COALESCE(ledger_totals.movement,0) as outstanding')->get();
        $recent = (clone $today)->orderByDesc('id')->limit(8)->get();
        $customerNames = Customer::where('client_id', $client)->whereIn('id', $recent->pluck('customer_id'))->pluck('name', 'id');
        $routeNames = DB::table('delivery_routes')->where('client_id', $client)->whereIn('id', $recent->pluck('route_id'))->pluck('name', 'id');
        $lines = DB::table('delivery_items as items')->join('products', function ($join) {
            $join->on('products.id', '=', 'items.product_id')->on('products.client_id', '=', 'items.client_id');
        })->where('items.client_id', $client)->whereIn('items.delivery_id', $recent->pluck('id'))->get(['items.delivery_id', 'products.name'])->groupBy('delivery_id');
        $sales = DB::table('delivery_items as items')->join('deliveries', function ($join) {
            $join->on('deliveries.id', '=', 'items.delivery_id')->on('deliveries.client_id', '=', 'items.client_id');
        })->join('products', function ($join) {
            $join->on('products.id', '=', 'items.product_id')->on('products.client_id', '=', 'items.client_id');
        })->where('deliveries.client_id', $client)->whereDate('deliveries.delivery_date', today())->whereIn('deliveries.status_id', [3, 8])
            ->selectRaw('products.name, SUM(items.amount) as amount')->groupBy('products.id', 'products.name')->get();
        $maxSales = max(1, (float) $sales->max('amount'));
        $modes = DB::table('payments')->join('ref_payment_methods as methods', 'methods.id', '=', 'payments.payment_method_id')
            ->where('payments.client_id', $client)->where('payments.status', 1)->whereDate('payments.payment_date', today())
            ->selectRaw('methods.name, SUM(payments.amount) as amount')->groupBy('methods.id', 'methods.name')->get();
        $maxMode = max(1, (float) $modes->max('amount'));
        $activity = DB::table('customer_ledger')->where('client_id', $client)->orderByDesc('id')->limit(6)->get();
        $activityNames = Customer::where('client_id', $client)->whereIn('id', $activity->pluck('customer_id'))->pluck('name', 'id');
        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'today_delivered' => $delivered, 'today_total' => $total, 'delivery_percentage' => $total ? round($delivered / $total * 100) : 0,
                'active_customers' => Customer::where('client_id', $client)->where('status', 1)->count(),
                'route_count' => DB::table('delivery_routes')->where('client_id', $client)->where('status', 1)->count(),
                'collected_today' => (clone $payments)->sum('amount'), 'cash_today' => (clone $payments)->where('payment_method_id', 1)->sum('amount'),
                'upi_today' => (clone $payments)->where('payment_method_id', 2)->sum('amount'),
                'outstanding' => $balances->sum(fn ($row) => max(0, (float) $row->outstanding)),
                'overdue_customers' => DB::table('invoices')->where('client_id', $client)->where('due_amount', '>', 0)->whereIn('status', [2,3])
                    ->whereDate('period_to', '<', today()->startOfMonth())->distinct()->count('customer_id'),
            ],
            'deliveries' => $recent->map(fn ($row) => ['id' => $row->id, 'customer' => $customerNames[$row->customer_id] ?? 'Customer', 'route' => $routeNames[$row->route_id] ?? 'Unassigned',
                'product' => ($lines[$row->id] ?? collect())->pluck('name')->implode(', '), 'status' => $row->status_id, 'amount' => $row->total_amount]),
            'ledgerActivities' => $activity->map(fn ($row) => ['id' => $row->id, 'customer' => $activityNames[$row->customer_id] ?? 'Customer',
                'description' => $row->description, 'time' => null, 'type' => (float) $row->credit > 0 ? 'payment' : 'delivery', 'amount' => max((float) $row->debit, (float) $row->credit)]),
            'productSales' => $sales->map(fn ($row) => ['name' => $row->name, 'amount' => $row->amount, 'percentage' => round($row->amount / $maxSales * 100)]),
            'paymentModes' => $modes->map(fn ($row) => ['name' => $row->name, 'amount' => $row->amount, 'percentage' => round($row->amount / $maxMode * 100)]),
        ]);
    }
}
