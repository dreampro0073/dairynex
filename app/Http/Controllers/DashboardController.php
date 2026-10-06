<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Baad me:
        // $clientId = auth()->user()->client_id;

        $clientId = 1;

        $activeCustomers = Customer::query()
            ->where('client_id', $clientId)
            ->where('status', 1)
            ->count();

        $routeCount = DB::table('delivery_route_customers')
            ->where('client_id', $clientId)
            ->distinct()
            ->count('route_id');

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'today_delivered' => 0,
                'today_total' => 0,
                'delivery_percentage' => 0,

                'active_customers' => $activeCustomers,
                'route_count' => $routeCount,

                'collected_today' => 0,
                'cash_today' => 0,
                'upi_today' => 0,

                'outstanding' => 0,
                'overdue_customers' => 0,
            ],

            'deliveries' => [],

            'ledgerActivities' => [],

            'productSales' => [],

            'paymentModes' => [],
        ]);
    }
}