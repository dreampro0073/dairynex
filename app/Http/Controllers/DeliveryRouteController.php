<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\DeliveryRoute;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Throwable;

class DeliveryRouteController extends Controller
{
    public function index()
    {

        $clientId = Tenant::id();

        $routes = DeliveryRoute::query()
            ->where('client_id', $clientId)
            ->with([
                'deliveryBoy' => fn ($query) => $query->where('client_id', $clientId)
                    ->where('user_type_id', Role::DeliveryBoy->value)
                    ->select('id', 'name'),
            ])
            ->withCount(['customers' => fn ($query) => $query->where('customers.client_id', $clientId)])
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

                    'area_locality' => $route->area_locality,

                    'expected_stops' => $route->expected_stops,

                    'customers_count' => $route->customers_count,

                    'delivery_boy_id' => $route->delivery_boy_id,

                    'delivery_boy' => $route->deliveryBoy?->name ?? '-',

                    'status' => $route->status,

                    'delivered_percentage' => $deliveredPercentage,
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
                'stops_count' => $routes->sum('customers_count'),
            ],
        ]);
    }

    public function create()
    {
        $deliveryBoys = $this->deliveryBoys(Tenant::id());

        return Inertia::render('Routes/Form', [
            'deliveryRoute' => null,
            'deliveryBoys' => $deliveryBoys,
        ]);
    }

    public function store(Request $request)
    {
        $clientId = Tenant::id();

        $validated = $this->validateRoute(
            $request,
            $clientId
        );

        try {
            DeliveryRoute::create([
                'client_id' => $clientId,
                'delivery_boy_id' => (int) ($validated['delivery_boy_id'] ?? 0),
                'name' => $validated['name'],
                'area_locality' => $validated['area_locality'] ?? null,
                'expected_stops' => $validated['expected_stops'] ?? 0,
                'route_date' => $validated['route_date'] ?? null,
                'status' => $validated['status'],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with(
                'error',
                'Route could not be saved because of a server error. Please try again.'
            );
        }

        return redirect()
            ->route('routes.index')
            ->with(
                'success',
                'Delivery route added successfully.'
            );
    }

    public function edit(DeliveryRoute $deliveryRoute)
    {
        $clientId = Tenant::id();

        abort_unless(
            (int) $deliveryRoute->client_id === $clientId,
            403
        );

        $deliveryBoys = $this->deliveryBoys($clientId);

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
        $clientId = Tenant::id();

        abort_unless(
            $deliveryRoute->client_id === $clientId,
            403
        );

        $validated = $this->validateRoute(
            $request,
            $clientId,
            $deliveryRoute->id
        );

        try {
            $deliveryRoute->update([
                'delivery_boy_id' => (int) ($validated['delivery_boy_id'] ?? 0),
                'name' => $validated['name'],
                'area_locality' => $validated['area_locality'] ?? null,
                'expected_stops' => $validated['expected_stops'] ?? 0,
                'route_date' => $validated['route_date'] ?? null,
                'status' => $validated['status'],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with(
                'error',
                'Route could not be updated because of a server error. Please try again.'
            );
        }

        return redirect()
            ->route('routes.index')
            ->with(
                'success',
                'Delivery route updated successfully.'
            );
    }

    public function destroy(DeliveryRoute $deliveryRoute)
    {
        $clientId = Tenant::id();

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
                'delete' => 'Customers are assigned to this route. Route cannot be deleted.',
            ]);
        }

        if (DB::table('deliveries')->where('route_id', $deliveryRoute->id)->exists()) {
            return back()
                ->withErrors([
                    'delete' => 'This record has business history and cannot be deleted. Deactivate it instead.',
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
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'delivery_routes',
                    'name'
                )
                    ->where(
                        fn ($query) => $query->where(
                            'client_id',
                            $clientId
                        )
                    )
                    ->ignore($routeId),
            ],

            // Restrict assignment to active delivery staff in the current dairy.
            'delivery_boy_id' => [
                'required',
                'integer',
                'min:0',
                Rule::when(
                    (int) $request->input('delivery_boy_id') !== 0,
                    [
                        Rule::exists('users', 'id')
                            ->where(
                                fn ($query) => $query->where(
                                    'user_type_id',
                                    Role::DeliveryBoy->value
                                )->where('client_id', $clientId)->where('status', 1)
                            ),
                    ],
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

        if ($validator->fails()) {
            Log::warning('Delivery route form validation failed.', [
                'client_id' => $clientId,
                'route_id' => $routeId,
                'failed_fields' => array_keys($validator->errors()->messages()),
            ]);

            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function deliveryBoys(int $clientId)
    {
        return User::query()

// Preserve the SQL reference ID for delivery staff.
            ->where('user_type_id', Role::DeliveryBoy->value)

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
