<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DeliveryBoyController extends Controller
{
    private const DELIVERY_BOY_TYPE = \App\Enums\Role::DeliveryBoy->value;

    private function clientId()
    {
        return \App\Support\Tenant::id();
    }

    /**
     * Delivery Boys Listing
     */
    public function index(Request $request)
    {
        $clientId = $this->clientId();

        $deliveryBoys = User::query()
            ->where('client_id', $clientId)
            ->where(
                'user_type_id',
                self::DELIVERY_BOY_TYPE
            )
            ->latest('id')
            ->get()
            ->map(function ($deliveryBoy) use ($clientId) {

                $route = DB::table('delivery_routes')
                    ->where(
                        'client_id',
                        $clientId
                    )
                    ->where(
                        'delivery_boy_id',
                        $deliveryBoy->id
                    )
                    ->first();

                return [
                    'id' => $deliveryBoy->id,

                    'name' =>
                        $deliveryBoy->name,

                    'mobile' =>
                        $deliveryBoy->mobile,

                    'email' =>
                        $deliveryBoy->email,

                    'status' =>
                        (int) $deliveryBoy->status,

                    'route_id' =>
                        $route->id ?? null,

                    'route' =>
                        $route->name
                        ?? 'Unassigned',

                    'expected_stops' =>
                        (int) (
                            $route->expected_stops
                            ?? 0
                        ),
                ];
            });

        $total = $deliveryBoys->count();

        $active = $deliveryBoys
            ->where('status', 1)
            ->count();

        $inactive = $deliveryBoys
            ->where('status', '!=', 1)
            ->count();

        return Inertia::render(
            'DeliveryBoys/Index',
            [
                'deliveryBoys' =>
                    $deliveryBoys,

                'stats' => [
                    'total' => $total,
                    'active' => $active,
                    'inactive' => $inactive,
                ],
            ]
        );
    }

    /**
     * Add Form
     */
    public function create()
    {
        $clientId = $this->clientId();

        return Inertia::render(
            'DeliveryBoys/Form',
            [
                'deliveryBoy' => null,

                'routes' =>
                    $this->getRoutes(
                        $clientId
                    ),
            ]
        );
    }

    /**
     * Store
     */
    public function store(Request $request)
    {
        $clientId = $this->clientId();

        $validated =
            $this->validateDeliveryBoy(
                $request,
                $clientId
            );

        DB::transaction(function () use (
            $validated,
            $clientId
        ) {

            $deliveryBoy = new User();

            $deliveryBoy->client_id =
                $clientId;

            $deliveryBoy->user_type_id =
                self::DELIVERY_BOY_TYPE;

            $deliveryBoy->name =
                $validated['name'];

            $deliveryBoy->mobile =
                $validated['mobile'];

            $deliveryBoy->email =
                $validated['email']
                ?? null;


            $deliveryBoy->status =
                $validated['status'] ?? 1;

            if (!empty($validated['password'])) {
                $deliveryBoy->password = $validated['password'];
            }

            $deliveryBoy->save();

            $this->assignRoute(
                $deliveryBoy->id,
                $validated['route_id']
                    ?? null,
                $clientId
            );
        });

        return redirect()
            ->route('delivery-boys.index')
            ->with(
                'success',
                'Delivery boy added successfully.'
            );
    }

    /**
     * Edit Form
     */
    public function edit(User $deliveryBoy)
    {
        $clientId = $this->clientId();

        $this->checkDeliveryBoy(
            $deliveryBoy,
            $clientId
        );

        $route = DB::table(
            'delivery_routes'
        )
            ->where(
                'client_id',
                $clientId
            )
            ->where(
                'delivery_boy_id',
                $deliveryBoy->id
            )
            ->first();

        return Inertia::render(
            'DeliveryBoys/Form',
            [
                'deliveryBoy' => [
                    'id' =>
                        $deliveryBoy->id,

                    'name' =>
                        $deliveryBoy->name,

                    'mobile' =>
                        $deliveryBoy->mobile,

                    'email' =>
                        $deliveryBoy->email,

                    'status' =>
                        (int)
                        $deliveryBoy->status,

                    'route_id' =>
                        $route->id
                        ?? '',
                ],

                'routes' =>
                    $this->getRoutes(
                        $clientId,
                        $deliveryBoy->id
                    ),
            ]
        );
    }

    /**
     * Update
     */
    public function update(
        Request $request,
        User $deliveryBoy
    ) {
        $clientId = $this->clientId();

        $this->checkDeliveryBoy(
            $deliveryBoy,
            $clientId
        );

        $validated =
            $this->validateDeliveryBoy(
                $request,
                $clientId,
                $deliveryBoy->id
            );

        DB::transaction(function () use (
            $deliveryBoy,
            $validated,
            $clientId
        ) {

            $deliveryBoy->name =
                $validated['name'];

            $deliveryBoy->mobile =
                $validated['mobile'];

            $deliveryBoy->email =
                $validated['email']
                ?? null;

            $deliveryBoy->status =
                $validated['status'];

            if (!empty($validated['password'])) {
                $deliveryBoy->password = $validated['password'];
            }

            $deliveryBoy->save();

            $this->assignRoute(
                $deliveryBoy->id,
                $validated['route_id']
                    ?? null,
                $clientId
            );
        });

        return redirect()
            ->route('delivery-boys.index')
            ->with(
                'success',
                'Delivery boy updated successfully.'
            );
    }

    /**
     * Delete
     */
    public function destroy(
        User $deliveryBoy
    ) {
        $clientId = $this->clientId();

        $this->checkDeliveryBoy(
            $deliveryBoy,
            $clientId
        );

        if (\Illuminate\Support\Facades\DB::table('deliveries')->where('delivery_boy_id', $deliveryBoy->id)->exists()) {
            return back()->withErrors(['delete' => 'This record has business history and cannot be deleted. Deactivate it instead.']);
        }
        if (\Illuminate\Support\Facades\DB::table('payments')->where('collected_by_user_id', $deliveryBoy->id)->exists()) {
            return back()->withErrors(['delete' => 'This record has business history and cannot be deleted. Deactivate it instead.']);
        }
        if (\Illuminate\Support\Facades\DB::table('customer_ledger')->where('created_by_user_id', $deliveryBoy->id)->exists()) {
            return back()->withErrors(['delete' => 'This record has business history and cannot be deleted. Deactivate it instead.']);
        }

        DB::transaction(function () use (
            $deliveryBoy,
            $clientId
        ) {

            /*
             * delivery_boy_id NOT NULL hai.
             * No foreign key bhi dump me nahi hai.
             *
             * 0 = Unassigned
             */
            DB::table('delivery_routes')
                ->where(
                    'client_id',
                    $clientId
                )
                ->where(
                    'delivery_boy_id',
                    $deliveryBoy->id
                )
                ->update([
                    'delivery_boy_id' => 0,
                    'updated_at' => now(),
                ]);

            $deliveryBoy->delete();
        });

        return back()->with(
            'success',
            'Delivery boy deleted successfully.'
        );
    }

    /**
     * Validation
     */
    private function validateDeliveryBoy(
        Request $request,
        int $clientId,
        ?int $deliveryBoyId = null
    ) {
        return $request->validate([
            'password' => [$deliveryBoyId ? 'nullable' : 'required', 'confirmed', \Illuminate\Validation\Rules\Password::min(12)],
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'mobile' => [
                'required',
                'digits:10',

                Rule::unique(
                    'users',
                    'mobile'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'client_id',
                                $clientId
                            )
                    )
                    ->ignore(
                        $deliveryBoyId
                    ),
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'route_id' => [
                'nullable',

                Rule::exists(
                    'delivery_routes',
                    'id'
                )->where(
                    fn ($query) =>
                        $query->where(
                            'client_id',
                            $clientId
                        )
                ),
            ],

            'status' => [
                'required',
                'integer',
                Rule::in([
                    0,
                    1,
                ]),
            ],
        ]);
    }

    /**
     * Assign Route
     */
    private function assignRoute(
        int $deliveryBoyId,
        $routeId,
        int $clientId
    ) {
        /*
         * Pehle delivery boy ki
         * existing route unassign.
         */
        DB::table('delivery_routes')
            ->where(
                'client_id',
                $clientId
            )
            ->where(
                'delivery_boy_id',
                $deliveryBoyId
            )
            ->update([
                'delivery_boy_id' => 0,
                'updated_at' => now(),
            ]);

        /*
         * No route selected.
         */
        if (empty($routeId)) {
            return;
        }

        $route = DB::table(
            'delivery_routes'
        )
            ->where(
                'client_id',
                $clientId
            )
            ->where(
                'id',
                $routeId
            )
            ->lockForUpdate()
            ->first();

        if (!$route) {
            throw ValidationException
                ::withMessages([
                    'route_id' =>
                        'Selected route does not exist.',
                ]);
        }

        /*
         * Check karo route agar kisi
         * REAL delivery boy ke paas hai.
         */
        if (
            !empty($route->delivery_boy_id)
            && $route->delivery_boy_id != 0
            && $route->delivery_boy_id
                != $deliveryBoyId
        ) {

            $assignedBoy =
                User::query()
                    ->where(
                        'id',
                        $route
                            ->delivery_boy_id
                    )
                    ->where(
                        'client_id',
                        $clientId
                    )
                    ->where(
                        'user_type_id',
                        self::DELIVERY_BOY_TYPE
                    )
                    ->first();

            if ($assignedBoy) {

                throw ValidationException
                    ::withMessages([
                        'route_id' =>
                            'This route is already assigned to '
                            . $assignedBoy->name
                            . '.',
                    ]);
            }
        }

        DB::table('delivery_routes')
            ->where(
                'id',
                $routeId
            )
            ->where(
                'client_id',
                $clientId
            )
            ->update([
                'delivery_boy_id' =>
                    $deliveryBoyId,

                'updated_at' => now(),
            ]);
    }

    /**
     * Routes Dropdown
     */
    private function checkDeliveryBoy(User $deliveryBoy, int $clientId): void
    {
        abort_unless((int) $deliveryBoy->client_id === $clientId
            && $deliveryBoy->role() === \App\Enums\Role::DeliveryBoy, 403);
    }

    private function getRoutes(
        int $clientId,
        ?int $currentDeliveryBoyId = null
    ) {
        return DB::table('delivery_routes')
            ->where('client_id', $clientId)
            ->where('status', 1)
            ->where(function ($query) use ($currentDeliveryBoyId) {
                $query->where('delivery_boy_id', 0);
                if ($currentDeliveryBoyId !== null) {
                    $query->orWhere('delivery_boy_id', $currentDeliveryBoyId);
                }
            })
            ->orderBy('name')->get(['id', 'name']);
    }
}
