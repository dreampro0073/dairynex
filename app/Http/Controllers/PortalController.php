<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PortalController extends Controller
{
    public function platform()
    {
        return Inertia::render('Portals/Overview', [
            'title' => 'Platform Overview',
            'metrics' => [
                'Total dairies' => Client::count(),
                'Active dairies' => Client::where('status', 1)->count(),
                'Other dairies' => Client::where('status', '!=', 1)->count(),
            ],
            'records' => Client::orderBy('name')->paginate(20)->through(fn ($client) => [
                'id' => $client->id, 'name' => $client->name,
                'detail' => (int) $client->status === 1 ? 'Active' : 'Inactive',
            ]),
            'notice' => 'Dairy account management and SaaS plan workflows are pending.',
        ]);
    }

    public function delivery(Request $request)
    {
        $user = $request->user();
        $query = DB::table('deliveries')->where('client_id', $user->client_id)
            ->where('delivery_boy_id', $user->id)->whereDate('delivery_date', today());

        return Inertia::render('Portals/Overview', [
            'title' => "Today's Assigned Deliveries",
            'metrics' => ['Assigned today' => (clone $query)->count()],
            'records' => $query->orderBy('id')->paginate(20)->through(fn ($delivery) => [
                'id' => $delivery->id, 'name' => 'Delivery #'.$delivery->id,
                'detail' => 'Status #'.$delivery->status_id.' · ₹'.$delivery->total_amount,
            ]),
            'notice' => 'Delivery completion, PIN verification and collection actions are pending.',
        ]);
    }

    public function customer()
    {
        // No customer-to-login mapping exists in the reference schema. Fail closed
        // instead of guessing ownership from shared email/mobile values.
        return Inertia::render('Portals/Overview', [
            'title' => 'Customer Account', 'metrics' => [], 'records' => null,
            'notice' => 'Customer record linking and self-service workflows are pending. Contact your dairy administrator.',
        ]);
    }
}
