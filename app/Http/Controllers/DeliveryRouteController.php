<?php

namespace App\Http\Controllers;

use App\Models\DeliveryRoute;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DeliveryRouteController extends Controller
{
    public function index()
    {
        // Baad me:
        // $clientId = auth()->user()->client_id;

        $clientId = 1;

        $routes = DeliveryRoute::query()
            ->where('client_id', $clientId)
            ->with([
                'deliveryBoy:id,name',
            ])
            ->withCount('customers')
            ->latest('id')
            ->get()
            ->map(function ($route) {

                /*
                 * Abhi actual deliveries table calculation
                 * connect nahi ki gayi hai.
                 *
                 * Isliye फिलहाल 0%.
                 * Jab deliveries CRUD connect hoga tab actual
                 * delivered percentage yahan calculate hoga.
                 */
                $deliveredPercentage = 0;

                return [
                    'id' => $route->id,
                    'name' => $route->name,

                    'area_locality' =>
                        $route->area_locality,

                    'expected_stops' =>
                        $route->expected_stops,

                    'customers_count' =>
                        $route->customers_count,

                    'delivery_boy_id' =>
                        $route->delivery_boy_id,

                    'delivery_boy' =>
                        $route->deliveryBoy?->name ?? '-',

                    'status' => $route->status,

                    'delivered_percentage' =>
                        $deliveredPercentage,
                ];
            });

        return Inertia::render('Routes/Index', [
            'routes' => $routes,

            'summary' => [
                'routes_count' => $routes->count(),

                /*
                 * Screenshot subtitle:
                 * "8 routes covering 1,240 stops"
                 *
                 * Customer assignments ko actual stops
                 * maana hai.
                 */
                'stops_count' =>
                    $routes->sum('customers_count'),
            ],
        ]);
    }

    public function create()
    {
        $deliveryBoys = \App\Models\User::query()
            ->where('user_type_id', 3)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return Inertia::render('Routes/Form', [
            'deliveryRoute' => null,
            'deliveryBoys' => $deliveryBoys,
        ]);
    }

    public function store(Request $request)
    {
        $clientId = 1;

        $validated = $this->validateRoute(
            $request,
            $clientId
        );

        DeliveryRoute::create([
            'client_id' => $clientId,

            'delivery_boy_id' =>
                $validated['delivery_boy_id'],

            'name' =>
                $validated['name'],

            'area_locality' =>
                $validated['area_locality'] ?? null,

            'expected_stops' =>
                $validated['expected_stops'] ?? 0,

            'route_date' =>
                $validated['route_date'] ?? null,

            'status' =>
                $validated['status'],
        ]);

        return redirect()
            ->route('routes.index')
            ->with(
                'success',
                'Delivery route added successfully.'
            );
    }

    public function edit(DeliveryRoute $deliveryRoute)
    {
        $clientId = 1;

        abort_unless(
            (int) $deliveryRoute->client_id === $clientId,
            403
        );

        $deliveryBoys = \App\Models\User::query()
            ->where('user_type_id', 3)
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return Inertia::render('Routes/Form', [
            'deliveryRoute' => [
                'id' => $deliveryRoute->id,
                'name' => $deliveryRoute->name,
                'area_locality' => $deliveryRoute->area_locality,
                'expected_stops' => $deliveryRoute->expected_stops,
                'delivery_boy_id' => $deliveryRoute->delivery_boy_id,
                'status' => $deliveryRoute->status,
            ],

            'deliveryBoys' => $deliveryBoys,
        ]);
    }

    public function update(
        Request $request,
        DeliveryRoute $deliveryRoute
    ) {
        $clientId = 1;

        abort_unless(
            $deliveryRoute->client_id === $clientId,
            403
        );

        $validated = $this->validateRoute(
            $request,
            $clientId,
            $deliveryRoute->id
        );

        $deliveryRoute->update([
            'delivery_boy_id' =>
                $validated['delivery_boy_id'],

            'name' =>
                $validated['name'],

            'area_locality' =>
                $validated['area_locality'] ?? null,

            'expected_stops' =>
                $validated['expected_stops'] ?? 0,

            'route_date' =>
                $validated['route_date'] ?? null,

            'status' =>
                $validated['status'],
        ]);

        return redirect()
            ->route('routes.index')
            ->with(
                'success',
                'Delivery route updated successfully.'
            );
    }

    public function destroy(DeliveryRoute $deliveryRoute)
    {
        $clientId = 1;

        abort_unless(
            $deliveryRoute->client_id === $clientId,
            403
        );

        /*
         * Customer assigned hai to route delete
         * nahi hone denge.
         */
        if ($deliveryRoute->customers()->exists()) {
            return back()->withErrors([
                'delete' =>
                    'Customers are assigned to this route. Route cannot be deleted.',
            ]);
        }

        $deliveryRoute->delete();

        return back()->with(
            'success',
            'Delivery route deleted successfully.'
        );
    }

    private function validateRoute(
        Request $request,
        int $clientId,
        ?int $routeId = null
    ): array {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'delivery_routes',
                    'name'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'client_id',
                                $clientId
                            )
                    )
                    ->ignore($routeId),
            ],

            /*
             * Delivery Boy sirf users table se
             * jiska user_type_id = 3 hai.
             */
            'delivery_boy_id' => [
                'required',

                Rule::exists('users', 'id')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'user_type_id',
                                3
                            )
                    ),
            ],

            'area_locality' => [
                'nullable',
                'string',
                'max:255',
            ],

            'expected_stops' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'route_date' => [
                'nullable',
                'date',
            ],

            'status' => [
                'required',
                'integer',
                Rule::in([0, 1]),
            ],
        ]);
    }

    private function deliveryBoys(int $clientId)
    {
        return User::query()

            /*
             * IMPORTANT:
             * user_type_id = 3 means Delivery Boy
             */
            ->where('user_type_id', 3)

            /*
             * Agar users table me client_id hai
             * to ye line use karo.
             */
            ->where('client_id', $clientId)

            /*
             * Agar users table me status hai aur
             * 1 = active hai to ye bhi rakho.
             */
            ->where('status', 1)

            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);
    }
}